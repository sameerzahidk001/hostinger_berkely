<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Page;
use App\Models\PagesSEO;
use Illuminate\Support\Str;

class SeoAnalyzerService
{
    public function analyze(PagesSEO $seo): array
    {
        $title = trim((string) $seo->title);
        $description = trim((string) $seo->meta_description);
        $focusKeyword = $this->focusKeyword($seo);
        $urlSlug = $this->resolveUrlSlug($seo);
        $previewUrl = $this->resolvePreviewUrl($seo);
        $content = $this->resolveContent($seo);
        $plainText = $this->stripToText($content);
        $wordCount = $this->wordCount($plainText);
        $keywordCount = $focusKeyword ? $this->keywordOccurrences($plainText, $focusKeyword) : 0;
        $density = $wordCount > 0 ? round(($keywordCount / $wordCount) * 100, 2) : 0.0;

        $basic = [
            $this->check($focusKeyword !== '', 'Focus keyword is set.'),
            $this->check(
                $focusKeyword !== '' && Str::contains(Str::lower($title), Str::lower($focusKeyword)),
                'Focus keyword used in the SEO title.'
            ),
            $this->check(
                $focusKeyword !== '' && Str::contains(Str::lower($description), Str::lower($focusKeyword)),
                'Focus keyword used in the meta description.'
            ),
            $this->check(
                $focusKeyword !== '' && Str::contains(Str::lower($urlSlug), Str::lower(str_replace(' ', '-', $focusKeyword))),
                'Focus keyword used in the URL.'
            ),
            $this->check(
                $focusKeyword === '' || $this->keywordInFirstTenPercent($plainText, $focusKeyword),
                'Focus keyword appears in the first 10% of the content.'
            ),
            $this->check(
                $focusKeyword === '' || Str::contains(Str::lower($plainText), Str::lower($focusKeyword)),
                'Focus keyword found in the content.'
            ),
            $this->check($wordCount >= 300, 'Content length is ' . number_format($wordCount) . ' words.' . ($wordCount < 300 ? ' (aim for 300+)' : '')),
        ];

        $additional = [
            $this->check(
                $focusKeyword === '' || $this->keywordInSubheadings($content, $focusKeyword),
                'Focus keyword found in subheadings (H2/H3).'
            ),
            $this->check(strlen($urlSlug) <= 75, 'URL length is ' . strlen($urlSlug) . ' characters.' . (strlen($urlSlug) > 75 ? ' (keep under 75)' : '')),
            $this->check($this->hasExternalLinks($content), 'Linking to external resources.'),
            $this->check($this->hasInternalLinks($content), 'Internal links found in content.'),
            $this->check(
                $focusKeyword === '' || $this->keywordInImageAlt($content, $focusKeyword),
                'Image with focus keyword as alt text.'
            ),
            $this->check(
                $focusKeyword === '' || ($density >= 0.5 && $density <= 2.5),
                'Keyword density is ' . $density . '%' . ($focusKeyword !== '' ? ' (' . $keywordCount . ' times)' : '') . '.'
            ),
            $this->check($title !== '' && $description !== '', 'Title and meta description are set.'),
            $this->check(
                $title === '' || (strlen($title) >= 30 && strlen($title) <= 60),
                'SEO title length is ' . strlen($title) . ' characters (ideal 30–60).'
            ),
            $this->check(
                $description === '' || (strlen($description) >= 120 && strlen($description) <= 160),
                'Meta description length is ' . strlen($description) . ' characters (ideal 120–160).'
            ),
        ];

        $allChecks = array_merge($basic, $additional);
        $passed = count(array_filter($allChecks, fn ($c) => $c['ok']));
        $score = (int) round(($passed / max(count($allChecks), 1)) * 100);

        return [
            'score' => $score,
            'label' => $this->scoreLabel($score),
            'focus_keyword' => $focusKeyword,
            'word_count' => $wordCount,
            'keyword_density' => $density,
            'keyword_count' => $keywordCount,
            'preview_url' => $previewUrl,
            'url_slug' => $urlSlug,
            'basic' => $basic,
            'additional' => $additional,
            'basic_errors' => count(array_filter($basic, fn ($c) => ! $c['ok'])),
            'additional_errors' => count(array_filter($additional, fn ($c) => ! $c['ok'])),
            'external_links' => $this->countLinks($content, 'external'),
            'internal_links' => $this->countLinks($content, 'internal'),
            'schema' => 'Article',
        ];
    }

