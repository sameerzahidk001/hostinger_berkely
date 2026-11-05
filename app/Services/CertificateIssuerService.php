<?php

namespace App\Services;

use App\Models\LmsCertificate;
use App\Models\StudentCompletion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CertificateIssuerService
{
    public function issueFromCompletion(StudentCompletion $completion, ?User $issuer = null): LmsCertificate
    {
        LmsCertificate::ensureTable();
        StudentCompletion::ensureTable();

        $existing = LmsCertificate::query()
            ->where('completion_id', $completion->id)
            ->where('status', 'issued')
            ->first();
        if ($existing) {
            return $existing;
        }

        $completion->loadMissing(['user', 'course']);
        $student = $completion->user;
        $course = $completion->course;

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

        $code = $this->nextCertificateCode();
        $verifyUrl = url('/verify-certificate/' . urlencode($code));

        $cert = LmsCertificate::query()->create([
            'completion_id' => $completion->id,
            'user_id' => (int) $completion->user_id,
            'course_id' => $completion->course_id ? (int) $completion->course_id : null,
            'certificate_code' => $code,
            'status' => 'issued',
            'issued_at' => now(),
            'student_name' => Str::limit($studentName, 255, ''),
            'course_title' => Str::limit($courseTitle, 500, ''),
            'completion_date' => optional($completion->completed_at)->toDateString() ?: now()->toDateString(),
            'qr_payload' => $verifyUrl,
            'issued_by' => $issuer?->id,
            'issued_by_name' => $issuer
                ? trim((string) ($issuer->name ?? $issuer->email ?? 'Admin'))
                : (function_exists('audit_user_name') ? audit_user_name() : 'Admin'),
        ]);

        $pdfRel = $this->renderPdf($cert);
        $cert->pdf_path = $pdfRel;
        $cert->save();

        if ($completion->status !== 'certificate_issued') {
            $completion->status = 'certificate_issued';
            $completion->save();
        }

        return $cert->fresh();
    }

    public function nextCertificateCode(): string
    {
        LmsCertificate::ensureTable();
        $year = date('Y');
        $prefix = 'CERT-' . $year . '-';
        $last = LmsCertificate::query()
            ->where('certificate_code', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('certificate_code');

        $seq = 1;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
    }

    public function renderPdf(LmsCertificate $cert): string
    {
        $dir = public_path('certificates');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $qrPath = $this->ensureQrImage($cert);
        $qrForPdf = null;
        if ($qrPath && is_file($qrPath)) {
            $qrForPdf = str_replace('\\', '/', $qrPath);
            if (! str_starts_with($qrForPdf, 'file://')) {
                $qrForPdf = 'file:///' . ltrim($qrForPdf, '/');
            }
        }
        $html = view('certificates.pdf', [
            'certificate' => $cert,
            'qrAbsolutePath' => $qrPath,
            'qrSrc' => $qrForPdf,
            'schoolName' => 'Berkeley School of Business, Arts & Sciences',
        ])->render();

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'landscape');
        $fileName = Str::slug($cert->certificate_code) . '.pdf';
        $absolute = $dir . DIRECTORY_SEPARATOR . $fileName;
        File::put($absolute, $pdf->output());

        return 'certificates/' . $fileName;
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
}
