<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnsureImageAltColumns extends Command
{
    protected $signature = 'berkely:ensure-image-alt-columns';

    protected $description = 'Ensure image alt columns exist on courses and course_dynamic_labels';

    public function handle(): int
    {
        $added = [];

        if (Schema::hasTable('courses') && ! Schema::hasColumn('courses', 'image_alts')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->json('image_alts')->nullable();
            });
            $added[] = 'courses.image_alts';
        }

        if (Schema::hasTable('course_dynamic_labels') && ! Schema::hasColumn('course_dynamic_labels', 'image_alts')) {
            Schema::table('course_dynamic_labels', function (Blueprint $table) {
                $table->json('image_alts')->nullable();
            });
            $added[] = 'course_dynamic_labels.image_alts';
        }

        if ($added === []) {
            $this->info('Image alt columns already exist.');

            return self::SUCCESS;
        }

        $this->info('Added image alt columns: ' . implode(', ', $added));

        return self::SUCCESS;
    }
}
