<?php

namespace App\Console\Commands;

use App\Services\ZohoCrmSyncService;
use Illuminate\Console\Command;

class ZohoCrmBackfillCommand extends Command
{
    protected $signature = 'zoho:crm-backfill
                            {--since= : Only payments created on/after this date (YYYY-MM-DD)}
                            {--limit=200 : Max payments to push}';

    protected $description = 'One-time / batch backfill of existing invoices and receipts to Zoho CRM';

    public function handle(ZohoCrmSyncService $sync): int
    {
        $done = $sync->backfill($this->option('since') ?: null, (int) $this->option('limit'));
        $this->info('Backfilled ' . $done . ' payment(s) to Zoho CRM.');

        return self::SUCCESS;
    }
}
