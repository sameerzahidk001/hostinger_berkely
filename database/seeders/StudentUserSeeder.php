<?php

namespace Database\Seeders;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studentUser = User::create([
            'name' => 'Student',
            'email' => 'student@gmail.com',
            'mobile_number' => '+971501234567',
            'password' => Hash::make('root'),
            'approved' => 1,
            'email_verified_at' => now(),
            'country' => 'AE',
        ]);

        $studentRole = Role::firstOrCreate(['name' => 'student', 'description' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.']);
        $studentUser->roles()->sync([$studentRole->id]);
    }
}
