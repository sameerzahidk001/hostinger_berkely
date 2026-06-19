<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses') && ! Schema::hasColumn('courses', 'image_alts')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->json('image_alts')->nullable()->after('overview_img');
            });
        }

        if (Schema::hasTable('course_dynamic_labels') && ! Schema::hasColumn('course_dynamic_labels', 'image_alts')) {
            Schema::table('course_dynamic_labels', function (Blueprint $table) {
                $table->json('image_alts')->nullable();
            });
        }

        $tables = [
            'clients' => ['image_alt'],
            'learner_stories' => ['image_alt'],
            'subjects' => ['image_alt'],
            'homepage_sections' => ['image_alt'],
            'course_testimonials' => ['image_alt'],
            'pages_s_e_o_s' => ['thumbnail_alt'],
        ];

        foreach ($tables as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        $blueprint->string($column, 255)->nullable();
                    }
                }
            });
        }

        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (! Schema::hasColumn('schools', 'image_alt')) {
                    $table->string('image_alt', 255)->nullable();
                }
                if (! Schema::hasColumn('schools', 'icon_alt')) {
                    $table->string('icon_alt', 255)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('courses', 'image_alts')) {
            Schema::table('courses', fn (Blueprint $table) => $table->dropColumn('image_alts'));
        }

        if (Schema::hasColumn('course_dynamic_labels', 'image_alts')) {
            Schema::table('course_dynamic_labels', fn (Blueprint $table) => $table->dropColumn('image_alts'));
        }

        foreach (['clients', 'learner_stories', 'subjects', 'homepage_sections', 'course_testimonials'] as $table) {
            if (Schema::hasColumn($table, 'image_alt')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('image_alt'));
            }
        }

        if (Schema::hasColumn('pages_s_e_o_s', 'thumbnail_alt')) {
            Schema::table('pages_s_e_o_s', fn (Blueprint $table) => $table->dropColumn('thumbnail_alt'));
        }

        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (Schema::hasColumn('schools', 'image_alt')) {
                    $table->dropColumn('image_alt');
                }
                if (Schema::hasColumn('schools', 'icon_alt')) {
                    $table->dropColumn('icon_alt');
                }
            });
        }
    }
};
