<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_terms_templates')) {
            Schema::table('invoice_terms_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('invoice_terms_templates', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                }
                if (! Schema::hasColumn('invoice_terms_templates', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                }
                if (! Schema::hasColumn('payments', 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoice_terms_templates')) {
            Schema::table('invoice_terms_templates', function (Blueprint $table) {
                if (Schema::hasColumn('invoice_terms_templates', 'created_by')) {
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('invoice_terms_templates', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'created_by')) {
                    $table->dropColumn('created_by');
                }
                if (Schema::hasColumn('payments', 'updated_by')) {
                    $table->dropColumn('updated_by');
                }
            });
        }
    }
};
