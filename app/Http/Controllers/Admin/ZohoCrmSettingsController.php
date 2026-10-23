<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ZohoCrmOAuthService;
use App\Services\ZohoCrmSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ZohoCrmSettingsController extends Controller
{
    public function show(ZohoCrmOAuthService $oauth, ZohoCrmSyncService $sync)
    {
        $sync->ensureSchema();
        $status = $oauth->connectionStatus();

        $pending = 0;
        $failed = 0;
        $recentFailures = collect();
        if (Schema::hasColumn('payments', 'zoho_crm_sync_status')) {
            $pending = \App\Models\Payment::where(function ($q) {
                $q->whereNull('zoho_crm_sync_status')->orWhere('zoho_crm_sync_status', 'pending');
            })->count();
            $failed = \App\Models\Payment::where('zoho_crm_sync_status', 'failed')->count();
            if (Schema::hasColumn('payments', 'zoho_crm_last_error')) {
                $recentFailures = \App\Models\Payment::query()
                    ->where('zoho_crm_sync_status', 'failed')
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get(['id', 'zoho_crm_last_error', 'zoho_crm_synced_at']);
            }
        }

        return view('admin.zoho-crm.settings', [
            'status' => $status,
            'pending' => $pending,
            'failed' => $failed,
            'recentFailures' => $recentFailures,
            'scopes' => \App\Console\Commands\ZohoCrmConnectCommand::SCOPES,
            'values' => [
                'ZOHO_CRM_ENABLED' => config('zoho_crm.enabled') ? '1' : '0',
                'ZOHO_CRM_CLIENT_ID' => config('zoho_crm.client_id'),
                'ZOHO_CRM_CLIENT_SECRET' => config('zoho_crm.client_secret'),
                'ZOHO_CRM_REFRESH_TOKEN' => config('zoho_crm.refresh_token'),
                'ZOHO_CRM_API_BASE' => config('zoho_crm.api_base'),
                'ZOHO_CRM_MODULE_INVOICE' => config('zoho_crm.modules.invoice'),
                'ZOHO_CRM_MODULE_RECEIPT' => config('zoho_crm.modules.receipt'),
                'ZOHO_CRM_MODULE_DEAL' => config('zoho_crm.modules.deal'),
            ],
        ]);
    }

    public function save(Request $request, ZohoCrmOAuthService $oauth)
    {
        $data = $request->validate([
            'ZOHO_CRM_ENABLED' => 'nullable|in:0,1',
            'ZOHO_CRM_CLIENT_ID' => 'nullable|string',
            'ZOHO_CRM_CLIENT_SECRET' => 'nullable|string',
            'ZOHO_CRM_REFRESH_TOKEN' => 'nullable|string',
            'ZOHO_CRM_API_BASE' => 'nullable|string',
            'ZOHO_CRM_MODULE_INVOICE' => 'nullable|string',
            'ZOHO_CRM_MODULE_RECEIPT' => 'nullable|string',
            'ZOHO_CRM_MODULE_DEAL' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $this->writeEnv($key, trim((string) $value));
        }

        $enabled = (string) ($data['ZOHO_CRM_ENABLED'] ?? '0') === '1' ? 'true' : 'false';
        $this->writeEnv('ZOHO_CRM_ENABLED', $enabled);
        config(['zoho_crm.enabled' => $enabled === 'true']);

        if (! empty($data['ZOHO_CRM_CLIENT_ID'])) {
            config(['zoho_crm.client_id' => $data['ZOHO_CRM_CLIENT_ID']]);
        }
        if (! empty($data['ZOHO_CRM_CLIENT_SECRET'])) {
            config(['zoho_crm.client_secret' => $data['ZOHO_CRM_CLIENT_SECRET']]);
        }
        if (! empty($data['ZOHO_CRM_REFRESH_TOKEN'])) {
            config(['zoho_crm.refresh_token' => $data['ZOHO_CRM_REFRESH_TOKEN']]);
        }
        if (! empty($data['ZOHO_CRM_API_BASE'])) {
            config(['zoho_crm.api_base' => $data['ZOHO_CRM_API_BASE']]);
        }
        if (! empty($data['ZOHO_CRM_MODULE_INVOICE'])) {
            config(['zoho_crm.modules.invoice' => $data['ZOHO_CRM_MODULE_INVOICE']]);
        }
        if (! empty($data['ZOHO_CRM_MODULE_RECEIPT'])) {
            config(['zoho_crm.modules.receipt' => $data['ZOHO_CRM_MODULE_RECEIPT']]);
        }
        if (! empty($data['ZOHO_CRM_MODULE_DEAL'])) {
            config(['zoho_crm.modules.deal' => $data['ZOHO_CRM_MODULE_DEAL']]);
        }

        $oauth->persistRuntime([
            'enabled' => $enabled === 'true',
            'client_id' => $data['ZOHO_CRM_CLIENT_ID'] ?? null,
            'client_secret' => $data['ZOHO_CRM_CLIENT_SECRET'] ?? null,
            'refresh_token' => $data['ZOHO_CRM_REFRESH_TOKEN'] ?? null,
            'api_base' => $data['ZOHO_CRM_API_BASE'] ?? null,
            'module_invoice' => $data['ZOHO_CRM_MODULE_INVOICE'] ?? null,
            'module_receipt' => $data['ZOHO_CRM_MODULE_RECEIPT'] ?? null,
            'module_deal' => $data['ZOHO_CRM_MODULE_DEAL'] ?? null,
        ]);

        $oauth->clearTokenCache();

        try {
            Artisan::call('config:clear');
        } catch (\Throwable $e) {
            // Hostinger may lock config cache
        }

        $status = $oauth->connectionStatus();
        if (! empty($status['error']) || empty($status['configured'])) {
            return redirect()
                ->route('admin.zoho-crm.settings')
                ->with('fail', 'Saved, but Zoho CRM check failed: ' . ($status['error'] ?: 'not configured'));
        }

        return redirect()
            ->route('admin.zoho-crm.settings')
            ->with('success', 'Zoho CRM connected' . ($status['connected_email'] ? ' as ' . $status['connected_email'] : '') . '.');
    }

    public function backfill(Request $request, ZohoCrmSyncService $sync, ZohoCrmOAuthService $oauth)
    {
        if ($blocked = $oauth->blockedReason()) {
            return redirect()->route('admin.zoho-crm.settings')->with('fail', $blocked);
        }

        $done = $sync->backfill($request->input('since'), (int) $request->input('limit', 100));

        return redirect()
            ->route('admin.zoho-crm.settings')
            ->with($done > 0 ? 'success' : 'fail', $done > 0
                ? 'Backfilled ' . $done . ' invoice(s) to Zoho CRM.'
                : 'No invoices were synced. Check the CRM error on each invoice, or confirm custom fields and module API names.');
    }

    public function retry(ZohoCrmSyncService $sync, ZohoCrmOAuthService $oauth)
    {
        if ($blocked = $oauth->blockedReason()) {
            return redirect()->route('admin.zoho-crm.settings')->with('fail', $blocked);
        }

        $done = $sync->retryFailed(50);

        return redirect()
            ->route('admin.zoho-crm.settings')
            ->with($done > 0 ? 'success' : 'fail', $done > 0
                ? 'Retried ' . $done . ' CRM sync(s).'
                : 'No records were synced. Open an invoice and use Push to Zoho CRM to see the error.');
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
