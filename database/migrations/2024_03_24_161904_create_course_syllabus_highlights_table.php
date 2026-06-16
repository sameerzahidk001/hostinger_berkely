<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_syllabus_highlights', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('course_syllabus_id')->unsigned()->index()->nullable();
            $table->foreign('course_syllabus_id')->references('id')->on('course_syllabus')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->mediumText('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('image')->nullable();
            $table->string('video')->nullable();
            $table->boolean('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_syllabus_highlights');
    }
};
