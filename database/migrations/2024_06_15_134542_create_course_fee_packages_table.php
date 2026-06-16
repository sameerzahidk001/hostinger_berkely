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
        Schema::create('course_fee_packages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('courses_id')->unsigned()->index()->nullable();
            $table->foreign('courses_id')->references('id')->on('courses')->onDelete('cascade');
            // $table->mediumText('heading')->nullale();
            $table->string('package_name')->required();
            $table->decimal('price', $precision = 8, $scale = 2)->nullable();
            $table->integer('discount_percentage')->nullable();
            $table->decimal('discounted_price', $precision = 8, $scale = 2)->nullable();
            $table->boolean('is_recommended')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_fee_packages');
    }
};
