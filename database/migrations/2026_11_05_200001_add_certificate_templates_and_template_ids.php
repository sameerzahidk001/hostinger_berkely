<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_templates')) {
            Schema::create('certificate_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('eyebrow', 255)->nullable();
                $table->string('title', 255)->nullable();
                $table->string('school_name', 255)->nullable();
                $table->string('intro_line', 500)->nullable();
                $table->string('completion_line', 500)->nullable();
                $table->string('orientation', 20)->default('landscape');
                $table->string('primary_color', 20)->nullable();
                $table->string('accent_color', 20)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_default')->default(false)->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('student_completions') && ! Schema::hasColumn('student_completions', 'template_id')) {
            Schema::table('student_completions', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->index()->after('batch_id');
            });
        }

        if (Schema::hasTable('lms_certificates') && ! Schema::hasColumn('lms_certificates', 'template_id')) {
            Schema::table('lms_certificates', function (Blueprint $table) {
                $table->unsignedBigInteger('template_id')->nullable()->index()->after('course_id');
            });
        }
    }

    public function down(): void
    {
        // Additive columns retained for live safety.
    }
};
