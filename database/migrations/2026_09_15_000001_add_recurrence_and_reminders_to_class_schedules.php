<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        Schema::table('class_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('class_schedules', 'recurrence_type')) {
                $table->string('recurrence_type', 20)->default('none')->after('duration_minutes');
            }
            if (! Schema::hasColumn('class_schedules', 'recurrence_days')) {
                $table->json('recurrence_days')->nullable()->after('recurrence_type');
            }
            if (! Schema::hasColumn('class_schedules', 'recurrence_count')) {
                $table->unsignedSmallInteger('recurrence_count')->nullable()->after('recurrence_days');
            }
            if (! Schema::hasColumn('class_schedules', 'recurrence_until')) {
                $table->date('recurrence_until')->nullable()->after('recurrence_count');
            }
            if (! Schema::hasColumn('class_schedules', 'reminders')) {
                $table->json('reminders')->nullable()->after('recurrence_until');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('class_schedules')) {
            return;
        }

        Schema::table('class_schedules', function (Blueprint $table) {
            foreach (['recurrence_type', 'recurrence_days', 'recurrence_count', 'recurrence_until', 'reminders'] as $column) {
                if (Schema::hasColumn('class_schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
