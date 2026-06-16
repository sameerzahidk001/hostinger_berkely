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
        Schema::create('course_structure_sub_headings', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('course_structures_id')->unsigned()->index()->nullable();
            $table->foreign('course_structures_id')->references('id')->on('course_structures')->onDelete('cascade');
            $table->mediumText('sub_heading')->required();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_structure_sub_headings');
    }
};
