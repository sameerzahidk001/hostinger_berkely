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
        Schema::table('courses', function (Blueprint $table) {
            $table->longText('exam_dates')->nullable();
            $table->longText('exam_reg_deadline')->nullable();
            $table->longText('exam_passing_criteria')->nullable();
            $table->json('exam_location')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('exam_dates');
            $table->dropColumn('exam_reg_deadline');
            $table->dropColumn('exam_passing_criteria');
            $table->dropColumn('exam_location');
        });
    }
};
