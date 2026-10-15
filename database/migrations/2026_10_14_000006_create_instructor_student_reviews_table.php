<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('instructor_student_reviews')) {
            Schema::create('instructor_student_reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->unsignedBigInteger('instructor_id')->index();
                $table->unsignedBigInteger('class_batch_id')->nullable()->index();
                $table->unsignedBigInteger('study_material_folder_id')->nullable()->index();
                $table->string('scope_key', 64);
                $table->unsignedTinyInteger('stars');
                $table->text('review_text')->nullable();
                $table->boolean('is_published')->default(false)->index();
                $table->boolean('is_locked')->default(false);
                $table->boolean('hide_student_name')->default(false);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['student_id', 'instructor_id', 'scope_key'],
                    'isr_student_instructor_scope_unique'
                );
            });
        }

        if (Schema::hasTable('class_batches')) {
            Schema::table('class_batches', function (Blueprint $table) {
                if (! Schema::hasColumn('class_batches', 'rate_and_review_enabled')) {
                    $table->boolean('rate_and_review_enabled')->default(true);
                }
                if (! Schema::hasColumn('class_batches', 'rate_review_text_required')) {
                    $table->boolean('rate_review_text_required')->default(false);
                }
            });
        }

        if (Schema::hasTable('study_material_folders')) {
            Schema::table('study_material_folders', function (Blueprint $table) {
                if (! Schema::hasColumn('study_material_folders', 'rate_and_review_enabled')) {
                    $table->boolean('rate_and_review_enabled')->default(true);
                }
                if (! Schema::hasColumn('study_material_folders', 'rate_review_text_required')) {
                    $table->boolean('rate_review_text_required')->default(false);
                }
            });
        }
    }

    public function down(): void
    {
        // Additive schema retained for live safety.
    }
};
