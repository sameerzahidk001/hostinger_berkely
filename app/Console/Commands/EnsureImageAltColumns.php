<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnsureImageAltColumns extends Command
{
    protected $signature = 'berkely:ensure-image-alt-columns';

    protected $description = 'Ensure image alt columns exist on courses and course_dynamic_labels';

    public function handle(): int
    {
        if (ensure_image_alt_columns_exist()) {
            $this->info('Image alt columns are ready.');

            return self::SUCCESS;
        }

        $this->error('Could not add image alt columns. Run database/sql/add-image-alt-columns.sql in phpMyAdmin.');

        return self::FAILURE;
    }
}
