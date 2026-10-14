<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiContentService
{
    public function __construct(
        private ContentEditorSettingsService $settings
    ) {
    }

    /**
     * @return array{ok:bool,content?:string,error?:string}
     */
    public function generate(
        string $mode,
        ?string $existingContent,
        ?string $topic,
        ?string $keywords,
        ?string $instructions,
        int $maxChars,
        bool $asHtml = true
    ): array {
        $ai = $this->settings->aiConfig();

        if (! ($ai['enabled'] ?? false)) {
            return ['ok' => false, 'error' => 'AI writing is disabled in Content Editor settings.'];
        }

        if (trim((string) ($ai['api_key'] ?? '')) === '') {
            return ['ok' => false, 'error' => 'OpenAI API key is not configured. Add it under Settings → Content Editor or OPENAI_API_KEY in .env.'];
        }

        $mode = strtolower(trim($mode));
        $allowed = ['write', 'rewrite', 'grammar', 'seo'];
        if (! in_array($mode, $allowed, true)) {
            return ['ok' => false, 'error' => 'Invalid AI mode.'];
        }

        $maxChars = max(50, min(20000, $maxChars));
        $system = $this->systemPrompt($asHtml, $maxChars);
        $user = $this->userPrompt($mode, $existingContent, $topic, $keywords, $instructions, $maxChars);

        try {
            $response = Http::withToken($ai['api_key'])
                ->timeout((int) ($ai['timeout'] ?? 60))
                ->acceptJson()
                ->post($ai['base_url'] . '/chat/completions', [
                    'model' => $ai['model'] ?: 'gpt-4o-mini',
                    'temperature' => $mode === 'grammar' ? 0.2 : 0.6,
                    'messages' => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ]);

            if (! $response->successful()) {
                $message = data_get($response->json(), 'error.message')
                    ?: ('AI provider error (HTTP ' . $response->status() . ').');
                Log::warning('AI content generation failed: ' . $message);

                return ['ok' => false, 'error' => $message];
            }

            $content = trim((string) data_get($response->json(), 'choices.0.message.content', ''));
            if ($content === '') {
                return ['ok' => false, 'error' => 'AI returned empty content.'];
            }

            $content = $this->stripCodeFences($content);
            $content = $this->enforceLimit($content, $maxChars, $asHtml);

            return ['ok' => true, 'content' => $content];
        } catch (\Throwable $e) {
            Log::warning('AI content generation exception: ' . $e->getMessage());

            return ['ok' => false, 'error' => 'AI request failed: ' . $e->getMessage()];
        }
    }

    private function systemPrompt(bool $asHtml, int $maxChars): string
    {
        $format = $asHtml
            ? 'Return HTML suitable for a rich text editor (use <p>, <ul>, <ol>, <strong>, <em>, <a> when helpful). Do not wrap the answer in markdown code fences.'
            : 'Return plain text only. Do not use markdown code fences.';

        return "You are an expert education-industry content writer for EduBerkeley. "
            . "Write clear, professional, SEO-friendly copy for website pages and courses. "
            . "Hard limit: the final output must be at most {$maxChars} characters of readable text "
            . '(count characters of visible text; keep HTML compact). '
            . $format
            . ' Do not invent unverifiable claims or fake accreditation details.';
    }

    private function userPrompt(
        string $mode,
        ?string $existingContent,
        ?string $topic,
        ?string $keywords,
        ?string $instructions,
        int $maxChars
    ): string {
        $parts = [
            'Mode: ' . $mode,
            'Maximum characters (plain text): ' . $maxChars,
        ];

        if ($topic) {
            $parts[] = 'Topic / focus: ' . trim($topic);
        }
        if ($keywords) {
            $parts[] = 'SEO keywords to include naturally: ' . trim($keywords);
        }
        if ($instructions) {
            $parts[] = 'Extra instructions: ' . trim($instructions);
        }

        $existing = trim((string) $existingContent);
        if ($existing !== '') {
            $parts[] = "Existing content:\n" . Str::limit($existing, 12000, '…');
        }

        $parts[] = match ($mode) {
            'write' => 'Generate new content from scratch for this field.',
            'rewrite' => 'Rewrite the existing content. Keep the meaning, improve clarity and flow.',
            'grammar' => 'Fix grammar, spelling, and punctuation. Keep meaning and structure. Do not add marketing fluff.',
            'seo' => 'Optimize the content for SEO: clearer headings/sentences, natural keyword use, stronger first paragraph, scannable structure. Keep factual accuracy.',
            default => 'Improve the content.',
        };

        return implode("\n\n", $parts);
    }

    private function stripCodeFences(string $content): string
    {
        $content = preg_replace('/^```(?:html|markdown|text)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;

        return trim($content);
    }

    private function enforceLimit(string $content, int $maxChars, bool $asHtml): string
    {
        $plain = $asHtml ? trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : $content;
        if (mb_strlen($plain) <= $maxChars) {
            return $content;
        }

        if (! $asHtml) {
            return mb_substr($content, 0, $maxChars);
        }

        // Soft trim: keep HTML but shrink plain text by truncating last text nodes via plain fallback.
        $trimmed = mb_substr($plain, 0, $maxChars);

        return '<p>' . htmlspecialchars($trimmed, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</p>';
    }
}
