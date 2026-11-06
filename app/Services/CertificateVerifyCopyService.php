<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class CertificateVerifyCopyService
{
    public const FILE = 'ecert-verify-copy.json';

    public function get(): array
    {
        $defaults = [
            'title' => 'Verify Certificate',
            'html' => '<p>Anyone can verify a Berkeley e-certificate. Enter verifier details first. Student ID and Certificate Number stay locked until those details are submitted. Both IDs are required.</p>',
        ];

        try {
            if (! Storage::disk('local')->exists(self::FILE)) {
                return $defaults;
            }
            $raw = json_decode((string) Storage::disk('local')->get(self::FILE), true);
            if (! is_array($raw)) {
                return $defaults;
            }
            $title = trim((string) ($raw['title'] ?? ''));
            $html = trim((string) ($raw['html'] ?? ''));

            return [
                'title' => $title !== '' ? $title : $defaults['title'],
                'html' => $html !== '' ? $html : $defaults['html'],
            ];
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    public function save(?string $title, ?string $html): void
    {
        $payload = [
            'title' => trim((string) $title),
            'html' => strip_waf_captcha_html(trim((string) $html)),
        ];
        Storage::disk('local')->put(self::FILE, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
