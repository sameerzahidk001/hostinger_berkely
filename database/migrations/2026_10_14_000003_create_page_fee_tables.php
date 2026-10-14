<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('page_fee_services')) {
            Schema::create('page_fee_services', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('page_id')->index();
                $table->string('name');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('page_fee_categories')) {
            Schema::create('page_fee_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('service_id')->index();
                $table->string('name');
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_visible')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('page_fee_packages')) {
            Schema::create('page_fee_packages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('category_id')->index();
                $table->string('package_name');
                $table->string('currency', 10)->default('AED');
                $table->decimal('price', 12, 2)->default(0);
                $table->decimal('tax_percentage', 8, 2)->nullable();
                $table->decimal('discount_amount', 12, 2)->nullable();
                $table->text('short_description')->nullable();
                $table->text('key_point')->nullable();
                $table->longText('package_includes')->nullable();
                $table->unsignedInteger('priority')->default(0);
                $table->unsignedInteger('installments')->nullable();
                $table->boolean('showonwebsite')->default(true);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('page_fee_packages');
        Schema::dropIfExists('page_fee_categories');
        Schema::dropIfExists('page_fee_services');
    }
};
