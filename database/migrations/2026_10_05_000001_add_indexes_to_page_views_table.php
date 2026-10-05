<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_views')) {
            return;
        }

        Schema::table('page_views', function (Blueprint $table) {
            try {
                $table->index('created_at');
            } catch (\Throwable $e) {
            }
            try {
                $table->index('updated_at');
            } catch (\Throwable $e) {
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('page_views')) {
            return;
        }

        Schema::table('page_views', function (Blueprint $table) {
            try {
                $table->dropIndex(['created_at']);
            } catch (\Throwable $e) {
            }
            try {
                $table->dropIndex(['updated_at']);
            } catch (\Throwable $e) {
            }
        });
    }
};
