<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pages_s_e_o_s')) {
            return;
        }

        Schema::table('pages_s_e_o_s', function (Blueprint $blueprint) {
            if (!Schema::hasColumn('pages_s_e_o_s', 'created_by')) {
                $blueprint->unsignedBigInteger('created_by')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('pages_s_e_o_s', 'updated_by')) {
                $blueprint->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pages_s_e_o_s')) {
            return;
        }

        Schema::table('pages_s_e_o_s', function (Blueprint $blueprint) {
            if (Schema::hasColumn('pages_s_e_o_s', 'updated_by')) {
                $blueprint->dropColumn('updated_by');
            }
            if (Schema::hasColumn('pages_s_e_o_s', 'created_by')) {
                $blueprint->dropColumn('created_by');
            }
        });
    }
};
