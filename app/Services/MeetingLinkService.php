<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\MeetingAccount;
use Illuminate\Support\Facades\Log;
use Throwable;

class MeetingLinkService
{
    public function __construct(
        protected ZohoLmsService $zoho,
        protected ZoomMeetingService $zoom
    ) {
    }

    /**
     * Create Join link for the schedule using the selected meeting account.
     *
     * @return array{meeting: string, calendar: string}
     */
    public function attachIntegrations(ClassSchedule $schedule): array
    {
        $account = $this->resolveAccount($schedule);

        return [
            'meeting' => $this->attachMeetingIfNeeded($schedule, $account),
            'calendar' => $account && $account->isZoho()
                ? $this->zoho->attachCalendarIfNeeded($schedule, $account)
                : 'skipped',
        ];
    }

    public function attachMeetingIfNeeded(ClassSchedule $schedule, ?MeetingAccount $account = null): string
    {
        if (filled($schedule->zoho_link)) {
            return 'existing';
        }

        $account = $account ?: $this->resolveAccount($schedule);
        if (! $account || ! $account->is_active) {
            return 'not_configured';
        }

        try {
            if ($account->isZoom()) {
                $meeting = $this->zoom->createMeetingForSchedule($schedule, $account);
            } else {
                $meeting = $this->zoho->createMeetingForSchedule($schedule, $account);
            }
        } catch (Throwable $e) {
            Log::error('Meeting create threw', [
                'provider' => $account->provider,
                'account_id' => $account->id,
                'message' => $e->getMessage(),
            ]);

            return 'failed';
        }

        if (! $meeting || empty($meeting['join_link'])) {
            return 'failed';
        }

        $schedule->zoho_link = $meeting['join_link'];
        $schedule->meeting_account_id = $account->id;
        $schedule->save();

        return 'created';
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
