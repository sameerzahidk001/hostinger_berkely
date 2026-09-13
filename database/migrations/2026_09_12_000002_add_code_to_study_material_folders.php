<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('study_material_folders')) {
            return;
        }

        if (! Schema::hasColumn('study_material_folders', 'code')) {
            Schema::table('study_material_folders', function (Blueprint $table) {
                $table->string('code', 40)->nullable()->unique()->after('id');
            });
        }

        $rows = DB::table('study_material_folders')->orderBy('id')->get(['id', 'code']);
        foreach ($rows as $row) {
            if (filled($row->code)) {
                continue;
            }

            DB::table('study_material_folders')
                ->where('id', $row->id)
                ->update(['code' => 'SM-' . str_pad((string) $row->id, 4, '0', STR_PAD_LEFT)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('study_material_folders') && Schema::hasColumn('study_material_folders', 'code')) {
            Schema::table('study_material_folders', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }
    }
};
