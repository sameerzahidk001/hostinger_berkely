<?php

namespace App\Console\Commands;

use App\Services\ZohoCrmOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ZohoCrmConnectCommand extends Command
{
    protected $signature = 'zoho:crm-connect
                            {--code= : Self Client grant code from api-console.zoho.com}
                            {--client-id= : Zoho CRM API Console client ID}
                            {--client-secret= : Zoho CRM API Console client secret}
                            {--enable : Set ZOHO_CRM_ENABLED=true}';

    protected $description = 'Connect Zoho CRM OAuth for website invoice and receipt sync';

    public const SCOPES = 'ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.users.READ';

    public function handle(ZohoCrmOAuthService $oauth): int
    {
        if ($this->option('client-id')) {
            $this->writeEnv('ZOHO_CRM_CLIENT_ID', (string) $this->option('client-id'));
            config(['zoho_crm.client_id' => $this->option('client-id')]);
        }
        if ($this->option('client-secret')) {
            $this->writeEnv('ZOHO_CRM_CLIENT_SECRET', (string) $this->option('client-secret'));
            config(['zoho_crm.client_secret' => $this->option('client-secret')]);
        }
        if ($this->option('enable')) {
            $this->writeEnv('ZOHO_CRM_ENABLED', 'true');
            config(['zoho_crm.enabled' => true]);
        }

        if ($this->option('code')) {
            if (! filled(config('zoho_crm.client_id')) || ! filled(config('zoho_crm.client_secret'))) {
                $this->error('Pass --client-id and --client-secret (or set them in .env first).');

                return self::FAILURE;
            }

            try {
                $tokens = $oauth->exchangeGrantCode((string) $this->option('code'));
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }

            $this->writeEnv('ZOHO_CRM_REFRESH_TOKEN', $tokens['refresh_token']);
            config(['zoho_crm.refresh_token' => $tokens['refresh_token']]);
            $this->info('CRM refresh token saved to .env');
        }

        $status = $oauth->connectionStatus();
        $this->line('CRM OAuth configured: ' . ($status['configured'] ? 'yes' : 'no'));
        $this->line('CRM sync enabled: ' . ($status['enabled'] ? 'yes' : 'no'));
        if ($status['connected_email']) {
            $this->info('CRM API OK as ' . $status['connected_email']);
        }
        if ($status['error']) {
            $this->warn($status['error']);
        }

        if (! $status['configured']) {
            $this->newLine();
            $this->line('One-time CRM OAuth setup:');
            $this->line('1. Open https://api-console.zoho.com → Self Client (separate from Meeting app)');
            $this->line('2. Generate a code with this scope:');
            $this->line('   ' . self::SCOPES);
            $this->line('3. php artisan zoho:crm-connect --client-id=... --client-secret=... --code=GRANT_CODE --enable');
            $this->line('4. Create CRM custom fields: Website Invoice ID, Website Receipt ID, Installment No, Balance Due, Package Name, Gateway');
        }

        return $status['configured'] && empty($status['error']) ? self::SUCCESS : self::FAILURE;
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
