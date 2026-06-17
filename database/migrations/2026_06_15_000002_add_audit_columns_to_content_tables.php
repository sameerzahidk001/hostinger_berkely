<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'courses',
        'pages',
        'course_agendas',
        'categories',
        'faqs',
        'pages_s_e_o_s',
        'clients',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (!Schema::hasColumn($table, 'created_by')) {
                    $blueprint->unsignedBigInteger('created_by')->nullable()->after('updated_at');
                }
                if (!Schema::hasColumn($table, 'updated_by')) {
                    $blueprint->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (Schema::hasColumn($table, 'updated_by')) {
                    $blueprint->dropColumn('updated_by');
                }
                if (Schema::hasColumn($table, 'created_by')) {
                    $blueprint->dropColumn('created_by');
                }
            });
        }
    }
};
