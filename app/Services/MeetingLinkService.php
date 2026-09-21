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
