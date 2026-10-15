<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PageFeeStructureService
{
    public function ensureTables(): bool
    {
        try {
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
                    $table->longText('terms_conditions')->nullable();
                    $table->unsignedInteger('priority')->default(0);
                    $table->unsignedInteger('installments')->nullable();
                    $table->boolean('showonwebsite')->default(true);
                    $table->boolean('status')->default(true);
                    $table->timestamps();
                });
            }

            $this->ensurePageFeeHeadingColumn();
            $this->ensureCommerceColumns();
            $this->ensureTermsConditionsColumn();

            return true;
        } catch (\Throwable $e) {
            Log::warning('page fee tables ensure failed: ' . $e->getMessage());

            return false;
        }
    }

    public function ensureCommerceColumns(): bool
    {
        try {
            if (Schema::hasTable('cart_items') && ! Schema::hasColumn('cart_items', 'page_fee_package_id')) {
                Schema::table('cart_items', function (Blueprint $table) {
                    $table->unsignedBigInteger('page_fee_package_id')->nullable()->index();
                });
            }

            if (Schema::hasTable('cart_items') && Schema::hasColumn('cart_items', 'course_fee_package_id')) {
                try {
                    DB::statement('ALTER TABLE cart_items MODIFY course_fee_package_id BIGINT UNSIGNED NULL');
                } catch (\Throwable $e) {
                    // Host may already allow null or block ALTER — continue.
                }
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

                try {
                    DB::statement('ALTER TABLE payments MODIFY course_id BIGINT UNSIGNED NULL');
                } catch (\Throwable $e) {
                    // already nullable / blocked
                }
                try {
                    DB::statement('ALTER TABLE payments MODIFY package_id BIGINT UNSIGNED NULL');
                } catch (\Throwable $e) {
                    // already nullable / blocked
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('page fee commerce columns ensure failed: ' . $e->getMessage());

            return false;
        }
    }

    public function ensureTermsConditionsColumn(): bool
    {
        try {
            if (Schema::hasTable('page_fee_packages') && ! Schema::hasColumn('page_fee_packages', 'terms_conditions')) {
                Schema::table('page_fee_packages', function (Blueprint $table) {
                    $table->longText('terms_conditions')->nullable();
                });
            }

            if (Schema::hasTable('course_fees') && ! Schema::hasColumn('course_fees', 'terms_conditions')) {
                Schema::table('course_fees', function (Blueprint $table) {
                    $table->longText('terms_conditions')->nullable();
                });
            }

            if (Schema::hasTable('page_fee_packages') && ! Schema::hasColumn('page_fee_packages', 'invoice_terms_template_id')) {
                Schema::table('page_fee_packages', function (Blueprint $table) {
                    $table->unsignedBigInteger('invoice_terms_template_id')->nullable()->index();
                });
            }

            if (Schema::hasTable('course_fees') && ! Schema::hasColumn('course_fees', 'invoice_terms_template_id')) {
                Schema::table('course_fees', function (Blueprint $table) {
                    $table->unsignedBigInteger('invoice_terms_template_id')->nullable()->index();
                });
            }

            \App\Models\InvoiceTermsTemplate::ensureTable();

            return true;
        } catch (\Throwable $e) {
            Log::warning('fee package terms_conditions ensure failed: ' . $e->getMessage());

            return false;
        }
    }

    private function ensurePageFeeHeadingColumn(): void
    {
        if (! Schema::hasTable('pages') || Schema::hasColumn('pages', 'fee_heading')) {
            return;
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->string('fee_heading')->nullable();
        });
    }
}