    public function focusKeyword(PagesSEO $seo): string
    {
        $keywords = trim((string) $seo->keywords);
        if ($keywords === '') {
            return '';
        }

        $parts = preg_split('/[,;|]+/', $keywords);

        return trim((string) ($parts[0] ?? ''));
    }

    private function resolveUrlSlug(PagesSEO $seo): string
    {
        if ($seo->page_id && $seo->relationLoaded('page') && $seo->page) {
            return (string) ($seo->page->full_url ?? $seo->page->url ?? '');
        }

        if ($seo->course_id && $seo->relationLoaded('course') && $seo->course) {
            return (string) ($seo->course->slug ?? '');
        }

        $seo->loadMissing(['page', 'course']);

        if ($seo->page) {
            return (string) ($seo->page->full_url ?? $seo->page->url ?? '');
        }

        if ($seo->course) {
            return (string) ($seo->course->slug ?? '');
        }

        return '';
    }

    private function resolvePreviewUrl(PagesSEO $seo): string
    {
        $slug = $this->resolveUrlSlug($seo);

        return $slug !== '' ? rtrim(config('app.url'), '/') . '/' . ltrim($slug, '/') : url('/');
    }

    private function resolveContent(PagesSEO $seo): string
    {
        $seo->loadMissing(['page.sections', 'course.dynamicLabel']);

        $content = '';

        if ($seo->page) {
            $content = $this->extractPageContent($seo->page);
        } elseif ($seo->course) {
            $content = $this->extractCourseContent($seo->course);
        }

        if (trim((string) $seo->thumbnail_alt) !== '') {
            $content .= ' <img alt="' . e($seo->thumbnail_alt) . '">';
        }

        return $content;
    }

    private function extractPageContent(Page $page): string
    {
        $chunks = [$page->page_name ?? ''];

        foreach ($page->sections ?? [] as $section) {
            $data = is_string($section->data) ? json_decode($section->data, true) : $section->data;
            $chunks[] = $this->flattenContent($data);
            $chunks[] = $this->extractCmsSeoSignals($data);
        }

        return implode(' ', array_filter($chunks));
    }

    private function extractCourseContent(Course $course): string
    {
        $fields = [
            $course->title,
            $course->short_description,
            $course->description,
            $course->course_structure_overview,
            $course->who_can_do,
            $course->eligibility,
            $course->benifits,
            $course->custom_section_01_description,
        ];

        $content = $this->flattenContent($fields);
        $content .= ' ' . $this->extractStoredImageAlts($course->image_alts, $course->title);

        if ($course->dynamicLabel) {
            $content .= ' ' . $this->extractStoredImageAlts($course->dynamicLabel->image_alts, $course->title);
        }

        return trim($content);
    }

    /**
     * CMS sections store headings and image alt text in JSON fields, not as HTML.
     * Synthesize tags so subheading and alt checks match what the frontend renders.
     */
    private function extractCmsSeoSignals(mixed $data, bool $isNestedItem = false): string
    {
        if (! is_array($data)) {
            return '';
        }

        $signals = [];

        $title = trim((string) ($data['title'] ?? ''));
        if ($title !== '') {
            $tag = $isNestedItem ? 'h3' : 'h2';
            $signals[] = '<' . $tag . '>' . e($title) . '</' . $tag . '>';
        }

        $subtitle = trim((string) ($data['subtitle'] ?? ''));
        if ($subtitle !== '') {
            $signals[] = '<h3>' . e($subtitle) . '</h3>';
        }

        if (! empty($data['image']) || trim((string) ($data['image_alt'] ?? '')) !== '') {
            $signals[] = '<img alt="' . e(image_alt($data['image_alt'] ?? null, $data['title'] ?? null)) . '">';
        }

        if (! empty($data['icon']) || trim((string) ($data['icon_alt'] ?? '')) !== '') {
            $signals[] = '<img alt="' . e(image_alt($data['icon_alt'] ?? null, ($data['title'] ?? 'Icon') . ' icon')) . '">';
        }

        foreach (['cards', 'schools', 'testimonials', 'courses', 'items', 'logos'] as $listKey) {
            foreach ($data[$listKey] ?? [] as $item) {
                if (is_array($item)) {
                    $signals[] = $this->extractCmsSeoSignals($item, true);
                }
            }
        }

        return implode(' ', array_filter($signals));
    }

