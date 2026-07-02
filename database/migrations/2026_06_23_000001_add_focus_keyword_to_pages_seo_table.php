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

        if (! Schema::hasColumn('pages_s_e_o_s', 'focus_keyword')) {
            Schema::table('pages_s_e_o_s', function (Blueprint $table) {
                $table->string('focus_keyword', 80)->nullable()->after('meta_description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pages_s_e_o_s') && Schema::hasColumn('pages_s_e_o_s', 'focus_keyword')) {
            Schema::table('pages_s_e_o_s', function (Blueprint $table) {
                $table->dropColumn('focus_keyword');
            });
        }
    }
};
