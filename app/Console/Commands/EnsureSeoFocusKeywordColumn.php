<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnsureSeoFocusKeywordColumn extends Command
{
    protected $signature = 'berkely:ensure-seo-focus-keyword-column';

    protected $description = 'Ensure focus_keyword column exists on pages_s_e_o_s';

    public function handle(): int
    {
        if (ensure_seo_focus_keyword_column_exists()) {
            $this->info('SEO focus_keyword column is ready.');

            return self::SUCCESS;
        }

        $this->error('Could not add focus_keyword column. Run database/sql/add-focus-keyword-column.sql in phpMyAdmin.');

        return self::FAILURE;
    }
}
