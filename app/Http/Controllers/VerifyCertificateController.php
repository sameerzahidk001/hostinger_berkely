<?php

namespace App\Http\Controllers;

use App\Mail\UserMail;
use App\Models\CertificateVerifyLog;
use App\Models\LmsCertificate;
use App\Models\Page;
use App\Services\CertificateVerifyCopyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        $scan = $request->session()->get('ecert_scan', []);
        if ($inputCode === '') {
            $inputCode = trim((string) ($scan['code'] ?? ''));
        }
        if ($sid === '') {
            $sid = trim((string) ($scan['student_number'] ?? ''));
        }
        if ($inputCode !== '' && $sid === '') {
            $matched = $this->findByCode($inputCode);
            $sid = trim((string) ($matched->student_number ?? ''));
        }
        $request->session()->put('ecert_scan', [
            'code' => $inputCode,
            'student_number' => $sid,
        ]);

        $verifierReady = $this->verifierFromSession($request) !== null;
        $hasScanInUrl = trim((string) ($code ?: $request->query('sid', ''))) !== '';
        if (! $verifierReady && $hasScanInUrl) {
            return redirect()->to(url('/ecert-verify'));
        }

        return $this->verifyView($request, $verifierReady ? $inputCode : '', null, null, [
            'student_number' => old('student_number', $verifierReady ? $sid : ''),
            'verifier_name' => old('verifier_name', ''),
            'verifier_email' => old('verifier_email', ''),
            'verifier_phone' => old('verifier_phone', ''),
            'verifier_organization' => old('verifier_organization', ''),
            'verifier_position' => old('verifier_position', ''),
            'verifier_reason' => old('verifier_reason', ''),
        ]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'verifier_email' => 'required|email|max:255',
        ], [
            'verifier_email.required' => 'Email address is required to send the OTP.',
            'verifier_email.email' => 'Enter a valid email address.',
        ]);

        $email = strtolower(trim((string) $request->input('verifier_email')));
        $lastSent = (int) $request->session()->get('ecert_otp_sent_at', 0);
        if ($lastSent > 0 && (time() - $lastSent) < 60) {
            return redirect()->to(url('/ecert-verify'))
                ->withInput($request->except('otp'))
                ->with('fail', 'Please wait a minute before requesting another OTP.');
        }

        $sends = (int) $request->session()->get('ecert_otp_sends', 0);
        if ($sends >= 8) {
            return redirect()->to(url('/ecert-verify'))
                ->withInput($request->except('otp'))
                ->with('fail', 'Too many OTP requests. Wait and try again later.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $request->session()->put('ecert_otp', [
            'email' => $email,
            'hash' => Hash::make($email.'|'.$code),
            'expires_at' => now()->addMinutes(10)->timestamp,
            'attempts' => 0,
        ]);
        $request->session()->forget('ecert_otp_ok');
        $request->session()->put('ecert_otp_sent_at', time());
        $request->session()->put('ecert_otp_sends', $sends + 1);

        $name = trim((string) $request->input('verifier_name', ''));
        $greeting = $name !== '' ? e($name) : 'there';
        $body = '<p>Hello '.$greeting.',</p>'
            .'<p>Your certificate verification code is:</p>'
            .'<p style="font-size:28px;letter-spacing:4px;font-weight:bold;">'.$code.'</p>'
            .'<p>This code expires in 10 minutes. If you did not request it, you can ignore this email.</p>';

        try {
            $guest = (object) ['email' => $email, 'name' => $name !== '' ? $name : 'Verifier'];
            Mail::to($email)->send(new UserMail($guest, 'Your certificate verification code', $body));
        } catch (\Throwable $e) {
            $request->session()->forget('ecert_otp');

            return redirect()->to(url('/ecert-verify'))
                ->withInput($request->except('otp'))
                ->with('fail', 'The OTP email could not be sent. Check the email address and try again.');
        }

        return redirect()->to(url('/ecert-verify'))
            ->withInput($request->except('otp'))
            ->with('success', 'OTP sent to '.$email.'. Enter the code, then submit your details.');
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
            'verifier_name.required' => 'Your Name is required.',
            'verifier_email.required' => 'Email Address is required.',
            'verifier_phone.required' => 'Contact Number is required.',
            'verifier_organization.required' => 'Organization / Company Name is required.',
            'verifier_position.required' => 'Position is required.',
            'verifier_reason.required' => 'Reason for Certificate Verification is required.',
        ]);

        $email = strtolower(trim((string) $data['verifier_email']));
        $data['verifier_email'] = $email;
        if (! $this->otpMatchesEmail($request, $email)) {
            $otpError = $this->consumeOtp($request, $email, trim((string) $request->input('otp', '')));
            if ($otpError !== null) {
                return redirect()->to(url('/ecert-verify'))
                    ->withInput($request->except('otp'))
                    ->withErrors(['otp' => $otpError]);
            }
        }

        $request->session()->put('ecert_otp_ok', $email);
        $request->session()->forget('ecert_otp');
        $request->session()->put('ecert_verifier', $data);

        return redirect()->to(url('/ecert-verify'))
            ->with('success', 'Details submitted. Enter the Certification Number and Student ID to check the certificate.');
    }

    public function check(Request $request)
    {
        LmsCertificate::ensureTable();
        CertificateVerifyLog::ensureTable();

        $verifier = $this->verifierFromSession($request);
        if (! $verifier) {
            return redirect()->to(url('/ecert-verify'))
                ->with('fail', 'Fill in your details, verify your email with the OTP, then submit before checking a certificate.');
        }

        $data = $request->validate([
            'student_number' => 'required|string|max:100',
            'code' => 'required|string|max:80',
        ], [
            'student_number.required' => 'Enter the Student ID.',
            'code.required' => 'Enter the Certification ID.',
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
            $scan = $request->session()->get('ecert_scan', []);
            if ($code === '') {
                $code = trim((string) ($scan['code'] ?? ''));
            }
            if (trim((string) ($old['student_number'] ?? '')) === '') {
                $old['student_number'] = trim((string) ($scan['student_number'] ?? ''));
            }
        } else {
            $old['student_number'] = '';
            $code = '';
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

        $otpEmail = strtolower(trim((string) ($old['verifier_email'] ?? '')));
        $otpVerified = $otpEmail !== '' && $this->otpMatchesEmail($request, $otpEmail);

        return view('certificates.verify', [
            'code' => $verifierReady ? $code : '',
            'certificate' => $verifierReady ? $certificate : null,
            'result' => $verifierReady ? $result : null,
            'old' => $old,
            'cmsPage' => $cmsPage,
            'copy' => $copy,
            'verifierReady' => $verifierReady,
            'otpVerified' => $otpVerified,
            'otpPending' => is_array($request->session()->get('ecert_otp')),
        ]);
    }

    protected function otpMatchesEmail(Request $request, string $email): bool
    {
        $ok = strtolower(trim((string) $request->session()->get('ecert_otp_ok', '')));

        return $ok !== '' && hash_equals($ok, strtolower(trim($email)));
    }

    protected function consumeOtp(Request $request, string $email, string $otp): ?string
    {
        if ($otp === '') {
            return 'Enter the OTP sent to your email address.';
        }

        $payload = $request->session()->get('ecert_otp');
        if (! is_array($payload) || trim((string) ($payload['hash'] ?? '')) === '') {
            return 'Send OTP to your email address first.';
        }

        $storedEmail = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($storedEmail === '' || ! hash_equals($storedEmail, strtolower(trim($email)))) {
            return 'Send OTP to this email address first.';
        }

        if ((int) ($payload['expires_at'] ?? 0) < time()) {
            $request->session()->forget('ecert_otp');

            return 'That OTP has expired. Send a new one.';
        }

        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= 8) {
            $request->session()->forget('ecert_otp');

            return 'Too many incorrect OTP attempts. Send a new code.';
        }

        if (! Hash::check(strtolower(trim($email)).'|'.$otp, (string) $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            $request->session()->put('ecert_otp', $payload);

            return 'That OTP is not correct. Check the code and try again.';
        }

        return null;
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

        $email = strtolower(trim((string) ($row['verifier_email'] ?? '')));
        if (! $this->otpMatchesEmail($request, $email)) {
            return null;
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
