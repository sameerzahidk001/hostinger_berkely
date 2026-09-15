<?php

namespace App\Console\Commands;

use App\Services\ZohoLmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ZohoConnectCommand extends Command
{
    protected $signature = 'zoho:connect
                            {--code= : Self Client grant code from api-console.zoho.com}
                            {--client-id= : Zoho API Console client ID}
                            {--client-secret= : Zoho API Console client secret}
                            {--folder-id= : WorkDrive folder ID for LMS uploads}
                            {--calendar-uid= : Zoho Calendar UID for class events}';

    protected $description = 'Connect Zoho Meeting, Calendar, and WorkDrive for LMS class schedules and file uploads';

    public const SCOPES = 'ZohoMeeting.meeting.CREATE,ZohoMeeting.meeting.READ,ZohoMeeting.meeting.UPDATE,ZohoMeeting.manageOrg.READ,WorkDrive.files.CREATE,WorkDrive.files.READ,WorkDrive.links.CREATE,ZohoCalendar.event.ALL,ZohoCalendar.calendar.ALL';

    public function handle(ZohoLmsService $zoho): int
    {
        if ($this->option('client-id')) {
            $this->writeEnv('ZOHO_CLIENT_ID', (string) $this->option('client-id'));
            config(['zoho.client_id' => $this->option('client-id')]);
        }
        if ($this->option('client-secret')) {
            $this->writeEnv('ZOHO_CLIENT_SECRET', (string) $this->option('client-secret'));
            config(['zoho.client_secret' => $this->option('client-secret')]);
        }
        if ($this->option('folder-id')) {
            $this->writeEnv('ZOHO_WORKDRIVE_FOLDER_ID', (string) $this->option('folder-id'));
            config(['zoho.workdrive_folder_id' => $this->option('folder-id')]);
        }
        if ($this->option('calendar-uid')) {
            $this->writeEnv('ZOHO_CALENDAR_UID', (string) $this->option('calendar-uid'));
            config(['zoho.calendar_uid' => $this->option('calendar-uid')]);
        }

        if ($this->option('code')) {
            if (! filled(config('zoho.client_id')) || ! filled(config('zoho.client_secret'))) {
                $this->error('Pass --client-id and --client-secret (or set them in .env first).');
                return self::FAILURE;
            }

            try {
                $tokens = $zoho->exchangeGrantCode((string) $this->option('code'));
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
                return self::FAILURE;
            }

            $this->writeEnv('ZOHO_REFRESH_TOKEN', $tokens['refresh_token']);
            config(['zoho.refresh_token' => $tokens['refresh_token']]);
            $this->info('Refresh token saved to .env');
        }

        $status = $zoho->connectionStatus();
        $this->persistDetectedIds($status);

        $this->line('Account (host): ' . ($status['account_email'] ?: 'bdm@berkeleyme.com'));
        if (!empty($status['connected_email'])) {
            $this->line('OAuth connected as: ' . $status['connected_email']);
        }
        $this->line('OAuth configured: ' . ($status['configured'] ? 'yes' : 'no'));
        if ($status['org_id']) {
            $this->line('Meeting org (zsoid): ' . $status['org_id']);
        }
        if ($status['presenter_zuid']) {
            $this->line('Presenter ZUID: ' . $status['presenter_zuid']);
        }
        if ($status['workdrive_folder_id']) {
            $this->line('WorkDrive folder: ' . $status['workdrive_folder_id']);
        }
        if ($status['calendar_uid']) {
            $this->line('Calendar UID: ' . $status['calendar_uid']);
        }
        if ($status['meeting_user']['primaryEmail'] ?? $status['meeting_user']['email'] ?? null) {
            $this->info('Meeting API OK as ' . ($status['meeting_user']['primaryEmail'] ?? $status['meeting_user']['email']));
        }
        if ($status['error']) {
            $this->warn($status['error']);
        }

        if (! $status['configured']) {
            $this->newLine();
            $this->line('One-time setup (logged in as bdm@berkeleyme.com):');
            $this->line('1. Open https://api-console.zoho.com → Self Client');
            $this->line('2. Generate a code with this scope:');
            $this->line('   ' . self::SCOPES);
            $this->line('3. php artisan zoho:connect --code=GRANT_CODE');
            $this->line('4. Optional: php artisan zoho:connect --folder-id=FOLDER_ID --calendar-uid=CALENDAR_UID');
        }

        return $status['configured'] && empty($status['error']) ? self::SUCCESS : self::FAILURE;
    }

    protected function persistDetectedIds(array $status): void
    {
        $this->writeEnv('ZOHO_ACCOUNT_EMAIL', 'bdm@berkeleyme.com');
        config(['zoho.account_email' => 'bdm@berkeleyme.com']);

        $map = [
            'org_id' => 'ZOHO_ORG_ID',
            'presenter_zuid' => 'ZOHO_PRESENTER_ZUID',
            'workdrive_folder_id' => 'ZOHO_WORKDRIVE_FOLDER_ID',
            'calendar_uid' => 'ZOHO_CALENDAR_UID',
        ];

        foreach ($map as $statusKey => $envKey) {
            $value = trim((string) ($status[$statusKey] ?? ''));
            if ($value === '') {
                continue;
            }
            $this->writeEnv($envKey, $value);
            config(['zoho.' . $statusKey => $value]);
        }
    }

    protected function writeEnv(string $key, string $value): void
    {
        $path = base_path('.env');
        $contents = File::exists($path) ? File::get($path) : '';
        $line = $key . '=' . $value;
        if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $contents)) {
            $contents = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $contents);
        } else {
            $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
        }
        File::put($path, $contents);
    }
}
