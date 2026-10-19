<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LmsAssessmentSchema
{
    public static function ensure(): bool
    {
        static $done = false;
        if ($done) {
            return Schema::hasTable('lms_assessments');
        }

        try {
            self::ensureQuestions();
            self::ensureAssessments();
            self::ensureAssessmentQuestions();
            self::ensureAssignments();
            self::ensureAttempts();
            self::ensureAnswers();
            self::ensureAdditiveColumns();
        } catch (\Throwable $e) {
            // Hostinger may deny CREATE/ALTER; pages must still open with existing tables.
        }

        $done = true;

        return Schema::hasTable('lms_assessments') && Schema::hasTable('lms_questions');
    }

    /** Nullable columns for assign/marking flow — safe on GitHub pull alone. */
    protected static function ensureAdditiveColumns(): void
    {
        if (Schema::hasTable('lms_assessments') && ! Schema::hasColumn('lms_assessments', 'target_role')) {
            Schema::table('lms_assessments', function (Blueprint $table) {
                $table->string('target_role', 40)->nullable()->index();
            });
        }
        if (Schema::hasTable('lms_questions') && ! Schema::hasColumn('lms_questions', 'target_role')) {
            Schema::table('lms_questions', function (Blueprint $table) {
                $table->string('target_role', 40)->nullable()->index();
            });
        }
        if (Schema::hasTable('lms_assessment_assignments') && ! Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')) {
            Schema::table('lms_assessment_assignments', function (Blueprint $table) {
                $table->unsignedBigInteger('marker_user_id')->nullable()->index();
            });
        }
        if (Schema::hasTable('lms_assessment_assignments') && ! Schema::hasColumn('lms_assessment_assignments', 'timezone')) {
            Schema::table('lms_assessment_assignments', function (Blueprint $table) {
                $table->string('timezone', 64)->nullable()->after('available_until');
            });
        }
        if (Schema::hasTable('lms_assessment_assignments') && ! Schema::hasColumn('lms_assessment_assignments', 'sitting_kind')) {
            Schema::table('lms_assessment_assignments', function (Blueprint $table) {
                $table->string('sitting_kind', 16)->nullable()->index();
            });
        }
        if (Schema::hasTable('lms_assessment_attempts') && ! Schema::hasColumn('lms_assessment_attempts', 'assignment_id')) {
            Schema::table('lms_assessment_attempts', function (Blueprint $table) {
                $table->unsignedBigInteger('assignment_id')->nullable()->index();
            });
        }
        if (Schema::hasTable('lms_questions') && ! Schema::hasColumn('lms_questions', 'time_limit_minutes')) {
            Schema::table('lms_questions', function (Blueprint $table) {
                $table->unsignedInteger('time_limit_minutes')->nullable();
            });
        }
        if (Schema::hasTable('lms_assessments') && ! Schema::hasColumn('lms_assessments', 'reference_files_json')) {
            Schema::table('lms_assessments', function (Blueprint $table) {
                $table->json('reference_files_json')->nullable();
            });
        }
        if (Schema::hasTable('lms_assessments') && ! Schema::hasColumn('lms_assessments', 'preseen_files_json')) {
            Schema::table('lms_assessments', function (Blueprint $table) {
                $table->json('preseen_files_json')->nullable();
            });
        }
    }

    protected static function ensureQuestions(): void
    {
        if (Schema::hasTable('lms_questions')) {
            return;
        }

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

    protected static function ensureAssessments(): void
    {
        if (Schema::hasTable('lms_assessments')) {
            return;
        }

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

    protected static function ensureAssessmentQuestions(): void
    {
        if (Schema::hasTable('lms_assessment_questions')) {
            return;
        }

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

    protected static function ensureAssignments(): void
    {
        if (Schema::hasTable('lms_assessment_assignments')) {
            return;
        }

        Schema::create('lms_assessment_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id')->index();
            $table->unsignedBigInteger('class_batch_id')->nullable()->index();
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->string('timezone', 64)->nullable();
            $table->unsignedBigInteger('assigned_by_admin_id')->nullable();
            $table->unsignedBigInteger('assigned_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    protected static function ensureAttempts(): void
    {
        if (Schema::hasTable('lms_assessment_attempts')) {
            return;
        }

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

    protected static function ensureAnswers(): void
    {
        if (Schema::hasTable('lms_attempt_answers')) {
            return;
        }

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
