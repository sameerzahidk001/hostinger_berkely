<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PanelRolesSeeder extends Seeder
{
    public function run(): void
    {
        $this->ensureContentWriterRole();
        $this->ensureAccountantRole();
        $this->ensureContentWriterUser();
    }

    private function ensureContentWriterRole(): void
    {
        $librarian = Role::where('name', 'librarian')->first();
        $contentWriter = Role::where('name', 'content_writer')->first();

        if ($librarian && $contentWriter) {
            DB::table('user_has_roles')
                ->where('role_id', $librarian->id)
                ->get()
                ->each(function ($row) use ($contentWriter, $librarian) {
                    $duplicate = DB::table('user_has_roles')
                        ->where('user_id', $row->user_id)
                        ->where('role_id', $contentWriter->id)
                        ->exists();

                    if ($duplicate) {
                        DB::table('user_has_roles')
                            ->where('user_id', $row->user_id)
                            ->where('role_id', $librarian->id)
                            ->delete();
                    } else {
                        DB::table('user_has_roles')
                            ->where('user_id', $row->user_id)
                            ->where('role_id', $librarian->id)
                            ->update(['role_id' => $contentWriter->id]);
                    }
                });

            $librarian->delete();
        } elseif ($librarian) {
            $librarian->update([
                'name' => 'content_writer',
                'description' => 'Content Writer — limited admin access for content management.',
            ]);
        } else {
            Role::firstOrCreate(
                ['name' => 'content_writer'],
                ['description' => 'Content Writer — limited admin access for content management.']
            );
        }
    }

    private function ensureAccountantRole(): void
    {
        Role::firstOrCreate(
            ['name' => 'accountant'],
            ['description' => 'Accountant — payments and currency access only.']
        );
    }

    private function ensureContentWriterUser(): void
    {
        $role = Role::where('name', 'content_writer')->first();

        if (!$role) {
            return;
        }

        $hasContentWriter = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['content_writer', 'librarian']);
        })->exists();

        if ($hasContentWriter) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => 'contentwriter@berkeleyme.com'],
            [
                'name' => 'Content Writer',
                'password' => Hash::make('password'),
                'approved' => 1,
                'image' => '/images/profiles/user.png',
            ]
        );

        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
