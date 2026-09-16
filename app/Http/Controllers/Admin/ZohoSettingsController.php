<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ZohoLmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ZohoSettingsController extends Controller
{
    public function show(ZohoLmsService $zoho)
    {
        $status = $zoho->connectionStatus();

        return view('admin.zoho.settings', [
            'status' => $status,
            'values' => [
                'ZOHO_ACCOUNT_EMAIL' => config('zoho.account_email', 'bdm@berkeleyme.com'),
                'ZOHO_CLIENT_ID' => config('zoho.client_id'),
                'ZOHO_CLIENT_SECRET' => config('zoho.client_secret'),
                'ZOHO_REFRESH_TOKEN' => config('zoho.refresh_token'),
                'ZOHO_ORG_ID' => config('zoho.org_id'),
                'ZOHO_PRESENTER_ZUID' => config('zoho.presenter_zuid'),
                'ZOHO_WORKDRIVE_FOLDER_ID' => config('zoho.workdrive_folder_id'),
                'ZOHO_CALENDAR_UID' => config('zoho.calendar_uid'),
                'ZOHO_TIMEZONE' => config('zoho.timezone', 'Asia/Dubai'),
            ],
        ]);
    }

    public function save(Request $request, ZohoLmsService $zoho)
    {
        $data = $request->validate([
            'ZOHO_ACCOUNT_EMAIL' => 'required|email',
            'ZOHO_CLIENT_ID' => 'required|string',
            'ZOHO_CLIENT_SECRET' => 'required|string',
            'ZOHO_REFRESH_TOKEN' => 'required|string',
            'ZOHO_ORG_ID' => 'nullable|string',
            'ZOHO_PRESENTER_ZUID' => 'nullable|string',
            'ZOHO_WORKDRIVE_FOLDER_ID' => 'nullable|string',
            'ZOHO_CALENDAR_UID' => 'nullable|string',
            'ZOHO_TIMEZONE' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $this->writeEnv($key, trim((string) $value));
            config(['zoho.' . $this->configKey($key) => trim((string) $value)]);
        }

        $this->writeEnv('ZOHO_ACCOUNTS_URL', 'https://accounts.zoho.com');
        $this->writeEnv('ZOHO_MEETING_URL', 'https://meeting.zoho.com');

        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // Hostinger may lock config cache; values are still in .env for next boot.
        }

        $status = $zoho->connectionStatus();
        if (! empty($status['error']) || empty($status['configured'])) {
            return redirect()
                ->route('admin.zoho.settings')
                ->with('fail', 'Saved, but Zoho API check failed: ' . ($status['error'] ?: 'not configured'));
        }

        return redirect()
            ->route('admin.zoho.settings')
            ->with('success', 'Zoho connected as ' . ($status['connected_email'] ?? $data['ZOHO_ACCOUNT_EMAIL']) . '. Class schedules will auto-create Meeting links.');
    }

    protected function configKey(string $envKey): string
    {
        return match ($envKey) {
            'ZOHO_ACCOUNT_EMAIL' => 'account_email',
            'ZOHO_CLIENT_ID' => 'client_id',
            'ZOHO_CLIENT_SECRET' => 'client_secret',
            'ZOHO_REFRESH_TOKEN' => 'refresh_token',
            'ZOHO_ORG_ID' => 'org_id',
            'ZOHO_PRESENTER_ZUID' => 'presenter_zuid',
            'ZOHO_WORKDRIVE_FOLDER_ID' => 'workdrive_folder_id',
            'ZOHO_CALENDAR_UID' => 'calendar_uid',
            'ZOHO_TIMEZONE' => 'timezone',
            default => strtolower(str_replace('ZOHO_', '', $envKey)),
        };
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
