<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ClassSchedule;
use App\Services\ZohoLmsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

$zoho = app(ZohoLmsService::class);
$status = $zoho->connectionStatus();

$ref = new ReflectionClass($zoho);
$tokenMethod = $ref->getMethod('accessToken');
$tokenMethod->setAccessible(true);
$token = $tokenMethod->invoke($zoho);

$meetingUser = Http::withToken($token, 'Zoho-oauthtoken')
    ->acceptJson()
    ->get(rtrim(config('zoho.meeting_url'), '/') . '/api/v2/user.json');

$calendarList = Http::withToken($token, 'Zoho-oauthtoken')
    ->acceptJson()
    ->get(rtrim(config('zoho.calendar_url'), '/') . '/calendars');

$cols = ['recurrence_type', 'recurrence_days', 'recurrence_count', 'recurrence_until', 'reminders', 'duration_minutes', 'zoho_link', 'zoho_calendar_event_uid'];
$colStatus = [];
foreach ($cols as $col) {
    $colStatus[$col] = Schema::hasTable('class_schedules') && Schema::hasColumn('class_schedules', $col);
}

$sample = new ClassSchedule([
    'recurrence_type' => 'weekly',
    'recurrence_days' => ['MO', 'WE', 'FR'],
    'recurrence_count' => 13,
    'reminders' => ClassSchedule::defaultReminders(),
    'scheduled_at' => now('Asia/Dubai')->addDay(),
    'duration_minutes' => 60,
]);

$out = [
    'ok' => empty($status['error']) && ($status['configured'] ?? false),
    'host_config' => $zoho->hostAccountEmail(),
    'oauth_connected_as' => $status['connected_email'] ?? null,
    'host_match' => isset($status['connected_email']) && strcasecmp($status['connected_email'], $zoho->hostAccountEmail()) === 0,
    'org_id' => $status['org_id'] ?? null,
    'presenter_zuid' => $status['presenter_zuid'] ?? null,
    'calendar_uid' => $status['calendar_uid'] ?? null,
    'workdrive_folder_set' => filled($status['workdrive_folder_id'] ?? null),
    'zoho_status_error' => $status['error'] ?? null,
    'meeting_user_api' => [
        'http' => $meetingUser->status(),
        'email' => data_get($meetingUser->json(), 'userDetails.email')
            ?: data_get($meetingUser->json(), 'userDetails.primaryEmail')
            ?: data_get($meetingUser->json(), 'email'),
        'zuid' => data_get($meetingUser->json(), 'userDetails.zuid') ?: data_get($meetingUser->json(), 'zuid'),
    ],
    'calendar_api' => [
        'http' => $calendarList->status(),
        'count' => count($calendarList->json('calendars') ?? []),
        'has_configured_uid' => collect($calendarList->json('calendars') ?? [])
            ->contains(fn ($c) => ($c['uid'] ?? '') === ($status['calendar_uid'] ?? '')),
    ],
    'db_columns' => $colStatus,
    'supports_recurrence' => ClassSchedule::supportsRecurrenceColumns(),
    'sample_rrule' => $sample->zohoRrule(),
    'sample_reminders' => $sample->reminderList(),
];

$out['ok'] = $out['ok']
    && $out['host_match']
    && ($out['meeting_user_api']['http'] === 200)
    && ($out['calendar_api']['http'] === 200)
    && $out['supports_recurrence']
    && !in_array(false, $colStatus, true);

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit($out['ok'] ? 0 : 2);
