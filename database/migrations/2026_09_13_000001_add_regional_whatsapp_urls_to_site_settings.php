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
            if (! Schema::hasColumn('site_settings', 'whatsapp_usa_url')) {
                $table->string('whatsapp_usa_url')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'whatsapp_uk_url')) {
                $table->string('whatsapp_uk_url')->nullable();
            }
            if (! Schema::hasColumn('site_settings', 'whatsapp_middle_east_url')) {
                $table->string('whatsapp_middle_east_url')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) {
            foreach (['whatsapp_usa_url', 'whatsapp_uk_url', 'whatsapp_middle_east_url'] as $column) {
                if (Schema::hasColumn('site_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
