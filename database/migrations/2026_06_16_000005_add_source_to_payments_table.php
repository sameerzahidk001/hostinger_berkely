<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('payments', 'source')) {
                $blueprint->string('source', 20)->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $blueprint) {
            if (Schema::hasColumn('payments', 'source')) {
                $blueprint->dropColumn('source');
            }
        });
    }
};
