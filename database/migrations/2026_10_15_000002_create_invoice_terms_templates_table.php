<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invoice_terms_templates')) {
            Schema::create('invoice_terms_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->longText('body')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('course_fees') && ! Schema::hasColumn('course_fees', 'invoice_terms_template_id')) {
            Schema::table('course_fees', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_terms_template_id')->nullable()->index()->after('terms_conditions');
            });
        }

        if (Schema::hasTable('page_fee_packages') && ! Schema::hasColumn('page_fee_packages', 'invoice_terms_template_id')) {
            Schema::table('page_fee_packages', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_terms_template_id')->nullable()->index()->after('terms_conditions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_fees') && Schema::hasColumn('course_fees', 'invoice_terms_template_id')) {
            Schema::table('course_fees', function (Blueprint $table) {
                $table->dropColumn('invoice_terms_template_id');
            });
        }

        if (Schema::hasTable('page_fee_packages') && Schema::hasColumn('page_fee_packages', 'invoice_terms_template_id')) {
            Schema::table('page_fee_packages', function (Blueprint $table) {
                $table->dropColumn('invoice_terms_template_id');
            });
        }

        Schema::dropIfExists('invoice_terms_templates');
    }
};
