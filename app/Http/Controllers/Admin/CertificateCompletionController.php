<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LmsCertificate;
use App\Models\StudentCompletion;
use App\Models\User;
use App\Services\CertificateIssuerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CertificateCompletionController extends Controller
{
    public function index(Request $request)
    {
        StudentCompletion::ensureTable();
        LmsCertificate::ensureTable();

        $q = StudentCompletion::query()->with(['user:id,name,email,first_name,last_name', 'course:id,title', 'certificate']);
        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $q->where(function ($outer) use ($s) {
                $outer->where('notes', 'like', '%' . $s . '%')
                    ->orWhere('marked_by_name', 'like', '%' . $s . '%')
                    ->orWhereHas('user', function ($u) use ($s) {
                        $u->where('name', 'like', '%' . $s . '%')
                            ->orWhere('email', 'like', '%' . $s . '%')
                            ->orWhere('first_name', 'like', '%' . $s . '%')
                            ->orWhere('last_name', 'like', '%' . $s . '%');
                    })
                    ->orWhereHas('course', function ($c) use ($s) {
                        $c->where('title', 'like', '%' . $s . '%');
                    });
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('course_id')) {
            $q->where('course_id', (int) $request->input('course_id'));
        }

        $rows = $q->orderByDesc('completed_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $courses = Course::query()->orderBy('title')->limit(500)->get(['id', 'title']);

        return view('admin.certificates.completions', [
            'rows' => $rows,
            'courses' => $courses,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', ''),
                'course_id' => (string) $request->input('course_id', ''),
            ],
        ]);
    }

    public function create()
    {
        StudentCompletion::ensureTable();
        $courses = Course::query()->orderBy('title')->limit(500)->get(['id', 'title']);
        $students = User::query()
            ->when(Schema::hasColumn('users', 'role'), function ($q) {
                $q->where(function ($outer) {
                    $outer->where('role', 'student')->orWhereNull('role');
                });
            })
            ->orderBy('name')
            ->limit(800)
            ->get(['id', 'name', 'email', 'first_name', 'last_name']);

        return view('admin.certificates.completion-form', [
            'courses' => $courses,
            'students' => $students,
        ]);
    }

    public function store(Request $request, CertificateIssuerService $issuer)
    {
        StudentCompletion::ensureTable();
        LmsCertificate::ensureTable();

        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'course_id' => 'required|integer|exists:courses,id',
            'completed_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'issue_now' => 'nullable|boolean',
        ]);

        $exists = StudentCompletion::query()
            ->where('user_id', $data['user_id'])
            ->where('course_id', $data['course_id'])
            ->whereIn('status', ['completed', 'certificate_issued'])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('fail', 'An active completion already exists for this student and course.');
        }

        $admin = Auth::guard('admin')->user() ?: Auth::user();
        $completion = StudentCompletion::query()->create([
            'user_id' => (int) $data['user_id'],
            'course_id' => (int) $data['course_id'],
            'status' => 'completed',
            'completed_at' => ! empty($data['completed_at']) ? $data['completed_at'] : now(),
            'marked_by' => $admin?->id,
            'marked_by_name' => $admin
                ? trim((string) ($admin->name ?? $admin->email ?? 'Admin'))
                : (function_exists('audit_user_name') ? audit_user_name() : 'Admin'),
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->boolean('issue_now')) {
            $issuer->issueFromCompletion($completion, $admin instanceof User ? $admin : null);

            return redirect()
                ->route('admin.certificates.index')
                ->with('success', 'Completion saved and certificate issued.');
        }

        return redirect()
            ->route('admin.certificates.completions.index')
            ->with('success', 'Student marked as completed.');
    }

    public function issue(int $id, CertificateIssuerService $issuer)
    {
        StudentCompletion::ensureTable();
        LmsCertificate::ensureTable();

        $completion = StudentCompletion::query()->findOrFail($id);
        $admin = Auth::guard('admin')->user() ?: Auth::user();
        $cert = $issuer->issueFromCompletion($completion, $admin instanceof User ? $admin : null);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', 'Certificate issued: ' . $cert->certificate_code);
    }
}
