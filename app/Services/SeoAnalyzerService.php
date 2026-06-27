<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Page;
use App\Models\PagesSEO;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SeoAnalyzerService
{
    public function analyze(PagesSEO $seo, bool $forListing = false, bool $lightContent = false): array
    {
        $title = trim((string) $seo->title);
        $description = trim((string) $seo->meta_description);
        $focusKeyword = $this->focusKeyword($seo);
        $urlSlug = $this->resolveUrlSlug($seo);
        $previewUrl = $this->resolvePreviewUrl($seo);
        $content = ($forListing || $lightContent)
            ? $this->resolveContentForListing($seo)
            : $this->resolveContent($seo);
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
        $technical = $forListing
            ? $this->technicalChecksForListing($seo, $title, $description)
            : $this->technicalChecks($seo, $previewUrl, $title, $description, $focusKeyword);

        $contentChecks = array_merge($basic, $additional);
        $contentPassed = count(array_filter($contentChecks, fn ($c) => $c['ok']));
        $contentScore = (int) round(($contentPassed / max(count($contentChecks), 1)) * 100);
        $technicalPassed = count(array_filter($technical, fn ($c) => $c['ok']));
        $liveScore = (int) round(($technicalPassed / max(count($technical), 1)) * 100);

        $score = $this->weightedScore([
            ['checks' => $basic, 'weight' => 25],
            ['checks' => $additional, 'weight' => 25],
            ['checks' => $technical, 'weight' => 50],
        ]);

        return [
            'score' => $score,
            'label' => $this->scoreLabel($score),
            'content_score' => $contentScore,
            'live_score' => $liveScore,
            'listing_fast' => $forListing,
            'focus_keyword' => $focusKeyword,
            'word_count' => $wordCount,
            'keyword_density' => $density,
            'keyword_count' => $keywordCount,
            'preview_url' => $previewUrl,
            'url_slug' => $urlSlug,
            'basic' => $basic,
            'additional' => $additional,
            'technical' => $technical,
            'basic_errors' => count(array_filter($basic, fn ($c) => ! $c['ok'])),
            'additional_errors' => count(array_filter($additional, fn ($c) => ! $c['ok'])),
            'technical_errors' => count(array_filter($technical, fn ($c) => ! $c['ok'])),
            'external_links' => $this->countLinks($content, 'external'),
            'internal_links' => $this->countLinks($content, 'internal'),
            'schema' => 'Article',
        ];
    }

    /**
     * Score for admin tables — same formula as edit, but skips live HTTP (uses cached full analysis when available).
     */
    public function analyzeForListing(PagesSEO $seo): array
    {
        $cacheStamp = $seo->updated_at?->getTimestamp() ?? 0;
        $fullCacheKey = 'seo_full_analysis:' . $seo->id . ':' . $cacheStamp;

        if ($cached = Cache::get($fullCacheKey)) {
            return $cached;
        }

        $listingCacheKey = 'seo_listing_analysis:' . $seo->id . ':' . $cacheStamp;

        return Cache::remember($listingCacheKey, now()->addHours(6), function () use ($seo) {
            return $this->analyze($seo, true, false);
        });
    }

    /**
     * Full score with live page checks (single record — edit screen).
     */
    public function analyzeForEdit(PagesSEO $seo): array
    {
        $cacheStamp = $seo->updated_at?->getTimestamp() ?? 0;
        $fullCacheKey = 'seo_full_analysis:' . $seo->id . ':' . $cacheStamp;

        return Cache::remember($fullCacheKey, now()->addMinutes(30), function () use ($seo, $cacheStamp) {
            $result = $this->analyze($seo, false, false);
            Cache::put('seo_listing_analysis:' . $seo->id . ':' . $cacheStamp, $result, now()->addHours(6));

            return $result;
        });
    }

    public function clearAnalysisCache(PagesSEO $seo): void
    {
        $cacheStamp = $seo->updated_at?->getTimestamp() ?? 0;
        Cache::forget('seo_full_analysis:' . $seo->id . ':' . $cacheStamp);
        Cache::forget('seo_listing_analysis:' . $seo->id . ':' . $cacheStamp);
        $this->clearLivePageCache($seo);
    }

    public function focusKeyword(PagesSEO $seo): string
    {
        return seo_focus_keyword($seo);
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

        if ($slug === '') {
            return url('/');
        }

        if ($seo->course_id || ($seo->relationLoaded('course') && $seo->course)) {
            return route('course.details', ['course' => $slug]);
        }

        return rtrim(config('app.url'), '/') . '/' . ltrim($slug, '/');
    }

    private function resolveContent(PagesSEO $seo): string
    {
        $seo->loadMissing(['page.sections', 'course.dynamicLabel', 'course.courseFaq']);

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

    /**
     * Lightweight content for admin list screens (avoids loading every page section).
     */
    private function resolveContentForListing(PagesSEO $seo): string
    {
        $seo->loadMissing(['page', 'course']);

        if ($seo->page) {
            return trim(implode(' ', array_filter([
                $seo->page->page_name ?? '',
                $seo->title ?? '',
                $seo->meta_description ?? '',
                $seo->keywords ?? '',
            ])));
        }

        if ($seo->course) {
            return trim(implode(' ', array_filter([
                $seo->course->title ?? '',
                $seo->course->short_description ?? '',
                $this->stripToText((string) ($seo->course->description ?? '')),
                $seo->title ?? '',
                $seo->meta_description ?? '',
                $seo->keywords ?? '',
            ])));
        }

        return trim(implode(' ', array_filter([
            $seo->title ?? '',
            $seo->meta_description ?? '',
            $seo->keywords ?? '',
        ])));
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

    private function weightedScore(array $sections): int
    {
        $total = 0.0;

        foreach ($sections as $section) {
            $checks = $section['checks'] ?? [];
            $weight = (float) ($section['weight'] ?? 0);
            $passed = count(array_filter($checks, fn ($c) => $c['ok']));
            $total += ($passed / max(count($checks), 1)) * $weight;
        }

        return (int) round(min(100, max(0, $total)));
    }

    private function technicalChecksForListing(PagesSEO $seo, string $title, string $description): array
    {
        return [
            $this->check($title !== '', 'SEO title saved in admin.'),
            $this->check($description !== '', 'Meta description saved in admin.'),
            $this->check($seo->course_id !== null || $seo->page_id !== null, 'SEO record linked to a page or course.'),
        ];
    }

    private function technicalChecks(
        PagesSEO $seo,
        string $previewUrl,
        string $title,
        string $description,
        string $focusKeyword
    ): array {
        $html = $this->fetchLivePageHtml($previewUrl);

        if ($html === null || $html === '') {
            return [
                $this->check(false, 'Could not fetch the live public page for verification.'),
                $this->check($title !== '', 'SEO title saved in admin (live page not verified).'),
                $this->check($description !== '', 'Meta description saved in admin (live page not verified).'),
                $this->check($seo->course_id !== null || $seo->page_id !== null, 'SEO record linked to a page or course.'),
            ];
        }

        $live = $this->parseLivePage($html, $focusKeyword);

        $titleMatches = $title === ''
            || $live['title'] === ''
            || Str::contains(Str::lower($live['title']), Str::lower(Str::limit($title, 30, '')));

        $checks = [
            $this->check(
                $live['title'] !== '',
                $live['title'] !== ''
                    ? 'Live page has a <title> tag.'
                    : 'Live page is missing a <title> tag (critical).'
            ),
            $this->check(
                $live['meta_description'] !== '',
                $live['meta_description'] !== ''
                    ? 'Live page has a meta description.'
                    : 'Live page is missing a meta description (critical).'
            ),
            $this->check($titleMatches, 'Live <title> matches the saved SEO title.'),
            $this->check(
                $live['h1_count'] === 1,
                'Live page has one primary H1 (' . $live['h1_count'] . ' found; aim for 1).'
            ),
            $this->check(
                $focusKeyword === '' || $live['h1_has_keyword'],
                'Primary H1 on the live page includes the focus keyword.'
            ),
            $this->check(
                $live['image_alt_coverage'] >= 60,
                'Image alt coverage on the live page is ' . $live['image_alt_coverage'] . '% (aim for 60%+).'
            ),
            $this->check(
                $live['text_ratio'] >= 8,
                'Text-to-HTML ratio is ' . $live['text_ratio'] . '% (aim for 8%+).'
            ),
            $this->check(
                $live['internal_links'] >= 2,
                'Live page has ' . $live['internal_links'] . ' internal links (aim for 2+).'
            ),
        ];

        if ($seo->course_id) {
            $checks[] = $this->check(
                $live['has_cta'],
                'Clear call-to-action buttons found on the live course page.'
            );
            $checks[] = $this->check(
                $live['has_faq'],
                'FAQ section detected on the live course page.'
            );
        }

        return $checks;
    }

    public function clearLivePageCache(PagesSEO $seo): void
    {
        $url = $this->resolvePreviewUrl($seo);
        if ($url !== '') {
            Cache::forget($this->livePageCacheKey($url));
        }
    }

    private function livePageCacheKey(string $url): string
    {
        return 'seo_live_html:' . md5($url);
    }
    private function fetchLivePageHtml(string $url): ?string
    {
        if ($url === '' || ! str_starts_with($url, 'http')) {
            return null;
        }

        return Cache::remember($this->livePageCacheKey($url), now()->addHour(), function () use ($url) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'BerkeleySEOChecker/1.0'])
                    ->get($url);

                if ($response->successful()) {
                    return (string) $response->body();
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('SEO live page fetch failed: ' . $e->getMessage());
            }

            return null;
        });
    }

    private function parseLivePage(string $html, string $focusKeyword = ''): array
    {
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $title = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $metaDescription = '';
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $matches)) {
            $metaDescription = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        } elseif (preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']/i', $html, $matches)) {
            $metaDescription = trim(html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $h1Matches);
        $h1Texts = array_map(fn ($chunk) => $this->stripToText($chunk), $h1Matches[1] ?? []);
        $h1Count = count($h1Texts);

        $h1HasKeyword = $focusKeyword === '';
        if ($focusKeyword !== '') {
            foreach ($h1Texts as $h1Text) {
                if (Str::contains(Str::lower($h1Text), Str::lower($focusKeyword))) {
                    $h1HasKeyword = true;
                    break;
                }
            }
        }

        preg_match_all('/<img\b[^>]*>/i', $html, $imgMatches);
        $imgTags = $imgMatches[0] ?? [];
        $imagesWithAlt = 0;

        foreach ($imgTags as $tag) {
            if (preg_match('/\balt=["\']([^"\']*)["\']/i', $tag, $altMatch)) {
                $alt = trim($altMatch[1]);
                if ($alt !== '' && Str::lower($alt) !== 'image') {
                    $imagesWithAlt++;
                }
            }
        }

        $imageAltCoverage = count($imgTags) > 0
            ? (int) round(($imagesWithAlt / count($imgTags)) * 100)
            : 100;

        $plainText = $this->stripToText($html);
        $textRatio = strlen($html) > 0
            ? round((strlen($plainText) / strlen($html)) * 100, 1)
            : 0.0;

        return [
            'title' => $title,
            'meta_description' => $metaDescription,
            'h1_count' => $h1Count,
            'h1_has_keyword' => $h1HasKeyword,
            'image_alt_coverage' => $imageAltCoverage,
            'text_ratio' => $textRatio,
            'internal_links' => $this->countLinks($html, 'internal'),
            'has_cta' => (bool) preg_match(
                '/\b(enroll|register|apply now|contact|admission|download brochure)\b/i',
                $html
            ),
            'has_faq' => (bool) preg_match('/\bfaq\b/i', $html) || str_contains($html, 'id="eleven"'),
        ];
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
