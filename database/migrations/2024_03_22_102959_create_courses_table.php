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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->string('slug')->required();
            $table->string('type')->default('course');
            //add courseCode column
            $table->bigInteger('subject_id')->unsigned()->index()->nullable();
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            $table->longText('description')->nullable();
            $table->mediumText('short_description')->nullable();
            $table->longText('awarded_by')->nullable();
            $table->decimal('price', $precision = 8, $scale = 2)->nullable();
            $table->string('currency');
            $table->string('course_brochure')->nullable();
            $table->date('starting_date')->nullable();

            $table->integer('course_duration')->nullable();
            $table->string('duration_unit');
            $table->string('duration_mode');

            $table->integer('no_of_exams')->nullable();
            $table->string('exams_mode');

            $table->json('learning_methodology')->nullable();

            $table->integer('no_of_lectures')->nullable();
            $table->integer('no_of_practice_mocks')->nullable();

            $table->string('thumbnail');
            $table->string('post_image')->nullable();
            $table->string('video')->nullable();
            $table->boolean('free_application')->default(1);
            $table->boolean('is_free')->default(0);
            $table->boolean('docs_required')->default(0);
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
        Schema::dropIfExists('courses');
    }
};