    private function extractStoredImageAlts(mixed $alts, ?string $fallback = null): string
    {
        $parsed = is_array($alts) ? $alts : (json_decode((string) $alts, true) ?: []);

        if (! is_array($parsed)) {
            return '';
        }

        $signals = [];
        foreach ($parsed as $alt) {
            $effective = image_alt(is_string($alt) ? $alt : null, $fallback);
            if ($effective !== 'Image') {
                $signals[] = '<img alt="' . e($effective) . '">';
            }
        }

        return implode(' ', $signals);
    }

    private function flattenContent(mixed $data): string
    {
        if (is_null($data)) {
            return '';
        }

        if (is_string($data)) {
            return $data;
        }

        if (is_numeric($data)) {
            return (string) $data;
        }

        if (! is_array($data)) {
            return '';
        }

        $parts = [];
        foreach ($data as $value) {
            $parts[] = $this->flattenContent($value);
        }

        return implode(' ', array_filter($parts));
    }

    private function stripToText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    private function wordCount(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        return count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);
    }

    private function keywordOccurrences(string $text, string $keyword): int
    {
        if ($keyword === '' || $text === '') {
            return 0;
        }

        return substr_count(Str::lower($text), Str::lower($keyword));
    }

    private function keywordInFirstTenPercent(string $text, string $keyword): bool
    {
        if ($text === '' || $keyword === '') {
            return false;
        }

        $length = max((int) floor(strlen($text) * 0.1), 1);

        return Str::contains(Str::lower(substr($text, 0, $length)), Str::lower($keyword));
    }

    private function keywordInSubheadings(string $html, string $keyword): bool
    {
        if (! preg_match_all('/<h[2-3][^>]*>(.*?)<\/h[2-3]>/is', $html, $matches)) {
            return false;
        }

        foreach ($matches[1] as $heading) {
            if (Str::contains(Str::lower($this->stripToText($heading)), Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function keywordInImageAlt(string $html, string $keyword): bool
    {
        if (! preg_match_all('/<img[^>]+alt=["\']([^"\']*)["\'][^>]*>/i', $html, $matches)) {
            return false;
        }

        foreach ($matches[1] as $alt) {
            if (Str::contains(Str::lower($alt), Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function hasExternalLinks(string $html): bool
    {
        return $this->countLinks($html, 'external') > 0;
    }

    private function hasInternalLinks(string $html): bool
    {
        return $this->countLinks($html, 'internal') > 0;
    }

    private function countLinks(string $html, string $type): int
    {
        if (! preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $html, $matches)) {
            return 0;
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $count = 0;

        foreach ($matches[1] as $href) {
            $isExternal = Str::startsWith($href, 'http') && $host && ! Str::contains($href, $host);
            $isInternal = ! Str::startsWith($href, 'http') || ($host && Str::contains($href, $host));

            if ($type === 'external' && $isExternal) {
                $count++;
            }

            if ($type === 'internal' && $isInternal) {
                $count++;
            }
        }

        return $count;
    }

    private function check(bool $ok, string $text): array
    {
        return ['ok' => $ok, 'text' => $text];
    }

    private function scoreLabel(int $score): string
    {
        if ($score >= 80) {
            return 'Excellent';
        }

        if ($score >= 50) {
            return 'Good';
        }

        return 'Needs work';
    }
}
