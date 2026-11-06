<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateVerifyLog;
use App\Models\LmsCertificate;
use App\Models\StudentCompletion;
use App\Models\User;
use App\Services\CertificateIssuerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class LmsCertificateController extends Controller
{
    public function index(Request $request)
    {
        LmsCertificate::ensureTable();
        StudentCompletion::ensureTable();

        $q = LmsCertificate::query()->with(['user:id,name,email', 'course:id,title']);
        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $q->where(function ($outer) use ($s) {
                $outer->where('certificate_code', 'like', '%' . $s . '%')
                    ->orWhere('student_name', 'like', '%' . $s . '%')
                    ->orWhere('course_title', 'like', '%' . $s . '%');
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }

        $rows = $q->orderByDesc('issued_at')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.certificates.index', [
            'rows' => $rows,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'status' => (string) $request->input('status', ''),
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

        return view('admin.certificates.verify-logs', [
            'rows' => $rows,
            'filters' => [
                'search' => (string) $request->input('search', ''),
                'result' => (string) $request->input('result', ''),
            ],
        ]);
    }

    public function download(int $id)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $path = public_path((string) $cert->pdf_path);
        if (! $cert->pdf_path || ! File::exists($path)) {
            return back()->with('fail', 'PDF file not found. Use Re-issue to regenerate.');
        }

        return response()->download($path, $cert->certificate_code . '.pdf');
    }

    public function preview(int $id)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $path = public_path((string) $cert->pdf_path);
        if (! $cert->pdf_path || ! File::exists($path)) {
            return back()->with('fail', 'PDF file not found. Use Re-issue to regenerate.');
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
}
