<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('study_material_items') || Schema::hasColumn('study_material_items', 'icon_type')) {
            return;
        }

        Schema::table('study_material_items', function (Blueprint $table) {
            $table->string('icon_type', 32)->nullable()->after('mime');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('study_material_items') || ! Schema::hasColumn('study_material_items', 'icon_type')) {
            return;
        }

        Schema::table('study_material_items', function (Blueprint $table) {
            $table->dropColumn('icon_type');
        });
    }
};
