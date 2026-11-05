<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LmsCertificate;
use App\Models\StudentCompletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        LmsCertificate::ensureTable();
        StudentCompletion::ensureTable();

        $userId = (int) Auth::id();
        $certificates = LmsCertificate::query()
            ->where('user_id', $userId)
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->paginate(20);

        $pending = StudentCompletion::query()
            ->with('course:id,title')
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereDoesntHave('certificate', function ($q) {
                $q->where('status', 'issued');
            })
            ->orderByDesc('completed_at')
            ->limit(50)
            ->get();

        return view('user.certificates.index', [
            'certificates' => $certificates,
            'pending' => $pending,
        ]);
    }

    public function download(int $id)
    {
        LmsCertificate::ensureTable();
        $cert = LmsCertificate::query()
            ->where('user_id', (int) Auth::id())
            ->findOrFail($id);

        if ($cert->isRevoked()) {
            return back()->with('fail', 'This certificate has been revoked.');
        }

        $path = public_path((string) $cert->pdf_path);
        if (! $cert->pdf_path || ! File::exists($path)) {
            return back()->with('fail', 'PDF file is not available yet. Please contact admin.');
        }

        return response()->download($path, $cert->certificate_code . '.pdf');
    }
}
