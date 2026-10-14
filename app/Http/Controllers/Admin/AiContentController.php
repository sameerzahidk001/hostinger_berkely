<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiContentService;
use App\Services\ContentEditorSettingsService;
use Illuminate\Http\Request;

class AiContentController extends Controller
{
    public function generate(
        Request $request,
        AiContentService $ai,
        ContentEditorSettingsService $settings
    ) {
        $data = $request->validate([
            'mode' => 'required|string|in:write,rewrite,grammar,seo',
            'module' => 'required|string|in:pages,courses',
            'field_key' => 'nullable|string|max:100',
            'content' => 'nullable|string|max:50000',
            'topic' => 'nullable|string|max:500',
            'keywords' => 'nullable|string|max:500',
            'instructions' => 'nullable|string|max:1000',
            'as_html' => 'nullable|boolean',
            'max_chars' => 'nullable|integer|min:50|max:20000',
        ]);

        $fieldKey = $data['field_key'] ?? 'default';
        $configuredMax = $settings->maxFor($data['module'], $fieldKey);
        $maxChars = min($configuredMax, (int) ($data['max_chars'] ?? $configuredMax));

        $result = $ai->generate(
            $data['mode'],
            $data['content'] ?? null,
            $data['topic'] ?? null,
            $data['keywords'] ?? null,
            $data['instructions'] ?? null,
            $maxChars,
            (bool) ($data['as_html'] ?? true)
        );

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'AI generation failed.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'content' => $result['content'],
            'max_chars' => $maxChars,
            'plain_length' => mb_strlen(trim(html_entity_decode(strip_tags($result['content']), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
        ]);
    }
}
