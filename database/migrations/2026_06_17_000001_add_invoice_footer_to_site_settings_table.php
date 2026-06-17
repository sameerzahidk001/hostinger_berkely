<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'invoice_footer_usa')) {
                $table->text('invoice_footer_usa')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'invoice_footer_uk')) {
                $table->text('invoice_footer_uk')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'invoice_footer_middle_east')) {
                $table->text('invoice_footer_middle_east')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'invoice_footer_email')) {
                $table->string('invoice_footer_email', 255)->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'invoice_footer_website')) {
                $table->string('invoice_footer_website', 255)->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'invoice_footer_presence')) {
                $table->string('invoice_footer_presence', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            foreach ([
                'invoice_footer_usa',
                'invoice_footer_uk',
                'invoice_footer_middle_east',
                'invoice_footer_email',
                'invoice_footer_website',
                'invoice_footer_presence',
            ] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
