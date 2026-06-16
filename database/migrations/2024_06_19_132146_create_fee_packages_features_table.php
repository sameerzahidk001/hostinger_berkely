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
        Schema::create('fee_packages_features', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('fee_packages_id')->unsigned()->index()->nullable();
            $table->foreign('fee_packages_id')->references('id')->on('course_fee_packages')->onDelete('cascade');
            $table->mediumText('heading')->nullable();
            $table->string('course_access')->nullable();
            $table->string('payment_option')->nullable();
            $table->json('pass_guarantee')->nullable();
            $table->json('exam_day_ready_features')->nullable();
            $table->json('unmatched_resources_and_tools')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_packages_features');
    }
};
