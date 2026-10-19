<?php

namespace Database\Seeders;

use App\Models\Email;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class LmsAssessmentEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('emails')) {
            return;
        }

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
                'name' => 'lms-test-assigned',
                'subject' => 'Test assigned: {test_id} - {test_name}',
                'cc' => null,
                'bcc' => null,
                'body' => '<p>Hello {name},</p>'
                    . '<p>A test has been assigned to you.</p>'
                    . '<p>Test ID: {test_id} - Test Name: {test_name}</p>'
                    . '<p>Appointment ID: {appointment_id} · {appointment_time}</p>'
                    . '<p>Course: {course_name}</p>'
                    . '<p><a href="{portal_url}">Open My Tests</a></p>'
                    . '<p>If you are not signed in, use <a href="{login_url}">this login page</a>.</p>',
            ],
            [
                'name' => 'lms-test-result-published',
                'subject' => 'Results published: {test_id} - {test_name}',
                'cc' => null,
                'bcc' => null,
                'body' => '<p>Hello {name},</p>'
                    . '<p>Your results have been published.</p>'
                    . '<p>Test ID: {test_id} - Test Name: {test_name}</p>'
                    . '<p>Appointment ID: {appointment_id} · {appointment_time}</p>'
                    . '<p>Course: {course_name}</p>'
                    . '<p><a href="{result_url}">View your result</a></p>'
                    . '<p>If you are not signed in, use <a href="{login_url}">this login page</a>.</p>',
            ],
        ];
    }
}
