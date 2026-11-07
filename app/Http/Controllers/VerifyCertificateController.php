<?php

namespace App\Http\Controllers;

use App\Models\CertificateVerifyLog;
use App\Models\LmsCertificate;
use App\Models\Page;
use App\Services\CertificateVerifyCopyService;
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
        $sid = trim((string) ($request->query('sid') ?: $request->input('student_number', '')));
        $sid = rawurldecode($sid);
        if ($inputCode !== '' && $sid === '') {
            $matched = $this->findByCode($inputCode);
            $sid = trim((string) ($matched->student_number ?? ''));
        }
        $request->session()->put('ecert_scan', [
            'code' => $inputCode,
            'student_number' => $sid,
        ]);

        return $this->verifyView($request, $inputCode, null, null, [
            'student_number' => old('student_number', $sid),
            'verifier_name' => old('verifier_name', ''),
            'verifier_email' => old('verifier_email', ''),
            'verifier_phone' => old('verifier_phone', ''),
            'verifier_organization' => old('verifier_organization', ''),
            'verifier_position' => old('verifier_position', ''),
            'verifier_reason' => old('verifier_reason', ''),
        ]);
    }

    public function saveVerifier(Request $request)
    {
        $data = $request->validate([
            'verifier_name' => 'required|string|max:255',
            'verifier_email' => 'required|email|max:255',
            'verifier_phone' => 'required|string|max:80',
            'verifier_organization' => 'required|string|max:255',
            'verifier_position' => 'required|string|max:255',
            'verifier_reason' => 'required|string|max:500',
        ], [
            'verifier_name.required' => 'Name is required.',
            'verifier_email.required' => 'Email address is required.',
            'verifier_phone.required' => 'Contact number is required.',
            'verifier_organization.required' => 'Organization / company name is required.',
            'verifier_position.required' => 'Position is required.',
            'verifier_reason.required' => 'Reason for verification is required.',
        ]);

        $request->session()->put('ecert_verifier', $data);

        $scan = $request->session()->get('ecert_scan', []);
        $code = trim((string) ($request->input('scan_code') ?: ($scan['code'] ?? '')));
        $sid = trim((string) ($request->input('scan_sid') ?: ($scan['student_number'] ?? '')));
        $url = url('/ecert-verify' . ($code !== '' ? '/' . rawurlencode($code) : ''));
        if ($sid !== '') {
            $url .= '?sid=' . rawurlencode($sid);
        }

        return redirect()->to($url)
            ->with('success', 'Verifier details saved. Check Student ID and Certificate Number to continue.');
    }

    public function check(Request $request)
    {
        LmsCertificate::ensureTable();
        CertificateVerifyLog::ensureTable();

        $verifier = $this->verifierFromSession($request);
        if (! $verifier) {
            return redirect()->to(url('/ecert-verify'))
                ->with('fail', 'Enter verifier details first. Student ID and Certificate Number stay locked until then.');
        }

        $data = $request->validate([
            'student_number' => 'required|string|max:100',
            'code' => 'required|string|max:80',
        ], [
            'student_number.required' => 'Enter the Student ID.',
            'code.required' => 'Enter the Certificate Number.',
        ]);

        $inputCode = trim((string) $data['code']);
        $studentNumber = trim((string) $data['student_number']);
        $certificate = $this->matchCertificate($inputCode, $studentNumber);

        if (! $certificate) {
            $result = 'not_found';
        } elseif ($certificate->isRevoked()) {
            $result = 'revoked';
        } else {
            $result = 'valid';
        }

        try {
            $ip = function_exists('visitor_client_ip')
                ? (visitor_client_ip($request) ?: (string) $request->ip())
                : (string) $request->ip();
            $place = function_exists('resolve_ip_location') ? resolve_ip_location($ip) : [];
            $row = [
                'certificate_code' => Str::limit($inputCode, 64, ''),
                'result' => $result,
                'ip' => substr((string) $ip, 0, 64),
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ];
            if (Schema::hasColumn('certificate_verify_logs', 'ip_city')) {
                $row['ip_city'] = substr((string) ($place['city'] ?? ''), 0, 120) ?: null;
            }
            if (Schema::hasColumn('certificate_verify_logs', 'ip_country')) {
                $row['ip_country'] = substr((string) ($place['country'] ?? ''), 0, 120) ?: null;
            }
            $extra = array_merge($verifier, [
                'student_number' => $studentNumber,
            ]);
            foreach ($extra as $col => $value) {
                if (Schema::hasColumn('certificate_verify_logs', $col)) {
                    $row[$col] = $value;
                }
            }
            CertificateVerifyLog::query()->create($row);
        } catch (\Throwable $e) {
            // Logging must never break verify UX.
        }

        return $this->verifyView($request, $inputCode, $certificate, $result, array_merge($verifier, [
            'student_number' => $studentNumber,
        ]));
    }

    protected function verifyView(Request $request, string $code, $certificate, ?string $result, array $old)
    {
        $copy = app(CertificateVerifyCopyService::class)->get();
        $verifierReady = $this->verifierFromSession($request) !== null;
        if ($verifierReady) {
            $sessionVerifier = $this->verifierFromSession($request);
            foreach ($sessionVerifier as $key => $value) {
                if (($old[$key] ?? '') === '') {
                    $old[$key] = $value;
                }
            }
        }

        $cmsPage = null;
        try {
            $cmsPage = Page::findVerifyCertificatePage();
            if ($cmsPage) {
                $cmsPage->load('sections');
                $cmsPage->setRelation(
                    'sections',
                    $cmsPage->sections->sortBy('order')->values()->map(function ($section) {
                        $decoded = is_string($section->data) ? json_decode($section->data, true) : $section->data;
                        $section->data = strip_waf_captcha_html(is_array($decoded) ? $decoded : []);
                        if (empty($section->section_type)) {
                            $section->section_type = $section->data['section_type'] ?? null;
                        }

                        return $section;
                    })
                );
            }
        } catch (\Throwable $e) {
            $cmsPage = null;
        }

        if (trim(strip_tags((string) ($copy['html'] ?? ''))) === '' && $cmsPage) {
            $parts = [];
            foreach ($cmsPage->sections ?? [] as $section) {
                $data = is_array($section->data ?? null) ? $section->data : [];
                $html = trim((string) ($data['description'] ?? $data['content'] ?? ''));
                if ($html !== '') {
                    $parts[] = $html;
                }
            }
            if ($parts !== []) {
                $copy['html'] = implode('', $parts);
            }
        }

        return view('certificates.verify', [
            'code' => $code,
            'certificate' => $certificate,
            'result' => $result,
            'old' => $old,
            'cmsPage' => $cmsPage,
            'copy' => $copy,
            'verifierReady' => $verifierReady,
        ]);
    }

    protected function verifierFromSession(Request $request): ?array
    {
        $row = $request->session()->get('ecert_verifier');
        if (! is_array($row)) {
            return null;
        }
        foreach (['verifier_name', 'verifier_email', 'verifier_phone', 'verifier_organization', 'verifier_position', 'verifier_reason'] as $key) {
            if (trim((string) ($row[$key] ?? '')) === '') {
                return null;
            }
        }

        return $row;
    }

    protected function findByCode(string $code): ?LmsCertificate
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $codeNorm = $this->normId($code);
        $candidates = LmsCertificate::query()
            ->where('certificate_code', $code)
            ->orWhereRaw('LOWER(certificate_code) = ?', [strtolower($code)])
            ->get();

        return $candidates->first(function (LmsCertificate $cert) use ($codeNorm) {
            return $this->normId((string) $cert->certificate_code) === $codeNorm;
        });
    }

    protected function matchCertificate(string $code, string $studentNumber): ?LmsCertificate
    {
        $codeNorm = $this->normId($code);
        $studentNorm = $this->normId($studentNumber);
        if ($codeNorm === '' || $studentNorm === '') {
            return null;
        }

        $candidates = LmsCertificate::query()
            ->where('certificate_code', $code)
            ->orWhereRaw('LOWER(certificate_code) = ?', [strtolower($code)])
            ->get();

        return $candidates->first(function (LmsCertificate $cert) use ($codeNorm, $studentNorm) {
            return $this->normId((string) $cert->certificate_code) === $codeNorm
                && $this->normId((string) $cert->student_number) === $studentNorm;
        });
    }

    protected function normId(string $value): string
    {
        return strtoupper((string) preg_replace('/[\s\-]+/', '', trim($value)));
    }
}
