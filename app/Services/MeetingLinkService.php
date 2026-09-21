<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\MeetingAccount;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeetingLinkService
{
    public ?string $lastError = null;

    public function __construct(
        protected ZohoLmsService $zoho,
        protected ZoomMeetingService $zoom
    ) {
    }

    /**
     * Create Join link for the schedule using the selected meeting account.
     *
     * @return array{meeting: string, calendar: string, error?: string|null}
     */
    public function attachIntegrations(ClassSchedule $schedule): array
    {
        $this->lastError = null;
        ClassSchedule::ensureMeetingKeyColumn();
        $account = $this->resolveAccount($schedule);

        $meeting = $this->attachMeetingIfNeeded($schedule, $account);

        return [
            'meeting' => $meeting,
            'calendar' => $account && $account->isZoho()
                ? $this->zoho->attachCalendarIfNeeded($schedule, $account)
                : 'skipped',
            'error' => $this->lastError,
        ];
    }

    /**
     * After schedule edit: create link if missing, otherwise update Zoho/Zoom when time changed.
     *
     * @return array{meeting: string, calendar: string, error?: string|null}
     */
    public function syncAfterScheduleUpdate(ClassSchedule $schedule, bool $timeChanged): array
    {
        $this->lastError = null;
        ClassSchedule::ensureMeetingKeyColumn();
        $schedule->refresh();

        if (! filled($schedule->zoho_link)) {
            return $this->attachIntegrations($schedule);
        }

        if (! $timeChanged) {
            return [
                'meeting' => 'existing',
                'calendar' => 'skipped',
                'error' => null,
            ];
        }

        $account = $this->resolveAccount($schedule);
        $updated = $this->updateMeetingIfPossible($schedule, $account);

        return [
            'meeting' => $updated,
            'calendar' => 'skipped',
            'error' => $this->lastError,
        ];
    }

    public function attachMeetingIfNeeded(ClassSchedule $schedule, ?MeetingAccount $account = null): string
    {
        if (filled($schedule->zoho_link)) {
            return 'existing';
        }

        $account = $account ?: $this->resolveAccount($schedule);
        if (! $account || ! $account->is_active) {
            $this->lastError = 'No active meeting account is selected for this session.';

            return 'not_configured';
        }

        if ($account->isZoom()) {
            return $this->createWithOptionalLock(
                'zoom-meeting-create-' . (int) $schedule->id,
                $schedule,
                $account,
                fn () => $this->zoom->createMeetingForSchedule($schedule, $account),
                'Zoom'
            );
        }

        return $this->createWithOptionalLock(
            'zoho-meeting-create-' . (int) $schedule->id,
            $schedule,
            $account,
            fn () => $this->zoho->createMeetingForSchedule($schedule, $account),
            'Zoho'
        );
    }

    public function updateMeetingIfPossible(ClassSchedule $schedule, ?MeetingAccount $account = null): string
    {
        $account = $account ?: $this->resolveAccount($schedule);
        if (! $account || ! $account->is_active) {
            $this->lastError = 'No active meeting account is selected for this session.';

            return 'not_configured';
        }

        $meetingKey = $schedule->resolveMeetingKey();
        if (! $meetingKey) {
            $this->lastError = 'Cannot update remote meeting — missing meeting key. Re-create the Join link or paste a new one.';

            return 'failed';
        }

        try {
            if ($account->isZoom()) {
                if (! $this->zoom->isReady($account)) {
                    $this->lastError = 'Zoom account has no API credentials — update the time in Zoom manually, or paste a new Join URL.';

                    return 'manual_required';
                }
                $ok = $this->zoom->updateMeetingForSchedule($schedule, $account, $meetingKey);
            } else {
                $ok = $this->zoho->updateMeetingForSchedule($schedule, $account, $meetingKey);
            }
        } catch (Throwable $e) {
            Log::error('Meeting update threw', [
                'schedule_id' => $schedule->id,
                'message' => $e->getMessage(),
            ]);
            $this->lastError = 'Meeting update API error: ' . $e->getMessage();

            return 'failed';
        }

        if (! $ok) {
            $this->lastError = $this->lastError
                ?: 'Remote meeting was not updated. Check Meeting Account credentials / host.';

            return 'failed';
        }

        if (blank($schedule->meeting_key)) {
            $schedule->meeting_key = $meetingKey;
            $schedule->save();
        }

        return 'updated';
    }

    /**
     * @param  callable(): (?array)  $creator
     */
    protected function createWithOptionalLock(
        string $lockKey,
        ClassSchedule $schedule,
        MeetingAccount $account,
        callable $creator,
        string $providerLabel
    ): string {
        if ($account->isZoom() && ! $this->zoom->isReady($account)) {
            $this->lastError = 'Zoom account has no API credentials — paste the Join URL on the schedule form.';

            return 'manual_required';
        }

        $lock = null;
        $gotLock = false;
        try {
            $lock = cache()->lock($lockKey, 45);
            $gotLock = (bool) $lock->get();
        } catch (Throwable $e) {
            // File/array cache can fail locks on some hosts — never treat that as success.
            Log::warning('Meeting create lock unavailable; continuing without lock', [
                'key' => $lockKey,
                'message' => $e->getMessage(),
            ]);
        }

        // IMPORTANT: a failed lock must NOT return "existing" — that left sessions on "Link soon".
        try {
            $schedule->refresh();
            if (filled($schedule->zoho_link)) {
                return 'existing';
            }

            try {
                $meeting = $creator();
            } catch (Throwable $e) {
                Log::error($providerLabel . ' Meeting create threw', [
                    'account_id' => $account->id,
                    'schedule_id' => $schedule->id,
                    'message' => $e->getMessage(),
                ]);
                $this->lastError = $providerLabel . ' API error: ' . $e->getMessage();

                return 'failed';
            }

            if (! $meeting || empty($meeting['join_link'])) {
                $this->lastError = $providerLabel . ' did not return a Join link. Check Meeting Account host email / Presenter ZUID / credentials (host='
                    . ($account->host_email ?: 'default') . ').';

                return 'failed';
            }

            $schedule->zoho_link = $meeting['join_link'];
            $schedule->meeting_account_id = $account->id;
            if (! empty($meeting['meeting_key'])) {
                $schedule->meeting_key = (string) $meeting['meeting_key'];
            }
            $schedule->save();

            return 'created';
        } finally {
            if ($gotLock && $lock) {
                try {
                    $lock->release();
                } catch (Throwable $e) {
                    // ignore
                }
            }
        }
    }

    public function resolveAccount(ClassSchedule $schedule): ?MeetingAccount
    {
        $schedule->loadMissing('meetingAccount');
        if ($schedule->meetingAccount && $schedule->meetingAccount->is_active) {
            return $schedule->meetingAccount;
        }

        if ($schedule->meeting_account_id) {
            return MeetingAccount::query()
                ->whereKey($schedule->meeting_account_id)
                ->where('is_active', true)
                ->first();
        }

        $defaultId = MeetingAccount::defaultId();

        return $defaultId
            ? MeetingAccount::query()->whereKey($defaultId)->first()
            : null;
    }
}
