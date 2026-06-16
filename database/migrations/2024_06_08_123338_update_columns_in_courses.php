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

            $table->string('currency')->nullable()->change();
            $table->string('duration_unit')->nullable()->change();
            $table->string('duration_mode')->nullable()->change();
            $table->string('exams_mode')->nullable()->change();
            $table->string('thumbnail')->nullable()->change();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {

            $table->string('currency')->nullable(false)->change();
            $table->string('duration_unit')->nullable(false)->change();
            $table->string('duration_mode')->nullable(false)->change();
            $table->string('exams_mode')->nullable(false)->change();
            $table->string('thumbnail')->nullable(false)->change();
            
        });
    }
};
