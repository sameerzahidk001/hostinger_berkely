<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('study_material_folders')) {
            return;
        }

        if (Schema::hasColumn('study_material_folders', 'validity_months')) {
            DB::statement('ALTER TABLE study_material_folders MODIFY validity_months TINYINT UNSIGNED NULL');
        }

        if (! Schema::hasTable('study_material_folder_packages')) {
            Schema::create('study_material_folder_packages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('folder_id')->constrained('study_material_folders')->cascadeOnDelete();
                $table->unsignedBigInteger('course_fee_id');
                $table->timestamps();

                $table->foreign('course_fee_id')->references('id')->on('course_fees')->cascadeOnDelete();
                $table->unique(['folder_id', 'course_fee_id'], 'sm_folder_package_unique');
            });
        }

        if (Schema::hasColumn('study_material_folders', 'fee_package_id')) {
            $rows = DB::table('study_material_folders')
                ->whereNotNull('fee_package_id')
                ->get(['id', 'fee_package_id']);

            foreach ($rows as $row) {
                $exists = DB::table('study_material_folder_packages')
                    ->where('folder_id', $row->id)
                    ->where('course_fee_id', $row->fee_package_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('study_material_folder_packages')->insert([
                    'folder_id' => $row->id,
                    'course_fee_id' => $row->fee_package_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('study_material_folder_packages');

        if (Schema::hasTable('study_material_folders') && Schema::hasColumn('study_material_folders', 'validity_months')) {
            DB::statement('ALTER TABLE study_material_folders MODIFY validity_months TINYINT UNSIGNED NOT NULL DEFAULT 1');
        }
    }
};
