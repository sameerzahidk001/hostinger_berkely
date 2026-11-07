<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\ClassBatch;
use App\Models\Course;
use App\Models\LmsCertificate;
use App\Models\StudentCompletion;
use App\Models\User;
use App\Services\CertificateIssuerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CertificateCompletionController extends Controller
{
    public function index(Request $request)
    {
        return redirect()->route('admin.certificates.index', $request->query());
    }

    public function create()
    {
        StudentCompletion::ensureTable();
        CertificateTemplate::ensureTable();

        $courses = Course::query()->orderBy('title')->limit(500)->get(['id', 'title']);
        $students = User::query()
            ->when(Schema::hasColumn('users', 'role'), function ($q) {
                $q->where(function ($outer) {
                    $outer->where('role', 'student')->orWhereNull('role');
                });
            })
            ->orderBy('name')
            ->limit(800)
            ->get($this->userSelectColumns());

        $batches = ClassBatch::query()
            ->with('course:id,title')
            ->orderByDesc('id')
            ->limit(400)
            ->get(['id', 'code', 'name', 'course_id']);

        $templates = CertificateTemplate::activeOptions();

        return view('admin.certificates.completion-form', [
            'courses' => $courses,
            'students' => $students,
            'batches' => $batches,
            'templates' => $templates,
        ]);
    }

    public function store(Request $request, CertificateIssuerService $issuer)
    {
        StudentCompletion::ensureTable();
        LmsCertificate::ensureTable();
        CertificateTemplate::ensureTable();

        $data = $request->validate([
            'target_type' => 'required|in:student,batch',
            'user_id' => 'nullable|integer|exists:users,id',
            'batch_id' => 'nullable|integer|exists:class_batches,id',
            'course_id' => 'nullable|integer|exists:courses,id',
            'template_id' => 'required|integer|exists:certificate_templates,id',
            'completed_at' => 'nullable|date',
            'programme_duration' => 'nullable|string|max:255',
            'cpd_hours' => 'nullable|string|max:100',
            'course_subtitle' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
            'issue_now' => 'nullable|boolean',
            'send_email' => 'nullable|boolean',
        ]);

        $template = CertificateTemplate::query()->findOrFail((int) $data['template_id']);
        if (! $template->is_active) {
            throw ValidationException::withMessages(['template_id' => 'Selected template is inactive.']);
        }

        $admin = Auth::guard('admin')->user() ?: Auth::user();
        $issueNow = $request->boolean('issue_now', true);
        $sendEmail = $request->boolean('send_email', true);
        $completedAt = ! empty($data['completed_at']) ? $data['completed_at'] : now();
        $notes = $data['notes'] ?? null;
        $markedBy = $admin?->id;
        $markedByName = $admin
            ? trim((string) ($admin->name ?? $admin->email ?? 'Admin'))
            : (function_exists('audit_user_name') ? audit_user_name(null) : 'Admin');

        $targets = [];
        if ($data['target_type'] === 'student') {
            if (empty($data['user_id'])) {
                throw ValidationException::withMessages(['user_id' => 'Select a student.']);
            }
            if (empty($data['course_id'])) {
                throw ValidationException::withMessages(['course_id' => 'Select a course.']);
            }
            $targets[] = [
                'user_id' => (int) $data['user_id'],
                'course_id' => (int) $data['course_id'],
                'batch_id' => null,
            ];
        } else {
            if (empty($data['batch_id'])) {
                throw ValidationException::withMessages(['batch_id' => 'Select a batch.']);
            }
            $batch = ClassBatch::query()->findOrFail((int) $data['batch_id']);
            $courseId = (int) ($batch->course_id ?: ($data['course_id'] ?? 0));
            if ($courseId <= 0) {
                throw ValidationException::withMessages(['batch_id' => 'This batch has no course linked.']);
            }
            $studentIds = ClassBatch::studentUserIdsFor((int) $batch->id);
            if ($studentIds->isEmpty()) {
                throw ValidationException::withMessages(['batch_id' => 'No students found on this batch.']);
            }
            foreach ($studentIds as $sid) {
                $targets[] = [
                    'user_id' => (int) $sid,
                    'course_id' => $courseId,
                    'batch_id' => (int) $batch->id,
                ];
            }
        }

        $created = 0;
        $issued = 0;
        $codes = [];

        foreach ($targets as $target) {
            $completion = StudentCompletion::query()->create([
                'user_id' => $target['user_id'],
                'course_id' => $target['course_id'],
                'batch_id' => $target['batch_id'],
                'template_id' => (int) $template->id,
                'status' => 'completed',
                'completed_at' => $completedAt,
                'programme_duration' => $data['programme_duration'] ?? null,
                'cpd_hours' => $data['cpd_hours'] ?? null,
                'course_subtitle' => $data['course_subtitle'] ?? null,
                'marked_by' => $markedBy,
                'marked_by_name' => $markedByName,
                'notes' => $notes,
            ]);
            $created++;

            if ($issueNow) {
                $cert = $issuer->issueFromCompletion(
                    $completion,
                    $admin instanceof User ? $admin : null,
                    $sendEmail,
                    $template,
                    true
                );
                $issued++;
                $codes[] = $cert->certificate_code;
            }
        }

        if ($issueNow) {
            $msg = "Issued {$issued} certificate(s) for {$created} student(s).";
            if (count($codes) === 1) {
                $msg .= ' Code: ' . $codes[0] . '.';
            }
            if ($sendEmail) {
                $msg .= ' Email attempted for issued certificates.';
            }

            return redirect()
                ->route('admin.certificates.index')
                ->with('success', $msg);
        }

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', "Saved for {$created} student(s). Certificate not issued yet.");
    }

    public function issue(Request $request, int $id, CertificateIssuerService $issuer)
    {
        StudentCompletion::ensureTable();
        LmsCertificate::ensureTable();
        CertificateTemplate::ensureTable();

        $completion = StudentCompletion::query()->findOrFail($id);
        $admin = Auth::guard('admin')->user() ?: Auth::user();
        $sendEmail = $request->boolean('send_email', true);

        $templateId = (int) $request->input('template_id', $completion->template_id ?: 0);
        $template = $templateId > 0
            ? CertificateTemplate::query()->find($templateId)
            : CertificateTemplate::ensureDefault();
        if (! $template) {
            return back()->with('fail', 'No certificate template available. Create one first.');
        }

        $cert = $issuer->issueFromCompletion(
            $completion,
            $admin instanceof User ? $admin : null,
            $sendEmail,
            $template,
            false
        );

        $msg = 'Certificate issued: ' . $cert->certificate_code;
        if ($sendEmail) {
            $msg .= $cert->emailed_at ? ' Email sent.' : ' (email not sent — check student email / SMTP)';
        }

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', $msg);
    }

    public function edit(int $id)
    {
        StudentCompletion::ensureTable();
        CertificateTemplate::ensureTable();
        $completion = StudentCompletion::query()->with(['user', 'course', 'template', 'certificates'])->findOrFail($id);
        $templates = CertificateTemplate::activeOptions();

        return view('admin.certificates.completion-edit', [
            'completion' => $completion,
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, int $id, CertificateIssuerService $issuer)
    {
        StudentCompletion::ensureTable();
        $completion = StudentCompletion::query()->findOrFail($id);
        $data = $request->validate([
            'completed_at' => 'nullable|date',
            'programme_duration' => 'nullable|string|max:255',
            'cpd_hours' => 'nullable|string|max:100',
            'course_subtitle' => 'nullable|string|max:500',
            'template_id' => 'nullable|integer|exists:certificate_templates,id',
            'notes' => 'nullable|string|max:2000',
            'apply_to_certificate' => 'nullable|boolean',
        ]);

        $completion->completed_at = $data['completed_at'] ?? $completion->completed_at;
        $completion->programme_duration = $data['programme_duration'] ?? null;
        $completion->cpd_hours = $data['cpd_hours'] ?? null;
        $completion->course_subtitle = $data['course_subtitle'] ?? null;
        $completion->notes = $data['notes'] ?? $completion->notes;
        if (! empty($data['template_id'])) {
            $completion->template_id = (int) $data['template_id'];
        }
        $completion->save();

        if ($request->boolean('apply_to_certificate', true)) {
            $latest = LmsCertificate::query()
                ->where('completion_id', $completion->id)
                ->orderByDesc('id')
                ->first();
            if ($latest && ! $latest->isRevoked()) {
                $latest->course_subtitle = $completion->course_subtitle;
                $latest->programme_duration = $completion->programme_duration;
                $latest->cpd_hours = $completion->cpd_hours;
                $latest->completion_date = optional($completion->completed_at)->toDateString() ?: $latest->completion_date;
                if ($completion->template_id) {
                    $latest->template_id = $completion->template_id;
                }
                $latest->save();
                $issuer->refreshPdf($latest->fresh());
            }
        }

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', 'Certificate details saved.');
    }

    /** @return array<int, string> */
    private function userSelectColumns(): array
    {
        $cols = ['id', 'name', 'email'];
        foreach (['first_name', 'last_name', 'student_id', 'student_number'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                $cols[] = $col;
            }
        }

        return $cols;
    }
}
