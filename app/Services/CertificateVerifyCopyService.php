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
            'html' => '',
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
                'html' => $html,
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
