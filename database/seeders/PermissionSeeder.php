<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Carbon\Carbon;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['name' => 'school-create', 'module' => 'school'],
            ['name' => 'school-read', 'module' => 'school'],
            ['name' => 'school-update', 'module' => 'school'],
            ['name' => 'school-delete', 'module' => 'school'],
            ['name' => 'school-list', 'module' => 'school'],
            ['name' => 'course-create', 'module' => 'course'],
            ['name' => 'course-read', 'module' => 'course'],
            ['name' => 'course-update', 'module' => 'course'],
            ['name' => 'course-delete', 'module' => 'course'],
            ['name' => 'course-list', 'module' => 'course'],
            ['name' => 'category-create', 'module' => 'category'],
            ['name' => 'category-read', 'module' => 'category'],
            ['name' => 'category-update', 'module' => 'category'],
            ['name' => 'category-delete', 'module' => 'category'],
            ['name' => 'category-list', 'module' => 'category'],
            ['name' => 'page-create', 'module' => 'page'],
            ['name' => 'page-read', 'module' => 'page'],
            ['name' => 'page-update', 'module' => 'page'],
            ['name' => 'page-delete', 'module' => 'page'],
            ['name' => 'page-list', 'module' => 'page'],
            ['name' => 'seo-create', 'module' => 'seo'],
            ['name' => 'seo-read', 'module' => 'seo'],
            ['name' => 'seo-update', 'module' => 'seo'],
            ['name' => 'seo-delete', 'module' => 'seo'],
            ['name' => 'seo-list', 'module' => 'seo'],
            ['name' => 'faq-create', 'module' => 'faq'],
            ['name' => 'faq-read', 'module' => 'faq'],
            ['name' => 'faq-update', 'module' => 'faq'],
            ['name' => 'faq-delete', 'module' => 'faq'],
            ['name' => 'faq-list', 'module' => 'faq'],
            ['name' => 'student-create', 'module' => 'student'],
            ['name' => 'student-read', 'module' => 'student'],
            ['name' => 'student-update', 'module' => 'student'],
            ['name' => 'student-delete', 'module' => 'student'],
            ['name' => 'student-list', 'module' => 'student'],
            ['name' => 'role-create', 'module' => 'role'],
            ['name' => 'role-read', 'module' => 'role'],
            ['name' => 'role-update', 'module' => 'role'],
            ['name' => 'role-delete', 'module' => 'role'],
            ['name' => 'role-list', 'module' => 'role'],
            ['name' => 'story-create', 'module' => 'story'],
            ['name' => 'story-read', 'module' => 'story'],
            ['name' => 'story-update', 'module' => 'story'],
            ['name' => 'story-delete', 'module' => 'story'],
            ['name' => 'story-list', 'module' => 'story'],
            ['name' => 'analytic-list', 'module' => 'analytic'],
            ['name' => 'profile-update', 'module' => 'profile'],
            ['name' => 'profile-delete', 'module' => 'profile'],
            ['name' => 'admin-create', 'module' => 'admin'],
            ['name' => 'admin-read', 'module' => 'admin'],
            ['name' => 'admin-update', 'module' => 'admin'],
            ['name' => 'admin-delete', 'module' => 'admin'],
            ['name' => 'admin-list', 'module' => 'admin'],
            ['name' => 'accountant-create', 'module' => 'accountant'],
            ['name' => 'accountant-read', 'module' => 'accountant'],
            ['name' => 'accountant-update', 'module' => 'accountant'],
            ['name' => 'accountant-delete', 'module' => 'accountant'],
            ['name' => 'accountant-list', 'module' => 'accountant'],
            ['name' => 'librarian-create', 'module' => 'librarian'],
            ['name' => 'librarian-read', 'module' => 'librarian'],
            ['name' => 'librarian-update', 'module' => 'librarian'],
            ['name' => 'librarian-delete', 'module' => 'librarian'],
            ['name' => 'librarian-list', 'module' => 'librarian'],
            ['name' => 'content_writer-create', 'module' => 'content_writer'],
            ['name' => 'content_writer-read', 'module' => 'content_writer'],
            ['name' => 'content_writer-update', 'module' => 'content_writer'],
            ['name' => 'content_writer-delete', 'module' => 'content_writer'],
            ['name' => 'content_writer-list', 'module' => 'content_writer'],
            ['name' => 'instructor-create', 'module' => 'instructor'],
            ['name' => 'instructor-read', 'module' => 'instructor'],
            ['name' => 'instructor-update', 'module' => 'instructor'],
            ['name' => 'instructor-delete', 'module' => 'instructor'],
            ['name' => 'instructor-list', 'module' => 'instructor'],
        ];

        // Insert all permissions into the database using Eloquent
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']], // Prevent duplicates
                [
                    'module'     => $permission['module'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}