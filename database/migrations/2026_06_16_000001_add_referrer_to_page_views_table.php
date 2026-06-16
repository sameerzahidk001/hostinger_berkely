<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_views') && !Schema::hasColumn('page_views', 'referrer')) {
            Schema::table('page_views', function (Blueprint $table) {
                $table->string('referrer', 500)->nullable()->after('url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('page_views') && Schema::hasColumn('page_views', 'referrer')) {
            Schema::table('page_views', function (Blueprint $table) {
                $table->dropColumn('referrer');
            });
        }
    }
};
