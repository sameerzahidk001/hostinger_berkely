<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses') && ! Schema::hasColumn('courses', 'image_text_2col_card')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->json('image_text_2col_card')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'image_text_2col_card')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('image_text_2col_card');
            });
        }
    }
};
