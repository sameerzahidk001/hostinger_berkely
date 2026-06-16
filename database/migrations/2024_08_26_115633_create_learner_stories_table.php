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
        Schema::create('learner_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subjects_id')->constrained('subjects')->onDelete('cascade');
            $table->string('name')->required();
            $table->string('image')->nullable();
            $table->longText('details')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_stories');
    }
};
