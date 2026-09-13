<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('study_material_items') || Schema::hasColumn('study_material_items', 'allow_download')) {
            return;
        }

        Schema::table('study_material_items', function (Blueprint $table) {
            $table->boolean('allow_download')->default(true)->after('sort_order');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('study_material_items') || ! Schema::hasColumn('study_material_items', 'allow_download')) {
            return;
        }

        Schema::table('study_material_items', function (Blueprint $table) {
            $table->dropColumn('allow_download');
        });
    }
};
