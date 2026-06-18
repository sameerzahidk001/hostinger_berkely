<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Carbon\Carbon;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'student', 'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
            ['name' => 'instructor', 'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
            ['name' => 'content_writer', 'description' => 'Content Writer — limited admin access for content management.'],
            ['name' => 'accountant', 'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
            // ['name' => 'admin', 'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.'],
        ];

        // Insert all permissions into the database using Eloquent
        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                [
                    'description'     => $role['description'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}