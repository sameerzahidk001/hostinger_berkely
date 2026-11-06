<?php

namespace App\Http\Controllers;

use App\Models\CertificateVerifyLog;
use App\Models\LmsCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VerifyCertificateController extends Controller
{
    public function show(Request $request, ?string $code = null)
    {
        LmsCertificate::ensureTable();
        CertificateVerifyLog::ensureTable();

        $inputCode = trim((string) ($code ?: $request->input('code', '')));
        $inputCode = rawurldecode($inputCode);

        return view('certificates.verify', [
            'code' => $inputCode,
            'certificate' => null,
            'result' => null,
            'old' => [
                'verifier_name' => old('verifier_name', ''),
                'verifier_email' => old('verifier_email', ''),
                'verifier_phone' => old('verifier_phone', ''),
                'verifier_organization' => old('verifier_organization', ''),
                'verifier_position' => old('verifier_position', ''),
                'verifier_reason' => old('verifier_reason', ''),
            ],
        ]);
    }

    public function check(Request $request)
    {
        LmsCertificate::ensureTable();
        CertificateVerifyLog::ensureTable();

        $data = $request->validate([
            'code' => 'required|string|max:80',
            'verifier_name' => 'required|string|max:255',
            'verifier_email' => 'required|email|max:255',
            'verifier_phone' => 'required|string|max:80',
            'verifier_organization' => 'required|string|max:255',
            'verifier_position' => 'required|string|max:255',
            'verifier_reason' => 'required|string|max:500',
        ], [
            'code.required' => 'Enter the certificate ID / number.',
            'verifier_name.required' => 'Name is required.',
            'verifier_email.required' => 'Email address is required.',
            'verifier_phone.required' => 'Contact number is required.',
            'verifier_organization.required' => 'Organization / company name is required.',
            'verifier_position.required' => 'Position is required.',
            'verifier_reason.required' => 'Reason for verification is required.',
        ]);

        $inputCode = trim((string) $data['code']);
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
            $row = [
                'certificate_code' => Str::limit($inputCode, 64, ''),
                'result' => $result,
                'ip' => substr((string) $request->ip(), 0, 64),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ];
            $extra = [
                'verifier_name' => $data['verifier_name'],
                'verifier_email' => $data['verifier_email'],
                'verifier_phone' => $data['verifier_phone'],
                'verifier_organization' => $data['verifier_organization'],
                'verifier_position' => $data['verifier_position'],
                'verifier_reason' => $data['verifier_reason'],
            ];
            foreach ($extra as $col => $value) {
                if (Schema::hasColumn('certificate_verify_logs', $col)) {
                    $row[$col] = $value;
                }
            }
            CertificateVerifyLog::query()->create($row);
        } catch (\Throwable $e) {
            // Logging must never break verify UX.
        }

        return view('certificates.verify', [
            'code' => $inputCode,
            'certificate' => $certificate,
            'result' => $result,
            'old' => [
                'verifier_name' => $data['verifier_name'],
                'verifier_email' => $data['verifier_email'],
                'verifier_phone' => $data['verifier_phone'],
                'verifier_organization' => $data['verifier_organization'],
                'verifier_position' => $data['verifier_position'],
                'verifier_reason' => $data['verifier_reason'],
            ],
        ]);
    }
}
