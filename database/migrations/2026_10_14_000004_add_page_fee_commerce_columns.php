<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages') && ! Schema::hasColumn('pages', 'fee_heading')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->string('fee_heading')->nullable();
            });
        }

        if (Schema::hasTable('cart_items') && ! Schema::hasColumn('cart_items', 'page_fee_package_id')) {
            Schema::table('cart_items', function (Blueprint $table) {
                $table->unsignedBigInteger('page_fee_package_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'page_id')) {
                    $table->unsignedBigInteger('page_id')->nullable()->index();
                }
                if (! Schema::hasColumn('payments', 'page_fee_service_id')) {
                    $table->unsignedBigInteger('page_fee_service_id')->nullable()->index();
                }
                if (! Schema::hasColumn('payments', 'page_fee_category_id')) {
                    $table->unsignedBigInteger('page_fee_category_id')->nullable()->index();
                }
                if (! Schema::hasColumn('payments', 'page_fee_package_id')) {
                    $table->unsignedBigInteger('page_fee_package_id')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        // Additive columns kept for safety on rollback of live data.
    }
};
