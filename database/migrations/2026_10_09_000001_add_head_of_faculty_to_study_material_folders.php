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

        if (! Schema::hasColumn('study_material_folders', 'head_of_faculty_id')) {
            Schema::table('study_material_folders', function (Blueprint $table) {
                $table->unsignedBigInteger('head_of_faculty_id')->nullable()->after('course_id');
                $table->index('head_of_faculty_id');
            });
        }

        // Backfill from course roster first instructor when that person already has folder access.
        if (! Schema::hasTable('study_material_instructor_access')) {
            return;
        }

        $folders = DB::table('study_material_folders')
            ->whereNull('head_of_faculty_id')
            ->whereNotNull('course_id')
            ->get(['id', 'course_id']);

        foreach ($folders as $folder) {
            $courseInstructor = DB::table('courses')->where('id', $folder->course_id)->value('instructor_id');
            $ids = [];
            if (is_string($courseInstructor) && $courseInstructor !== '') {
                $decoded = json_decode($courseInstructor, true);
                if (is_array($decoded)) {
                    $ids = $decoded;
                } elseif (is_numeric($courseInstructor)) {
                    $ids = [(int) $courseInstructor];
                }
            } elseif (is_numeric($courseInstructor)) {
                $ids = [(int) $courseInstructor];
            }

            $hofId = (int) ($ids[0] ?? 0);
            if ($hofId <= 0) {
                continue;
            }

            $hasAccess = DB::table('study_material_instructor_access')
                ->where('folder_id', $folder->id)
                ->where('instructor_id', $hofId)
                ->exists();

            if ($hasAccess) {
                DB::table('study_material_folders')
                    ->where('id', $folder->id)
                    ->update(['head_of_faculty_id' => $hofId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('study_material_folders') && Schema::hasColumn('study_material_folders', 'head_of_faculty_id')) {
            Schema::table('study_material_folders', function (Blueprint $table) {
                $table->dropIndex(['head_of_faculty_id']);
                $table->dropColumn('head_of_faculty_id');
            });
        }
    }
};
