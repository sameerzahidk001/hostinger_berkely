<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('study_material_items') || ! Schema::hasColumn('study_material_items', 'external_url')) {
            return;
        }

        DB::statement('ALTER TABLE study_material_items MODIFY external_url TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('study_material_items') || ! Schema::hasColumn('study_material_items', 'external_url')) {
            return;
        }

        DB::statement('ALTER TABLE study_material_items MODIFY external_url VARCHAR(255) NULL');
    }
};
