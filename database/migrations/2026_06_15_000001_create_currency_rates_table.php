<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('currency_id');
            $table->decimal('rate_to_aed', 12, 4)->default(1);
            $table->timestamps();
            $table->unique('currency_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
