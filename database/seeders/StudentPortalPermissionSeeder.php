<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StudentPortalPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'dashboard-read', 'module' => 'dashboard'],
            ['name' => 'cart-list', 'module' => 'cart'],
            ['name' => 'installment-list', 'module' => 'installment'],
            ['name' => 'testimonial-list', 'module' => 'testimonial'],
            ['name' => 'testimonial-create', 'module' => 'testimonial'],
            ['name' => 'testimonial-read', 'module' => 'testimonial'],
            ['name' => 'testimonial-update', 'module' => 'testimonial'],
            ['name' => 'testimonial-delete', 'module' => 'testimonial'],
        ];

        $permissionIds = [];

        foreach ($permissions as $permission) {
            $record = Permission::updateOrCreate(
                ['name' => $permission['name']],
                [
                    'module' => $permission['module'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
            $permissionIds[] = $record->id;
        }

        $studentRole = Role::where('name', 'student')->first();

        if ($studentRole) {
            $studentRole->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
