<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateVerifyLog;
use App\Models\Course;
use App\Models\LmsCertificate;
use App\Models\StudentCompletion;
use App\Models\User;
use App\Models\CertificateTemplate;
use App\Services\CertificateIssuerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LmsCertificateController extends Controller
{
    public function index(Request $request)
    {
        LmsCertificate::ensureTable();
        StudentCompletion::ensureTable();
        CertificateTemplate::ensureTable();

        $userCols = ['id', 'name', 'email'];
        foreach (['first_name', 'last_name', 'student_id', 'student_number'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                $userCols[] = $col;
            }
        }

        $q = StudentCompletion::query()->with([
            'user:' . implode(',', $userCols),
            'course:id,title',
            'batch:id,code,name',
            'template:id,name',
            'certificates',
        ]);
        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $q->where(function ($outer) use ($s) {
                $outer->where('notes', 'like', '%' . $s . '%')
                    ->orWhere('marked_by_name', 'like', '%' . $s . '%')
                    ->orWhere('course_subtitle', 'like', '%' . $s . '%');
                if (Schema::hasColumn('student_completions', 'student_number')) {
                    $outer->orWhere('student_number', 'like', '%' . $s . '%');
                }
                $outer
                    ->orWhereHas('user', function ($u) use ($s) {
                        $u->where('name', 'like', '%' . $s . '%')
                            ->orWhere('email', 'like', '%' . $s . '%');
                        if (Schema::hasColumn('users', 'student_id')) {
                            $u->orWhere('student_id', 'like', '%' . $s . '%');
                        }
                    })
                    ->orWhereHas('course', function ($c) use ($s) {
                        $c->where('title', 'like', '%' . $s . '%');
                    })
                    ->orWhereHas('certificates', function ($c) use ($s) {
                        $c->where('certificate_code', 'like', '%' . $s . '%')
                            ->orWhere('student_name', 'like', '%' . $s . '%')
                            ->orWhere('student_number', 'like', '%' . $s . '%');
                    });
            });
        }
        if ($request->filled('course_id')) {
            $q->where('course_id', (int) $request->input('course_id'));
        }
        $status = (string) $request->input('status', '');
        if ($status === 'issued' || $status === 'certificate_issued') {
            $q->whereHas('certificates', function ($c) {
                $c->where('status', 'issued');
            });
        } elseif ($status === 'revoked') {
            $q->whereHas('certificates', function ($c) {
                $c->where('status', 'revoked');
            });
        } elseif ($status === 'not_issued' || $status === 'completed') {
            $q->whereDoesntHave('certificates');
        }

        $rows = $q->orderByDesc('completed_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $courses = Course::query()->orderBy('title')->limit(500)->get(['id', 'title']);

        return view('admin.certificates.index', [
            'rows' => $rows,
            'courses' => $courses,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'status' => $status,
                'course_id' => (string) $request->input('course_id', ''),
            ],
        ]);
    }

    public function verifyLogs(Request $request)
    {
        CertificateVerifyLog::ensureTable();

        $q = CertificateVerifyLog::query();
        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $q->where(function ($outer) use ($s) {
                $outer->where('certificate_code', 'like', '%' . $s . '%');
                foreach (['verifier_name', 'verifier_email', 'verifier_organization', 'verifier_phone'] as $col) {
                    if (Schema::hasColumn('certificate_verify_logs', $col)) {
                        $outer->orWhere($col, 'like', '%' . $s . '%');
                    }
                }
            });
        }
        if ($request->filled('result')) {
            $q->where('result', $request->input('result'));
        }

        $rows = $q->orderByDesc('id')->paginate(40)->withQueryString();
        foreach ($rows as $row) {
            $needsCity = Schema::hasColumn('certificate_verify_logs', 'ip_city') && trim((string) ($row->ip_city ?? '')) === '';
            $needsCountry = Schema::hasColumn('certificate_verify_logs', 'ip_country') && trim((string) ($row->ip_country ?? '')) === '';
            if (($needsCity || $needsCountry) && ! empty($row->ip) && function_exists('resolve_ip_location')) {
                $place = resolve_ip_location((string) $row->ip);
                $dirty = false;
                if ($needsCity && ! empty($place['city'])) {
                    $row->ip_city = substr((string) $place['city'], 0, 120);
                    $dirty = true;
                }
                if ($needsCountry && ! empty($place['country'])) {
                    $row->ip_country = substr((string) $place['country'], 0, 120);
                    $dirty = true;
                }
                if ($dirty) {
                    try {
                        $row->save();
                    } catch (\Throwable $e) {
                        // Display still uses in-memory values.
                    }
                }
            }
        }

        return view('admin.certificates.verify-logs', [
            'rows' => $rows,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'result' => (string) $request->input('result', ''),
            ],
        ]);
    }

    public function download(int $id, CertificateIssuerService $issuer)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $path = $issuer->ensurePdfFile($cert);
        if (! $path) {
            return back()->with('fail', 'Certificate PDF could not be generated.');
        }

        return response()->download($path, $cert->certificate_code . '.pdf');
    }

    public function preview(int $id, CertificateIssuerService $issuer)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $path = $issuer->ensurePdfFile($cert);
        if (! $path) {
            return back()->with('fail', 'Certificate PDF could not be generated.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $cert->certificate_code . '.pdf"',
        ]);
    }

    public function email(int $id, CertificateIssuerService $issuer)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $error = $issuer->emailCertificate($cert);
        if ($error) {
            return back()->with('fail', $error);
        }

        return back()->with('success', 'Certificate emailed to the student.');
    }

    public function reissue(Request $request, int $id, CertificateIssuerService $issuer)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $admin = Auth::guard('admin')->user() ?: Auth::user();
        $sendEmail = $request->boolean('send_email', false);
        $fresh = $issuer->reissue($cert, $admin instanceof User ? $admin : null, $sendEmail);

        $msg = 'Certificate re-issued: ' . $fresh->certificate_code;
        if ($sendEmail) {
            $msg .= $fresh->emailed_at ? ' Email sent.' : ' (email not sent)';
        }

        return back()->with('success', $msg);
    }

    public function revoke(Request $request, int $id)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $data = $request->validate([
            'revoke_reason' => 'nullable|string|max:2000',
        ]);

        $cert->status = 'revoked';
        $cert->revoked_at = now();
        $cert->revoke_reason = $data['revoke_reason'] ?? null;
        $cert->save();

        return back()->with('success', 'Certificate revoked: ' . $cert->certificate_code);
    }

    public function edit(int $id)
    {
        LmsCertificate::ensureTable();
        CertificateTemplate::ensureTable();
        $cert = LmsCertificate::query()->with(['user', 'course', 'template'])->findOrFail($id);
        $templates = CertificateTemplate::activeOptions();

        return view('admin.certificates.edit', [
            'cert' => $cert,
            'templates' => $templates,
        ]);
    }

    public function update(Request $request, int $id, CertificateIssuerService $issuer)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $data = $request->validate([
            'student_name' => 'required|string|max:255',
            'course_title' => 'required|string|max:500',
            'course_subtitle' => 'nullable|string|max:500',
            'programme_duration' => 'nullable|string|max:255',
            'cpd_hours' => 'nullable|string|max:100',
            'completion_date' => 'nullable|date',
            'template_id' => 'nullable|integer|exists:certificate_templates,id',
            'regenerate_pdf' => 'nullable|boolean',
        ]);

        $cert->student_name = $data['student_name'];
        $cert->course_title = $data['course_title'];
        $cert->course_subtitle = $data['course_subtitle'] ?? null;
        $cert->programme_duration = $data['programme_duration'] ?? null;
        $cert->cpd_hours = $data['cpd_hours'] ?? null;
        $cert->completion_date = $data['completion_date'] ?? $cert->completion_date;
        if (! empty($data['template_id'])) {
            $cert->template_id = (int) $data['template_id'];
        }
        $cert->save();

        if ($request->boolean('regenerate_pdf', true) && ! $cert->isRevoked()) {
            $issuer->refreshPdf($cert->fresh());
        }

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', 'Certificate details saved' . ($request->boolean('regenerate_pdf', true) ? ' and PDF updated.' : '.'));
    }
}
