<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LmsCertificate;
use App\Models\StudentCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

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

    public function download(int $id)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()->findOrFail($id);
        $path = public_path((string) $cert->pdf_path);
        if (! $cert->pdf_path || ! File::exists($path)) {
            return back()->with('fail', 'PDF file not found. Re-issue the certificate.');
        }

        return response()->download($path, $cert->certificate_code . '.pdf');
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
