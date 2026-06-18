<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $librarian = DB::table('roles')->where('name', 'librarian')->first();
        $contentWriter = DB::table('roles')->where('name', 'content_writer')->first();

        if ($librarian && $contentWriter) {
            $librarianPermissions = DB::table('role_has_permissions')
                ->where('role_id', $librarian->id)
                ->pluck('permission_id');

            foreach ($librarianPermissions as $permissionId) {
                $exists = DB::table('role_has_permissions')
                    ->where('role_id', $contentWriter->id)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $contentWriter->id,
                        'permission_id' => $permissionId,
                    ]);
                }
            }

            $userIds = DB::table('user_has_roles')
                ->where('role_id', $librarian->id)
                ->pluck('user_id');

            foreach ($userIds as $userId) {
                $hasContentWriter = DB::table('user_has_roles')
                    ->where('user_id', $userId)
                    ->where('role_id', $contentWriter->id)
                    ->exists();

                if ($hasContentWriter) {
                    DB::table('user_has_roles')
                        ->where('user_id', $userId)
                        ->where('role_id', $librarian->id)
                        ->delete();
                } else {
                    DB::table('user_has_roles')
                        ->where('user_id', $userId)
                        ->where('role_id', $librarian->id)
                        ->update(['role_id' => $contentWriter->id]);
                }
            }

            DB::table('roles')->where('id', $librarian->id)->delete();
        } elseif ($librarian) {
            DB::table('roles')->where('id', $librarian->id)->update([
                'name' => 'content_writer',
                'description' => 'Content Writer — limited admin access for content management.',
                'updated_at' => now(),
            ]);
        } elseif (!$contentWriter) {
            DB::table('roles')->insert([
                'name' => 'content_writer',
                'description' => 'Content Writer — limited admin access for content management.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $contentWriter = DB::table('roles')->where('name', 'content_writer')->first();

        if ($contentWriter && !DB::table('roles')->where('name', 'librarian')->exists()) {
            DB::table('roles')->where('id', $contentWriter->id)->update([
                'name' => 'librarian',
                'updated_at' => now(),
            ]);
        }
    }
};
