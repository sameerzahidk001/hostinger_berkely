<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_assets')) {
            return;
        }

        if (! Schema::hasColumn('media_assets', 'description')) {
            Schema::table('media_assets', function (Blueprint $table) {
                $table->text('description')->nullable()->after('caption');
            });
        }
    }

    public function down(): void
    {
        // Additive column retained for live safety.
    }
};
