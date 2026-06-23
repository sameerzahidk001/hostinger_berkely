<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admins')) {
            return;
        }

        if (! Schema::hasColumn('admins', 'image')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->string('image')->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'image')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
};
