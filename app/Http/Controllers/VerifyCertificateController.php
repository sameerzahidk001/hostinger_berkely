<?php

namespace App\Http\Controllers;

use App\Models\LmsCertificate;
use Illuminate\Http\Request;

class VerifyCertificateController extends Controller
{
    public function show(Request $request, ?string $code = null)
    {
        LmsCertificate::ensureTable();

        $inputCode = trim((string) ($code ?: $request->input('code', '')));
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
        }

        return view('certificates.verify', [
            'code' => $inputCode,
            'certificate' => $certificate,
            'result' => $result,
        ]);
    }
}
