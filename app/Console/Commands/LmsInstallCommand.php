<?php

namespace App\Console\Commands;

use Database\Seeders\StudyMaterialEmailTemplateSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class LmsInstallCommand extends Command
{
    protected $signature = 'lms:install';

    protected $description = 'Create LMS tables only (Study Materials + Class Schedules) and seed email templates. Does not alter existing app tables beyond optional Zoho calendar embed column.';

    public function handle(): int
    {
        $paths = [
            'database/migrations/2026_09_10_000001_create_study_materials_and_schedules_tables.php',
            'database/migrations/2026_09_11_000001_update_study_material_folders_packages_and_validity.php',
            'database/migrations/2026_09_11_000002_change_study_material_external_url_to_text.php',
            'database/migrations/2026_09_11_000003_add_zoho_calendar_fields_to_schedules.php',
            'database/migrations/2026_09_11_000004_add_zoho_calendar_event_uid_to_class_schedules.php',
            'database/migrations/2026_09_12_000001_add_allow_download_to_study_material_items.php',
            'database/migrations/2026_09_12_000002_add_code_to_study_material_folders.php',
            'database/migrations/2026_09_15_000001_add_recurrence_and_reminders_to_class_schedules.php',
        ];

        foreach ($paths as $path) {
            $this->info("Migrating {$path}");
            $code = Artisan::call('migrate', [
                '--path' => $path,
                '--force' => true,
            ]);
            $this->line(trim(Artisan::output()));
            if ($code !== 0) {
                $this->error("Migration failed: {$path}");

                return self::FAILURE;
            }
        }

        $this->info('Seeding study material email templates...');
        Artisan::call('db:seed', [
            '--class' => StudyMaterialEmailTemplateSeeder::class,
            '--force' => true,
        ]);
        $this->line(trim(Artisan::output()));

        $ok = Schema::hasTable('study_material_folders')
            && Schema::hasTable('study_material_items')
            && Schema::hasTable('study_material_student_access')
            && Schema::hasTable('study_material_instructor_access')
            && Schema::hasTable('class_schedules');

        if (! $ok) {
            $this->error('LMS tables are still missing after migrate. Check DB user permissions and foreign keys to courses/users/course_fees.');

            return self::FAILURE;
        }

        $this->info('LMS install complete. Study Materials and Class Schedule are ready.');

        return self::SUCCESS;
    }
}
