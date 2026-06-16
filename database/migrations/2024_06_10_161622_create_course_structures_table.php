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
        Schema::create('course_structures', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('courses_id')->unsigned()->index()->nullable();
            $table->foreign('courses_id')->references('id')->on('courses')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->mediumText('heading')->required();
            $table->mediumText('exam_format')->nullable();
            $table->mediumText('exam_duration')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_structures');
    }
};
