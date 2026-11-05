<?php

namespace App\Http\Controllers;

use App\Models\CertificateVerifyLog;
use App\Models\LmsCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VerifyCertificateController extends Controller
{
    public function show(Request $request, ?string $code = null)
    {
        LmsCertificate::ensureTable();
        CertificateVerifyLog::ensureTable();

        $inputCode = trim((string) ($code ?: $request->input('code', '')));
        $inputCode = rawurldecode($inputCode);
        $certificate = null;
        $result = null;

        if ($inputCode !== '') {
            $certificate = LmsCertificate::query()
                ->where('certificate_code', $inputCode)
                ->first();

            if (! $certificate) {
                $result = 'not_found';
            } elseif ($certificate->isRevoked()) {
                $result = 'revoked';
            } else {
                $result = 'valid';
            }

            try {
                CertificateVerifyLog::query()->create([
                    'certificate_code' => Str::limit($inputCode, 64, ''),
                    'result' => $result,
                    'ip' => substr((string) $request->ip(), 0, 64),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                ]);
            } catch (\Throwable $e) {
                // Logging must never break verify UX.
            }
        }

        return view('certificates.verify', [
            'code' => $inputCode,
            'certificate' => $certificate,
            'result' => $result,
        ]);
    }
}
