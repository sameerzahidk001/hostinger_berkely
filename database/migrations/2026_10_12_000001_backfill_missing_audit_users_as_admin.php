<?php

use App\Models\Admin;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = Admin::query()
            ->where(function ($q) {
                $q->where('name', 'Admin')
                    ->orWhere('email', 'like', 'admin@%')
                    ->orWhere('email', 'info@berkeleyme.com');
            })
            ->orderBy('id')
            ->value('id');

        if (! $adminId) {
            $adminId = Admin::query()->orderBy('id')->value('id');
        }

        if (! $adminId) {
            return;
        }

        foreach (['pages', 'courses', 'pages_s_e_o_s'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (Schema::hasColumn($table, 'created_by')) {
                DB::table($table)->whereNull('created_by')->update(['created_by' => $adminId]);
            }

            if (Schema::hasColumn($table, 'updated_by')) {
                DB::table($table)->whereNull('updated_by')->update(['updated_by' => $adminId]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible data backfill.
    }
};
