<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_schedules') && ! Schema::hasColumn('class_schedules', 'head_of_faculty_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('head_of_faculty_id')->nullable()->after('instructor_id');
                $table->index('head_of_faculty_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_schedules') && Schema::hasColumn('class_schedules', 'head_of_faculty_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropIndex(['head_of_faculty_id']);
                $table->dropColumn('head_of_faculty_id');
            });
        }
    }
};
