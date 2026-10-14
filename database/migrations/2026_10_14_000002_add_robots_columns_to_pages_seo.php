<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages_s_e_o_s')) {
            return;
        }

        Schema::table('pages_s_e_o_s', function (Blueprint $table) {
            if (! Schema::hasColumn('pages_s_e_o_s', 'robots_index')) {
                $table->string('robots_index', 20)->default('index')->nullable();
            }
            if (! Schema::hasColumn('pages_s_e_o_s', 'robots_follow')) {
                $table->string('robots_follow', 20)->default('follow')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pages_s_e_o_s')) {
            return;
        }

        Schema::table('pages_s_e_o_s', function (Blueprint $table) {
            if (Schema::hasColumn('pages_s_e_o_s', 'robots_follow')) {
                $table->dropColumn('robots_follow');
            }
            if (Schema::hasColumn('pages_s_e_o_s', 'robots_index')) {
                $table->dropColumn('robots_index');
            }
        });
    }
};
