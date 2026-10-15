<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lms_questions')) {
            Schema::create('lms_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('course_id')->nullable()->index();
                $table->string('type', 20)->default('mcq')->index();
                $table->string('topic')->nullable()->index();
                $table->text('stem');
                $table->text('explanation')->nullable();
                $table->json('options_json')->nullable();
                $table->decimal('max_marks', 8, 2)->default(1);
                $table->string('status', 20)->default('active')->index();
                $table->unsignedBigInteger('created_by_admin_id')->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('lms_assessments')) {
            Schema::create('lms_assessments', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('kind', 20)->default('practice')->index();
                $table->string('audience', 20)->default('student')->index();
                $table->unsignedBigInteger('course_id')->nullable()->index();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->decimal('pass_percent', 5, 2)->nullable();
                $table->unsignedInteger('max_attempts')->nullable();
                $table->boolean('shuffle_questions')->default(false);
                $table->string('results_mode', 30)->default('after_publish');
                $table->string('status', 20)->default('draft')->index();
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('created_by_admin_id')->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('lms_assessment_questions')) {
            Schema::create('lms_assessment_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assessment_id')->index();
                $table->unsignedBigInteger('question_id')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->decimal('marks_override', 8, 2)->nullable();
                $table->timestamps();
                $table->unique(['assessment_id', 'question_id'], 'lms_aq_unique');
            });
        }

        if (! Schema::hasTable('lms_assessment_assignments')) {
            Schema::create('lms_assessment_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assessment_id')->index();
                $table->unsignedBigInteger('class_batch_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->timestamp('available_from')->nullable();
                $table->timestamp('available_until')->nullable();
                $table->unsignedBigInteger('assigned_by_admin_id')->nullable();
                $table->unsignedBigInteger('assigned_by_user_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lms_assessment_attempts')) {
            Schema::create('lms_assessment_attempts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assessment_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('status', 20)->default('in_progress')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->decimal('score_raw', 10, 2)->nullable();
                $table->decimal('score_max', 10, 2)->nullable();
                $table->boolean('passed')->nullable();
                $table->text('grader_feedback')->nullable();
                $table->boolean('results_published')->default(false)->index();
                $table->timestamp('results_published_at')->nullable();
                $table->unsignedBigInteger('graded_by_admin_id')->nullable();
                $table->unsignedBigInteger('graded_by_user_id')->nullable();
                $table->timestamp('graded_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lms_attempt_answers')) {
            Schema::create('lms_attempt_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('attempt_id')->index();
                $table->unsignedBigInteger('question_id')->index();
                $table->json('answer_json')->nullable();
                $table->decimal('auto_score', 8, 2)->nullable();
                $table->decimal('manual_score', 8, 2)->nullable();
                $table->decimal('max_marks', 8, 2)->default(1);
                $table->text('feedback')->nullable();
                $table->boolean('is_correct')->nullable();
                $table->unsignedBigInteger('marked_by_admin_id')->nullable();
                $table->unsignedBigInteger('marked_by_user_id')->nullable();
                $table->timestamp('marked_at')->nullable();
                $table->timestamps();
                $table->unique(['attempt_id', 'question_id'], 'lms_aa_unique');
            });
        }
    }

    public function down(): void
    {
        // Additive schema retained for live safety.
    }
};
