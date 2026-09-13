<?php

namespace Database\Seeders;

use App\Models\Email;
use Illuminate\Database\Seeder;

class StudyMaterialEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::templates() as $template) {
            Email::query()->firstOrCreate(
                ['name' => $template['name']],
                [
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'cc' => $template['cc'] ?? null,
                    'bcc' => $template['bcc'] ?? null,
                ]
            );
        }
    }

    public static function templates(): array
    {
        return [
            [
                'name' => 'study-material-instructor-access',
                'subject' => 'Folder access granted — {folder_name}',
                'cc' => null,
                'bcc' => null,
                'body' => '<p>Hello {name},</p>'
                    . '<p>You have been granted access to edit and upload study materials for <strong>{folder_name}</strong>.</p>'
                    . '<p>Course: {course_name}<br>Validity: {validity}<br>Access till: {access_till}<br>Issued: {issued_at}</p>'
                    . '<p><a href="{portal_url}">Open Study Material Folders</a></p>'
                    . '<p>If you are not signed in, use <a href="{login_url}">this login page</a>.</p>',
            ],
            [
                'name' => 'study-material-student-access',
                'subject' => 'Your study materials are ready — {folder_name}',
                'cc' => null,
                'bcc' => null,
                'body' => '<p>Hello {name},</p>'
                    . '<p>You now have access to study materials for <strong>{folder_name}</strong>.</p>'
                    . '<p>Course: {course_name}<br>Instructor: {instructor_name}<br>Validity: {validity}<br>Access till: {access_till}<br>Issued: {issued_at}</p>'
                    . '<p><a href="{portal_url}">Open Study Materials</a></p>'
                    . '<p>If you are not signed in, use <a href="{login_url}">this login page</a>.</p>',
            ],
            [
                'name' => 'study-material-student-disabled',
                'subject' => 'Study material access disabled — {folder_name}',
                'cc' => null,
                'bcc' => null,
                'body' => '<p>Hello {name},</p>'
                    . '<p>Your access to study materials for <strong>{folder_name}</strong> has been {reason}.</p>'
                    . '<p>Course: {course_name}<br>Instructor: {instructor_name}</p>'
                    . '<p>If you believe this is a mistake, contact <a href="mailto:admin@eduberkeley.com">admin@eduberkeley.com</a>.</p>'
                    . '<p><a href="{login_url}">Sign in to the student portal</a></p>',
            ],
        ];
    }
}
