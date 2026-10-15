<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_fees') && ! Schema::hasColumn('course_fees', 'terms_conditions')) {
            Schema::table('course_fees', function (Blueprint $table) {
                $table->longText('terms_conditions')->nullable()->after('package_includes');
            });
        }

        if (Schema::hasTable('page_fee_packages') && ! Schema::hasColumn('page_fee_packages', 'terms_conditions')) {
            Schema::table('page_fee_packages', function (Blueprint $table) {
                $table->longText('terms_conditions')->nullable()->after('package_includes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_fees') && Schema::hasColumn('course_fees', 'terms_conditions')) {
            Schema::table('course_fees', function (Blueprint $table) {
                $table->dropColumn('terms_conditions');
            });
        }

        if (Schema::hasTable('page_fee_packages') && Schema::hasColumn('page_fee_packages', 'terms_conditions')) {
            Schema::table('page_fee_packages', function (Blueprint $table) {
                $table->dropColumn('terms_conditions');
            });
        }
    }
};
