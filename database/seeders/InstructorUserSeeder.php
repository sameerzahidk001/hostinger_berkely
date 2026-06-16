<?php

namespace Database\Seeders;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class InstructorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructorUser = User::create([
            'name' => 'Instructor',
            'email' => 'instructor@gmail.com',
            'password' => Hash::make('root'),
        ]);
        $instructorRole = Role::firstOrCreate(['name' => 'instructor', 'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.']);
        $instructorUser->roles()->sync([$instructorRole->id]);
    }
}
