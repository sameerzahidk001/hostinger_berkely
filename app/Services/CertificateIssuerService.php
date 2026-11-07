<?php

namespace App\Services;

use App\Mail\CertificateIssuedMail;
use App\Models\CertificateTemplate;
use App\Models\Email;
use App\Models\LmsCertificate;
use App\Models\StudentCompletion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CertificateIssuerService
{
    public function issueFromCompletion(
        StudentCompletion $completion,
        ?User $issuer = null,
        bool $sendEmail = false,
        ?CertificateTemplate $template = null,
        bool $forceNew = false
    ): LmsCertificate {
        LmsCertificate::ensureTable();
        StudentCompletion::ensureTable();
        CertificateTemplate::ensureTable();

        if (! $forceNew) {
            $existing = LmsCertificate::query()
                ->where('completion_id', $completion->id)
                ->where('status', 'issued')
                ->orderByDesc('id')
                ->first();
            if ($existing) {
                if ($sendEmail) {
                    $this->emailCertificate($existing);
                }

                return $existing;
            }
        }

        $completion->loadMissing(['user', 'course', 'template']);
        $student = $completion->user;
        $course = $completion->course;

        if (! $template) {
            $template = $completion->template
                ?: ($completion->template_id
                    ? CertificateTemplate::query()->find($completion->template_id)
                    : null)
                ?: CertificateTemplate::ensureDefault();
        }

        $studentName = trim((string) ($student?->name ?? ''));
        if ($studentName === '') {
            $studentName = trim((string) (($student->first_name ?? '') . ' ' . ($student->last_name ?? '')));
        }
        if ($studentName === '') {
            $studentName = 'Student';
        }

        $courseTitle = trim((string) ($course?->title ?? $course?->name ?? 'Course'));
        if ($courseTitle === '') {
            $courseTitle = 'Course';
        }
        $courseSubtitle = trim((string) ($completion->course_subtitle ?: ($course?->short_name ?? '')));
        $duration = trim((string) ($completion->programme_duration ?: ($template?->default_duration ?? '')));
        if ($duration === '' && $course) {
            $duration = trim((string) ($course->course_duration ?? '') . ' ' . (string) ($course->duration_unit ?? ''));
        }
        $cpd = trim((string) ($completion->cpd_hours ?: ($template?->default_cpd_hours ?? '')));
        $studentNumber = $this->resolveStudentNumber($student);
        if ($studentNumber !== '' && (string) ($completion->student_number ?? '') !== $studentNumber) {
            $completion->student_number = $studentNumber;
            $completion->save();
        }

        $code = $this->nextCertificateCode();
        $verifyUrl = url('/ecert-verify/' . rawurlencode($code));
        if ($studentNumber !== '') {
            $verifyUrl .= '?sid=' . rawurlencode($studentNumber);
        }

        $cert = LmsCertificate::query()->create([
            'completion_id' => $completion->id,
            'user_id' => (int) $completion->user_id,
            'course_id' => $completion->course_id ? (int) $completion->course_id : null,
            'template_id' => $template?->id,
            'certificate_code' => $code,
            'status' => 'issued',
            'issued_at' => now(),
            'student_name' => Str::limit($studentName, 255, ''),
            'student_number' => Str::limit($studentNumber, 100, ''),
            'course_title' => Str::limit($courseTitle, 500, ''),
            'course_subtitle' => Str::limit($courseSubtitle, 500, ''),
            'programme_duration' => Str::limit($duration, 255, ''),
            'cpd_hours' => Str::limit($cpd, 100, ''),
            'completion_date' => optional($completion->completed_at)->toDateString() ?: now()->toDateString(),
            'qr_payload' => $verifyUrl,
            'issued_by' => $issuer?->id,
            'issued_by_name' => $issuer
                ? trim((string) ($issuer->name ?? $issuer->email ?? 'Admin'))
                : (function_exists('audit_user_name') ? audit_user_name(null) : 'Admin'),
        ]);

        if ($template && (! $completion->template_id || (int) $completion->template_id !== (int) $template->id)) {
            $completion->template_id = $template->id;
        }

        $pdfRel = $this->renderPdf($cert->fresh(['template']), $template);
        $cert->pdf_path = $pdfRel;
        $cert->save();

        if ($completion->status !== 'certificate_issued') {
            $completion->status = 'certificate_issued';
        }
        $completion->save();

        if ($sendEmail) {
            $this->emailCertificate($cert->fresh());
        }

        return $cert->fresh();
    }

    public function reissue(LmsCertificate $cert, ?User $issuer = null, bool $sendEmail = false): LmsCertificate
    {
        LmsCertificate::ensureTable();
        $cert->status = 'issued';
        $cert->revoke_reason = null;
        if (Schema::hasColumn('lms_certificates', 'reissued_at')) {
            $cert->reissued_at = now();
        }
        if ($issuer) {
            $cert->issued_by = $issuer->id;
            $cert->issued_by_name = trim((string) ($issuer->name ?? $issuer->email ?? 'Admin'));
        }
        $cert->qr_payload = $cert->verifyUrl();
        $cert->loadMissing('template');
        $template = $cert->template ?: CertificateTemplate::ensureDefault();
        $cert->pdf_path = $this->renderPdf($cert, $template);
        $cert->save();

        if ($sendEmail) {
            $this->emailCertificate($cert->fresh());
        }

        return $cert->fresh();
    }

    public function refreshPdf(LmsCertificate $cert): LmsCertificate
    {
        LmsCertificate::ensureTable();
        $cert->qr_payload = $cert->verifyUrl();
        $cert->loadMissing('template');
        $template = $cert->template ?: CertificateTemplate::ensureDefault();
        $cert->pdf_path = $this->renderPdf($cert, $template);
        $cert->save();

        return $cert->fresh();
    }

    /**
     * @return string|null Error message, or null on success.
     */
    public function emailCertificate(LmsCertificate $cert): ?string
    {
        LmsCertificate::ensureTable();
        $cert->loadMissing('user');
        $user = $cert->user;
        if (! $user || empty($user->email)) {
            return 'Student has no email address.';
        }
        if ($cert->isRevoked()) {
            return 'Revoked certificates cannot be emailed. Re-issue first.';
        }

        $cert->loadMissing('template');
        $cert = $this->refreshPdf($cert);
        $pdfAbs = public_path((string) $cert->pdf_path);
        if (! File::exists($pdfAbs)) {
            return 'Certificate PDF could not be generated.';
        }

        $template = $this->ensureEmailTemplate();
        if (! $template) {
            return 'Certificate email template is missing.';
        }

        $placeholders = [
            '{name}' => $cert->student_name ?: ($user->name ?? ''),
            '{email}' => $user->email ?? '',
            '{course_name}' => $cert->course_title ?: '—',
            '{certificate_code}' => $cert->certificate_code,
            '{student_id}' => $cert->student_number ?: '—',
            '{issued_at}' => optional($cert->issued_at)->format('d M Y') ?: '—',
            '{completion_date}' => optional($cert->completion_date)->format('d M Y') ?: '—',
            '{verify_url}' => $cert->verifyUrl(),
            '{portal_url}' => url(route('user.certificates.index', [], false)),
            '{login_url}' => url('/login'),
        ];

        try {
            $body = str_replace(array_keys($placeholders), array_values($placeholders), (string) $template->body);
            $subject = str_replace(array_keys($placeholders), array_values($placeholders), (string) $template->subject);
            $cc = ! empty($template->cc) ? array_filter(array_map('trim', explode(',', (string) $template->cc))) : [];
            $bcc = ! empty($template->bcc) ? array_filter(array_map('trim', explode(',', (string) $template->bcc))) : [];

            Mail::to($user->email)->cc($cc)->bcc($bcc)->send(new CertificateIssuedMail(
                $user,
                $subject,
                $body,
                $pdfAbs,
                $cert->certificate_code . '.pdf'
            ));

            $cert->emailed_at = now();
            $cert->save();
        } catch (\Throwable $e) {
            Log::warning('Certificate email failed: ' . $e->getMessage());

            return 'Email sending failed: ' . $e->getMessage();
        }

        return null;
    }

    public function ensureEmailTemplate(): ?Email
    {
        if (! Schema::hasTable('emails')) {
            return null;
        }

        $existing = Email::query()
            ->whereIn('name', ['certificate-issued', 'Certificate Issued'])
            ->orderByRaw("CASE name WHEN 'certificate-issued' THEN 0 ELSE 1 END")
            ->first();
        if ($existing) {
            return $existing;
        }

        return Email::query()->firstOrCreate(
            ['name' => 'certificate-issued'],
            [
                'subject' => 'Your certificate is ready | {course_name}',
                'cc' => null,
                'bcc' => null,
                'body' => 'Dear {name},<br><br>'
                    . 'Congratulations. Your digital certificate for <strong>{course_name}</strong> is ready.<br><br>'
                    . 'Certificate code: <strong>{certificate_code}</strong><br>'
                    . 'Issued: {issued_at}<br>'
                    . 'Completion date: {completion_date}<br><br>'
                    . 'Download the attached PDF, or verify online:<br>'
                    . '<a href="{verify_url}">{verify_url}</a><br><br>'
                    . 'You can also open your certificates in the student portal:<br>'
                    . '<a href="{portal_url}">{portal_url}</a><br><br>'
                    . 'Berkeley School of Business, Arts &amp; Sciences',
            ]
        );
    }

    public function nextCertificateCode(): string
    {
        LmsCertificate::ensureTable();
        $year = date('Y');
        $prefix = 'CERT-' . $year . '-';
        $last = LmsCertificate::query()
            ->where('certificate_code', 'like', $prefix . '%')
            ->orderByDesc('certificate_code')
            ->value('certificate_code');

        $seq = 1;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 8;
        }

        do {
            $code = $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
            $taken = LmsCertificate::query()->where('certificate_code', $code)->exists();
            if (! $taken) {
                return $code;
            }
            $seq += 8;
        } while ($seq < 1000000);

        return $prefix . str_pad((string) (time() % 1000000), 6, '0', STR_PAD_LEFT);
    }

    public function renderPdf(LmsCertificate $cert, ?CertificateTemplate $template = null): string
    {
        CertificateTemplate::ensureTable();
        $dir = public_path('certificates');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        if (! $template) {
            $cert->loadMissing('template');
            $template = $cert->template ?: CertificateTemplate::ensureDefault();
        }

        $qrPath = $this->ensureQrImage($cert);
        $html = view('certificates.pdf', [
            'certificate' => $cert,
            'template' => $template,
            'qrSrc' => $this->pdfFileSrc($qrPath),
            'bgSrc' => $this->pdfPublicSrc($template?->resolvedBackgroundPath()),
            'logoSrc' => $this->pdfPublicSrc($template?->logo_path),
            'sealSrc' => $this->pdfPublicSrc($template?->seal_path),
            'signerLeftSrc' => $this->pdfPublicSrc($template?->signer_left_signature),
            'signerRightSrc' => $this->pdfPublicSrc($template?->signer_right_signature),
            'schoolName' => $template ? $template->schoolName() : 'Berkeley School of Business, Arts & Sciences',
        ])->render();

        // Artwork and HTML are A4 portrait. Landscape paper leaves a white strip
        // and spills the footer onto a second page in Zoho/Gmail PDF preview.
        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        try {
            $dompdf = $pdf->getDomPDF();
            $options = $dompdf->getOptions();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('dpi', 96);
        } catch (\Throwable $e) {
            // PDF still writes with default options.
        }
        // Unique filename when multiple certs share similar codes over time.
        $fileName = Str::slug($cert->certificate_code) . '-' . $cert->id . '.pdf';
        $absolute = $dir . DIRECTORY_SEPARATOR . $fileName;
        File::put($absolute, $pdf->output());

        return 'certificates/' . $fileName;
    }

    public function ensurePdfFile(LmsCertificate $cert): ?string
    {
        $candidates = [];
        $stored = trim((string) $cert->pdf_path);
        if ($stored !== '') {
            $candidates[] = public_path($stored);
            $candidates[] = base_path($stored);
            if (preg_match('/^([A-Za-z]:\\\\|\/)/', $stored) === 1) {
                $candidates[] = $stored;
            }
        }
        $slug = Str::slug((string) $cert->certificate_code);
        $candidates[] = public_path('certificates/' . $slug . '-' . $cert->id . '.pdf');
        $candidates[] = public_path('certificates/' . $slug . '.pdf');
        $candidates[] = public_path('certificates/' . $cert->certificate_code . '.pdf');

        foreach ($candidates as $path) {
            if ($path && is_file($path) && filesize($path) > 64) {
                return $path;
            }
        }

        $fresh = $this->refreshPdf($cert);
        $path = public_path((string) $fresh->pdf_path);

        return (is_file($path) && filesize($path) > 64) ? $path : null;
    }

    public function ensureQrImage(LmsCertificate $cert): ?string
    {
        $payload = trim((string) ($cert->qr_payload ?: $cert->verifyUrl()));
        if ($payload === '') {
            return null;
        }

        $dir = public_path('certificates/qr');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $file = $dir . DIRECTORY_SEPARATOR . Str::slug($cert->certificate_code) . '.png';
        if (File::exists($file) && File::size($file) > 64) {
            return $file;
        }

        try {
            $response = Http::timeout(12)->get('https://api.qrserver.com/v1/create-qr-code/', [
                'size' => '220x220',
                'data' => $payload,
            ]);
            if ($response->successful() && strlen((string) $response->body()) > 64) {
                File::put($file, $response->body());

                return $file;
            }
        } catch (\Throwable $e) {
            // Fall through — PDF still works without QR image.
        }

        return File::exists($file) ? $file : null;
    }

    public function resolveStudentNumber(?User $user, ?string $override = null): string
    {
        if (! $user) {
            return User::nextBsbStudentId();
        }

        $fromCert = '';
        if (Schema::hasTable('lms_certificates')) {
            $fromCert = User::normalizeBsbStudentId((string) LmsCertificate::query()
                ->where('user_id', (int) $user->id)
                ->where('student_number', 'like', 'BSB%')
                ->orderBy('id')
                ->value('student_number'));
        }
        if ($fromCert !== '' && $user->studentIdValue() === '') {
            $user->student_id = $fromCert;
            $user->student_number = $fromCert;
            $user->save();
        }

        return $user->ensureFixedStudentId();
    }

    public function pdfPublicSrc(?string $relative): ?string
    {
        $relative = trim((string) $relative);
        if ($relative === '') {
            return null;
        }
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        $abs = $this->resolvePublicFile($relative);
        if ($abs) {
            return $this->pdfFileSrc($abs);
        }

        try {
            return url('/' . $relative);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function pdfFileSrc(?string $absolute): ?string
    {
        if (! $absolute || ! is_file($absolute)) {
            return null;
        }

        $jpeg = $this->pdfJpegData($absolute);
        if ($jpeg) {
            return 'data:image/jpeg;base64,' . base64_encode($jpeg);
        }

        $raw = @file_get_contents($absolute);
        if ($raw === false || $raw === '') {
            return null;
        }
        $mime = 'image/png';
        $ext = strtolower((string) pathinfo($absolute, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'], true)) {
            $mime = 'image/jpeg';
        } elseif ($ext === 'gif') {
            $mime = 'image/gif';
        } elseif ($ext === 'webp') {
            $mime = 'image/webp';
        }

        return 'data:' . $mime . ';base64,' . base64_encode($raw);
    }

    private function resolvePublicFile(string $relative): ?string
    {
        $candidates = [
            public_path($relative),
            base_path('public/' . $relative),
            base_path($relative),
        ];
        $doc = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', (string) $_SERVER['DOCUMENT_ROOT']), '/') : '';
        if ($doc !== '') {
            $candidates[] = $doc . '/' . $relative;
        }
        foreach ($candidates as $abs) {
            if ($abs && is_file($abs) && filesize($abs) > 32) {
                return $abs;
            }
        }

        return null;
    }

    private function pdfJpegData(string $absolute): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }
        $raw = @file_get_contents($absolute);
        if ($raw === false || $raw === '') {
            return null;
        }
        $src = @imagecreatefromstring($raw);
        if (! $src) {
            return null;
        }
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);

            return null;
        }
        $maxW = 1240;
        $maxH = 1754;
        $scale = min(1, $maxW / $w, $maxH / $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $nw, $nh, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        ob_start();
        imagejpeg($dst, null, 82);
        $jpeg = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        return is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }
}
