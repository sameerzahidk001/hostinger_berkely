<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'subject', 'cc', 'bcc', 'body'];

    public static function templateChoices()
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
            'lms-test-submitted' => 'LMS Test Submitted',
            'lms-test-result-published' => 'LMS Test Result Published',
        ];
    }

    /** Old keys kept in the database but not used or listed. */
    public static function unusedTemplateNames()
    {
        return ['lms-test-assigned', 'New Test assigned'];
    }

    public static function placeholderHelp($name)
    {
        $lmsAssign = 'Use these in subject and body:<br>'
            . '<strong>{name}</strong> student name · <strong>{email}</strong><br>'
            . '<strong>{test_id}</strong> · <strong>{test_name}</strong> · <strong>{test_type}</strong> (Practice / Mock / General / Skill)<br>'
            . '<strong>{sitting_kind}</strong> Exam or Self test<br>'
            . '<strong>{start_date}</strong> · <strong>{start_time}</strong> · <strong>{start_datetime}</strong> (Self test = Start anytime)<br>'
            . '<strong>{timezone}</strong> · <strong>{duration}</strong> · <strong>{assessor}</strong><br>'
            . '<strong>{max_attempts}</strong> · <strong>{appointment_id}</strong> · <strong>{appointment_time}</strong><br>'
            . '<strong>{course_name}</strong> · <strong>{portal_url}</strong> · <strong>{login_url}</strong>';

        switch ((string) $name) {
            case 'user-registration':
                return 'You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{password}</strong> <strong>{role}</strong>';
            case 'email-verification':
                return 'You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{email-verification}</strong>';
            case 'user-suspend':
            case 'user-active':
            case 'send-invoice':
            case 'send-receipt':
            case 'admin-user-update':
                return 'You can use: <strong>{name}</strong> <strong>{email}</strong>';
            case 'user-update':
            case 'password-changed':
            case 'admin-user-password-changed':
                return 'You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{password}</strong>';
            case 'password-reset':
                return 'You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{password-reset}</strong>';
            case 'fees-paid':
                return 'You can use: <strong>{name}</strong> <strong>{email}</strong> <strong>{fees-paid}</strong> <strong>{fees-installment}</strong>';
            case 'study-material-student-access':
                return 'Folder: <strong>{name}</strong> <strong>{email}</strong> <strong>{folder_name}</strong> <strong>{course_name}</strong> <strong>{head_of_faculty_name}</strong> <strong>{trainer_name}</strong> <strong>{instructor_name}</strong> <strong>{validity}</strong> <strong>{access_till}</strong> <strong>{issued_at}</strong> <strong>{portal_url}</strong> <strong>{login_url}</strong>';
            case 'study-material-student-disabled':
                return 'Folder disabled: <strong>{name}</strong> <strong>{email}</strong> <strong>{folder_name}</strong> <strong>{course_name}</strong> <strong>{reason}</strong> <strong>{portal_url}</strong> <strong>{login_url}</strong>';
            case 'study-material-instructor-access':
                return 'Folder: <strong>{name}</strong> <strong>{email}</strong> <strong>{folder_name}</strong> <strong>{course_name}</strong> <strong>{validity}</strong> <strong>{access_till}</strong> <strong>{issued_at}</strong> <strong>{portal_url}</strong> <strong>{login_url}</strong>';
            case 'lms-exam-assigned':
            case 'lms-self-test-assigned':
            case 'lms-test-assigned':
                return $lmsAssign;
            case 'lms-test-submitted':
                return $lmsAssign . '<br><strong>{started_at}</strong> · <strong>{submitted_at}</strong> · <strong>{portal_url}</strong>';
            case 'lms-test-result-published':
                return $lmsAssign . '<br><strong>{result_url}</strong> result page';
            default:
                return '';
        }
    }
}
