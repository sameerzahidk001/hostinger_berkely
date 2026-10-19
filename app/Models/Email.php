<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'subject', 'cc', 'bcc', 'body'];

    /** System template keys shown in the admin dropdown. */
    public static function templateChoices(): array
    {
        return [
            'user-registration' => 'User Registration',
            'email-verification' => 'Email Verification',
            'user-suspend' => 'User Suspend',
            'user-active' => 'User Active',
            'user-update' => 'User Update',
            'password-reset' => 'Password Reset',
            'password-changed' => 'Password Changed',
            'fees-paid' => 'Fees Paid',
            'send-invoice' => 'Send Invoice',
            'send-receipt' => 'Send Receipt',
            'admin-user-update' => 'Admin User Update',
            'admin-user-password-changed' => 'Admin User Password Changed',
            'study-material-student-access' => 'Study Material Student Access',
            'study-material-student-disabled' => 'Study Material Student Disabled',
            'study-material-instructor-access' => 'Study Material Instructor Access',
            'lms-exam-assigned' => 'LMS Exam Assigned (with appointment)',
            'lms-self-test-assigned' => 'LMS Self Test Assigned (start anytime)',
            'lms-test-result-published' => 'LMS Test Result Published',
        ];
    }

    public static function placeholderHelp(string $name): string
    {
        $lmsAssign = 'Use these in subject and body:<br>'
            . '<strong>{name}</strong> student name · <strong>{email}</strong><br>'
            . '<strong>{test_id}</strong> · <strong>{test_name}</strong> · <strong>{test_type}</strong> (Practice / Mock / General / Skill)<br>'
            . '<strong>{sitting_kind}</strong> Exam or Self test<br>'
            . '<strong>{start_date}</strong> · <strong>{start_time}</strong> · <strong>{start_datetime}</strong> (Self test = Start anytime)<br>'
            . '<strong>{timezone}</strong> · <strong>{duration}</strong> · <strong>{assessor}</strong><br>'
            . '<strong>{max_attempts}</strong> · <strong>{appointment_id}</strong> · <strong>{appointment_time}</strong><br>'
            . '<strong>{course_name}</strong> · <strong>{portal_url}</strong> · <strong>{login_url}</strong>';

        return match ($name) {
            'user-registration' => "You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{password}</strong> <strong>{role}</strong>",
            'email-verification' => "You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{email-verification}</strong>",
            'user-suspend', 'user-active', 'send-invoice', 'send-receipt', 'admin-user-update' => "You can use: <strong>{name}</strong> <strong>{email}</strong>",
            'user-update', 'password-changed', 'admin-user-password-changed' => "You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{password}</strong>",
            'password-reset' => "You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{password-reset}</strong>",
            'fees-paid' => "You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{fees-paid}</strong> <strong>{fees-installment}</strong>",
            'study-material-student-access' => "Folder: <strong>{name}</strong> <strong>{email}</strong> <strong>{folder_name}</strong> <strong>{course_name}</strong> <strong>{head_of_faculty_name}</strong> <strong>{trainer_name}</strong> <strong>{instructor_name}</strong> <strong>{validity}</strong> <strong>{access_till}</strong> <strong>{issued_at}</strong> <strong>{portal_url}</strong> <strong>{login_url}</strong>",
            'study-material-student-disabled' => "Folder disabled: <strong>{name}</strong> <strong>{email}</strong> <strong>{folder_name}</strong> <strong>{course_name}</strong> <strong>{reason}</strong> <strong>{portal_url}</strong> <strong>{login_url}</strong>",
            'study-material-instructor-access' => "Folder: <strong>{name}</strong> <strong>{email}</strong> <strong>{folder_name}</strong> <strong>{course_name}</strong> <strong>{validity}</strong> <strong>{access_till}</strong> <strong>{issued_at}</strong> <strong>{portal_url}</strong> <strong>{login_url}</strong>",
            'lms-exam-assigned', 'lms-self-test-assigned', 'lms-test-assigned' => $lmsAssign,
            'lms-test-result-published' => $lmsAssign . '<br><strong>{result_url}</strong> result page',
            default => '',
        };
    }
}