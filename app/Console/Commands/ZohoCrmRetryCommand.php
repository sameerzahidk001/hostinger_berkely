<?php

namespace App\Console\Commands;

use App\Services\ZohoCrmSyncService;
use Illuminate\Console\Command;

class ZohoCrmRetryCommand extends Command
{
    protected $signature = 'zoho:crm-retry {--limit=50 : Max records to retry}';

    protected $description = 'Retry failed Zoho CRM invoice/receipt pushes';

    public function handle(ZohoCrmSyncService $sync): int
    {
        $done = $sync->retryFailed((int) $this->option('limit'));
        $this->info('Retried / synced ' . $done . ' CRM record(s).');

        return self::SUCCESS;
    }
}
