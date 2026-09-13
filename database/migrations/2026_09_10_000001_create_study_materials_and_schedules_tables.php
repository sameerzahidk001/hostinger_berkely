<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_material_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->unsignedBigInteger('fee_package_id')->nullable();
            $table->unsignedTinyInteger('validity_months')->default(1);
            $table->enum('status', ['disabled', 'active'])->default('disabled');
            $table->string('owner_type', 20)->default('admin'); // admin|instructor
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('fee_package_id')->references('id')->on('course_fees')->nullOnDelete();
            $table->index(['status', 'course_id']);
        });

        Schema::create('study_material_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('study_material_folders')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->enum('type', ['folder', 'file'])->default('file');
            $table->string('name');
            $table->string('disk_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('study_material_items')->cascadeOnDelete();
            $table->index(['folder_id', 'parent_id']);
        });

        Schema::create('study_material_instructor_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('study_material_folders')->cascadeOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['disabled', 'active'])->default('disabled');
            $table->date('issued_at')->nullable();
            $table->date('access_till')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['folder_id', 'instructor_id'], 'sm_instructor_folder_unique');
        });

        Schema::create('study_material_student_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('study_material_folders')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['disabled', 'active'])->default('disabled');
            $table->date('issued_at')->nullable();
            $table->date('access_till')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['folder_id', 'student_id'], 'sm_student_folder_unique');
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('batch_name');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('zoho_link')->nullable();
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('class_schedule_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['class_schedule_id', 'student_id'], 'class_schedule_student_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedule_student');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('study_material_student_access');
        Schema::dropIfExists('study_material_instructor_access');
        Schema::dropIfExists('study_material_items');
        Schema::dropIfExists('study_material_folders');
    }
};
