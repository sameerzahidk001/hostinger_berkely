<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('class_batches')) {
            Schema::create('class_batches', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                $table->foreignId('head_of_faculty_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('active'); // active|disabled
                $table->unsignedBigInteger('created_by_admin_id')->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('class_batch_instructor')) {
            Schema::create('class_batch_instructor', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_batch_id')->constrained('class_batches')->cascadeOnDelete();
                $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['class_batch_id', 'instructor_id']);
            });
        }

        if (! Schema::hasTable('class_batch_student')) {
            Schema::create('class_batch_student', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_batch_id')->constrained('class_batches')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['class_batch_id', 'student_id']);
            });
        }

        if (Schema::hasTable('class_schedules') && ! Schema::hasColumn('class_schedules', 'batch_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_id')->nullable()->after('batch_name');
                $table->index('batch_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_schedules') && Schema::hasColumn('class_schedules', 'batch_id')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                $table->dropColumn('batch_id');
            });
        }
        Schema::dropIfExists('class_batch_student');
        Schema::dropIfExists('class_batch_instructor');
        Schema::dropIfExists('class_batches');
    }
};
