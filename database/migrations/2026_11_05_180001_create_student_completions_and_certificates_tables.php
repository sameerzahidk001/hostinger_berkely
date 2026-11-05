<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_completions')) {
            Schema::create('student_completions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('course_id')->nullable()->index();
                $table->unsignedBigInteger('batch_id')->nullable()->index();
                $table->string('status', 40)->default('completed')->index();
                $table->timestamp('completed_at')->nullable()->index();
                $table->unsignedBigInteger('marked_by')->nullable()->index();
                $table->string('marked_by_name', 255)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lms_certificates')) {
            Schema::create('lms_certificates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('completion_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('course_id')->nullable()->index();
                $table->string('certificate_code', 64)->unique();
                $table->string('status', 40)->default('issued')->index();
                $table->timestamp('issued_at')->nullable()->index();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamp('emailed_at')->nullable();
                $table->string('student_name', 255)->nullable();
                $table->string('course_title', 500)->nullable();
                $table->date('completion_date')->nullable();
                $table->string('pdf_path', 500)->nullable();
                $table->string('qr_payload', 500)->nullable();
                $table->unsignedBigInteger('issued_by')->nullable();
                $table->string('issued_by_name', 255)->nullable();
                $table->text('revoke_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Additive tables retained for live safety.
    }
};
