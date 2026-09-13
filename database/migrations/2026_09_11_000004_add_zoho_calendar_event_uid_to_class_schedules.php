<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_schedules') && ! Schema::hasColumn('class_schedules', 'zoho_calendar_event_uid')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->string('zoho_calendar_event_uid')->nullable()->after('zoho_link');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_schedules') && Schema::hasColumn('class_schedules', 'zoho_calendar_event_uid')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn('zoho_calendar_event_uid');
            });
        }
    }
};
