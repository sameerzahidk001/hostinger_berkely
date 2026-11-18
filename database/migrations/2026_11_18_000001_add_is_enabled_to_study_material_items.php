<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('study_material_items') || Schema::hasColumn('study_material_items', 'is_enabled')) {
            return;
        }

        Schema::table('study_material_items', function (Blueprint $table) {
            $table->boolean('is_enabled')->default(true)->nullable()->after('allow_download');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('study_material_items') || ! Schema::hasColumn('study_material_items', 'is_enabled')) {
            return;
        }

        Schema::table('study_material_items', function (Blueprint $table) {
            $table->dropColumn('is_enabled');
        });
    }
};
