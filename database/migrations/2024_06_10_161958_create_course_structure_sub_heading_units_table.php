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
        Schema::create('course_structure_sub_heading_units', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sub_headings_id')->unsigned()->index()->nullable();
            $table->foreign('sub_headings_id')->references('id')->on('course_structure_sub_headings')->onDelete('cascade');
            $table->text('unit_title')->nullable();
            $table->string('unit_video')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_structure_sub_heading_units');
    }
};
