<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_schedules') && ! Schema::hasColumn('class_schedules', 'duration_minutes')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->unsignedSmallInteger('duration_minutes')->default(60)->after('scheduled_at');
            });
        }

        if (Schema::hasTable('site_settings') && ! Schema::hasColumn('site_settings', 'zoho_calendar_embed_url')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->text('zoho_calendar_embed_url')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_schedules') && Schema::hasColumn('class_schedules', 'duration_minutes')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn('duration_minutes');
            });
        }

        if (Schema::hasTable('site_settings') && Schema::hasColumn('site_settings', 'zoho_calendar_embed_url')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->dropColumn('zoho_calendar_embed_url');
            });
        }
    }
};
