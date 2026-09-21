<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\MeetingAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZoomMeetingService
{
    public function isReady(?MeetingAccount $account = null): bool
    {
        return $account
            && $account->isZoom()
            && $account->is_active
            && $account->hasRequiredCredentials();
    }

    public function createMeetingForSchedule(ClassSchedule $schedule, MeetingAccount $account): ?array
    {
        if (! $this->isReady($account)) {
            return null;
        }

        $schedule->loadMissing(['course', 'instructor', 'students']);
        $token = $this->accessToken($account);
        if (! $token) {
            return null;
        }

        $start = $schedule->scheduled_at
            ? $schedule->scheduled_at->copy()->timezone($account->timezone ?: 'UTC')
            : null;
        if (! $start) {
            return null;
        }

        $payload = [
            'topic' => $schedule->calendarTitle(),
            'type' => 2, // scheduled
            'start_time' => $start->format('Y-m-d\TH:i:s'),
            'timezone' => $account->timezone ?: 'Asia/Dubai',
            'duration' => $schedule->durationMinutes(),
            'agenda' => trim(($schedule->course->title ?? '') . "\n" . ($schedule->notes ?? '')),
            'settings' => [
                'join_before_host' => false,
                'waiting_room' => true,
                'mute_upon_entry' => true,
            ],
        ];

        $userId = (string) ($account->credential('user_id')
            ?: $account->host_email
            ?: 'me');
        $userId = rawurlencode($userId);
        $response = $this->http()
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->post('https://api.zoom.us/v2/users/' . $userId . '/meetings', $payload);

        if (! $response->successful()) {
            Log::error('Zoom Meeting create failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'account_id' => $account->id,
            ]);

            return null;
        }

        $data = $response->json() ?? [];

        return [
            'join_link' => $data['join_url'] ?? null,
            'start_link' => $data['start_url'] ?? null,
            'meeting_key' => isset($data['id']) ? (string) $data['id'] : null,
            'host_email' => $account->host_email,
        ];
    }

    /**
     * Update an existing Zoom meeting (time / duration / timezone / topic).
     */
    public function updateMeetingForSchedule(
        ClassSchedule $schedule,
        MeetingAccount $account,
        string $meetingId
    ): bool {
        if (! $this->isReady($account)) {
            return false;
        }

        $schedule->loadMissing(['course', 'instructor']);
        $token = $this->accessToken($account);
        if (! $token) {
            return false;
        }

        $timezone = $schedule->timezoneName();
        $start = $schedule->scheduled_at
            ? $schedule->scheduled_at->copy()->shiftTimezone($timezone)
            : null;
        if (! $start) {
            return false;
        }

        $payload = [
            'topic' => $schedule->calendarTitle(),
            'type' => 2,
            'start_time' => $start->format('Y-m-d\TH:i:s'),
            'timezone' => $timezone,
            'duration' => $schedule->durationMinutes(),
            'agenda' => trim(($schedule->course->title ?? '') . "\n" . ($schedule->notes ?? '')),
        ];

        $response = $this->http()
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->patch('https://api.zoom.us/v2/meetings/' . rawurlencode($meetingId), $payload);

        if (! $response->successful()) {
            Log::error('Zoom Meeting update failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'meeting_id' => $meetingId,
                'schedule_id' => $schedule->id,
                'account_id' => $account->id,
            ]);

            return false;
        }

        return true;
    }

    protected function accessToken(MeetingAccount $account): ?string
    {
        $cacheKey = 'zoom_s2s_token_' . $account->id;

        try {
            return Cache::remember($cacheKey, 50 * 60, function () use ($account) {
                $accountId = $account->credential('account_id');
                $clientId = $account->credential('client_id');
                $clientSecret = $account->credential('client_secret');

                $response = $this->http()
                    ->withBasicAuth($clientId, $clientSecret)
                    ->asForm()
                    ->post('https://zoom.us/oauth/token', [
                        'grant_type' => 'account_credentials',
                        'account_id' => $accountId,
                    ]);

                if (! $response->successful() || empty($response->json('access_token'))) {
                    Log::error('Zoom S2S token failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'account_id' => $account->id,
                    ]);
                    throw new \RuntimeException('Zoom OAuth token failed.');
                }

                return $response->json('access_token');
            });
        } catch (Throwable $e) {
            Cache::forget($cacheKey);
            Log::error('Zoom token exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    protected function http()
    {
        $client = Http::timeout(30);
        $caBundle = base_path('.tools/php/extras/ssl/cacert.pem');
        if (is_file($caBundle)) {
            $client = $client->withOptions(['verify' => $caBundle]);
        }

        return $client;
    }
}
