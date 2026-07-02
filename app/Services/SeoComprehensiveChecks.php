<?php

namespace App\Services;

use App\Models\PagesSEO;
use Illuminate\Support\Str;

class SeoComprehensiveChecks
{
    /** @var array<string, int> */
    public const WEIGHTS = [
        'keyword_optimization' => 20,
        'content_quality' => 15,
        'ux_readability' => 5,
        'title_meta' => 10,
        'headings' => 10,
        'links' => 10,
        'image_seo' => 10,
        'technical_seo' => 10,
        'performance' => 5,
        'mobile_seo' => 3,
        'schema_rich' => 2,
    ];

    /** @var array<string, string> */
    public const SECTION_LABELS = [
        'basic_seo' => 'Basic SEO',
        'content_seo' => 'Content SEO',
        'technical_seo' => 'Technical SEO',
        'image_seo' => 'Image SEO',
        'ux_readability' => 'UX & Readability',
        'advanced_seo' => 'Advanced SEO',
    ];

    public function build(
        PagesSEO $seo,
        string $title,
        string $description,
        string $focusKeyword,
        string $priorityKeywords,
        string $urlSlug,
        string $contentHtml,
        string $plainText,
        int $wordCount,
        ?string $liveHtml = null,
        bool $includeLive = true
    ): array {
        $live = $liveHtml ? $this->parseLiveDocument($liveHtml, $focusKeyword) : null;
        $density = $wordCount > 0 && $focusKeyword !== ''
            ? round(($this->keywordOccurrences($plainText, $focusKeyword) / $wordCount) * 100, 2)
            : 0.0;
        $readability = $this->readabilityMetrics($plainText);
        $headings = $this->headingMetrics($contentHtml);
        $images = $this->imageMetrics($contentHtml, $live);
        $links = $this->linkMetrics($contentHtml, $liveHtml ?? $contentHtml);

        $keywordChecks = $this->keywordChecks($title, $description, $focusKeyword, $priorityKeywords, $urlSlug, $plainText, $contentHtml, $wordCount, $density, $images);
        $titleMetaChecks = $this->titleMetaChecks($seo, $title, $description, $focusKeyword);
        $contentChecks = $this->contentQualityChecks($plainText, $wordCount, $contentHtml, $seo, $readability);
        $headingChecks = $this->headingChecks($headings, $focusKeyword, $contentHtml, $live);
        $linkChecks = $this->linkChecks($links, $contentHtml);
        $imageChecks = $this->imageChecks($images);
        $technicalChecks = $this->technicalChecks($urlSlug, $live, $includeLive);
        $performanceChecks = $this->performanceChecks($liveHtml, $live);
        $mobileChecks = $this->mobileChecks($live, $includeLive);
        $schemaChecks = $this->schemaChecks($live, $seo, $includeLive);
        $uxChecks = $this->uxReadabilityChecks($readability, $plainText);

        $weightedGroups = [
            ['key' => 'keyword_optimization', 'checks' => $keywordChecks],
            ['key' => 'title_meta', 'checks' => $titleMetaChecks],
            ['key' => 'content_quality', 'checks' => $contentChecks],
            ['key' => 'ux_readability', 'checks' => $uxChecks],
            ['key' => 'headings', 'checks' => $headingChecks],
            ['key' => 'links', 'checks' => $linkChecks],
            ['key' => 'image_seo', 'checks' => $imageChecks],
            ['key' => 'technical_seo', 'checks' => $technicalChecks],
            ['key' => 'performance', 'checks' => $performanceChecks],
            ['key' => 'mobile_seo', 'checks' => $mobileChecks],
            ['key' => 'schema_rich', 'checks' => $schemaChecks],
        ];

        $overall = $this->weightedScore($weightedGroups);

        $sections = [
            [
                'key' => 'basic_seo',
                'title' => self::SECTION_LABELS['basic_seo'],
                'checks' => array_merge($keywordChecks, $titleMetaChecks),
                'score' => $this->sectionScore(array_merge($keywordChecks, $titleMetaChecks)),
            ],
            [
                'key' => 'content_seo',
                'title' => self::SECTION_LABELS['content_seo'],
                'checks' => $contentChecks,
                'score' => $this->sectionScore($contentChecks),
            ],
            [
                'key' => 'ux_readability',
                'title' => self::SECTION_LABELS['ux_readability'],
                'checks' => $uxChecks,
                'score' => $this->sectionScore($uxChecks),
            ],
            [
                'key' => 'headings',
                'title' => 'Heading Structure',
                'checks' => $headingChecks,
                'score' => $this->sectionScore($headingChecks),
            ],
            [
                'key' => 'links',
                'title' => 'Internal & External Links',
                'checks' => $linkChecks,
                'score' => $this->sectionScore($linkChecks),
            ],
            [
                'key' => 'image_seo',
                'title' => self::SECTION_LABELS['image_seo'],
                'checks' => $imageChecks,
                'score' => $this->sectionScore($imageChecks),
            ],
            [
                'key' => 'technical_seo',
                'title' => self::SECTION_LABELS['technical_seo'],
                'checks' => array_merge($technicalChecks, $performanceChecks, $mobileChecks),
                'score' => $this->sectionScore(array_merge($technicalChecks, $performanceChecks, $mobileChecks)),
            ],
            [
                'key' => 'advanced_seo',
                'title' => self::SECTION_LABELS['advanced_seo'],
                'checks' => $schemaChecks,
                'score' => $this->sectionScore($schemaChecks),
            ],
        ];

        return [
            'score' => $overall,
            'label' => $this->scoreLabel($overall),
            'sections' => $sections,
            'keyword_optimization' => $keywordChecks,
            'title_meta' => $titleMetaChecks,
            'content_quality' => $contentChecks,
            'headings' => $headingChecks,
            'links' => $linkChecks,
            'image_seo' => $imageChecks,
            'technical_seo' => $technicalChecks,
            'performance' => $performanceChecks,
            'mobile_seo' => $mobileChecks,
            'schema_rich' => $schemaChecks,
            'ux_readability' => $uxChecks,
            'readability_score' => $readability['flesch'],
            'word_count' => $wordCount,
            'keyword_density' => $density,
            'keyword_count' => $focusKeyword !== '' ? $this->keywordOccurrences($plainText, $focusKeyword) : 0,
            'internal_links' => $links['internal'],
            'external_links' => $links['external'],
            'image_summary' => $images['summary'],
        ];
    }

    public function buildMetaOnly(PagesSEO $seo, string $title, string $description, string $focusKeyword): array
    {
        $keywordChecks = [
            $this->check($focusKeyword !== '', 'Focus keyword is set.'),
            $this->check($focusKeyword !== '' && $title !== '' && Str::contains(Str::lower($title), Str::lower($focusKeyword)), 'Focus keyword used in the SEO title.'),
            $this->check($focusKeyword !== '' && $description !== '' && Str::contains(Str::lower($description), Str::lower($focusKeyword)), 'Focus keyword used in the meta description.'),
        ];
        $titleMetaChecks = [
            $this->check($title !== '', 'SEO title is set.'),
            $this->check($description !== '', 'Meta description is set.'),
            $this->check($title === '' || (strlen($title) >= 30 && strlen($title) <= 60), 'SEO title length is ' . strlen($title) . ' characters (ideal 30–60).'),
            $this->check($description === '' || (strlen($description) >= 120 && strlen($description) <= 160), 'Meta description length is ' . strlen($description) . ' characters (ideal 120–160).'),
            $this->check($seo->course_id !== null || $seo->page_id !== null, 'SEO record linked to a page or course.'),
        ];

        $checks = array_merge($keywordChecks, $titleMetaChecks);
        $score = $this->sectionScore($checks);

        return [
            'score' => $score,
            'label' => $this->scoreLabel($score),
            'sections' => [
                ['key' => 'basic_seo', 'title' => self::SECTION_LABELS['basic_seo'], 'checks' => $checks, 'score' => $score],
            ],
            'word_count' => 0,
            'keyword_density' => 0,
            'internal_links' => 0,
            'external_links' => 0,
            'readability_score' => null,
            'image_summary' => null,
        ];
    }

    private function keywordChecks(
        string $title,
        string $description,
        string $focusKeyword,
        string $priorityKeywords,
        string $urlSlug,
        string $plainText,
        string $contentHtml,
        int $wordCount,
        float $density,
        array $images
    ): array {
        $kwSlug = Str::lower(str_replace(' ', '-', $focusKeyword));
        $related = $this->parseKeywordList($priorityKeywords);
        $relatedUsed = 0;
        foreach ($related as $term) {
            if ($term !== '' && Str::contains(Str::lower($plainText), Str::lower($term))) {
                $relatedUsed++;
            }
        }

        $longTailUsed = false;
        foreach ($related as $term) {
            if ($term !== '' && str_word_count($term) >= 3 && Str::contains(Str::lower($plainText), Str::lower($term))) {
                $longTailUsed = true;
                break;
            }
        }

        return [
            $this->check($focusKeyword !== '', 'Focus keyword is set.'),
            $this->check($focusKeyword === '' || Str::contains(Str::lower($title), Str::lower($focusKeyword)), 'Focus keyword used in the SEO title.'),
            $this->check($focusKeyword === '' || Str::contains(Str::lower($description), Str::lower($focusKeyword)), 'Focus keyword used in the meta description.'),
            $this->check($focusKeyword === '' || Str::contains(Str::lower($urlSlug), $kwSlug), 'Focus keyword used in the URL.'),
            $this->check($focusKeyword === '' || $this->keywordInFirstTenPercent($plainText, $focusKeyword), 'Focus keyword appears in the first 10% of the content.'),
            $this->check($focusKeyword === '' || $this->keywordInLastTenPercent($plainText, $focusKeyword), 'Focus keyword appears in the last 10% of the content.'),
            $this->check($focusKeyword === '' || Str::contains(Str::lower($plainText), Str::lower($focusKeyword)), 'Focus keyword found in the content.'),
            $this->check($focusKeyword === '' || $this->keywordInSubheadings($contentHtml, $focusKeyword), 'Focus keyword found in subheadings (H2/H3).'),
            $this->check($focusKeyword === '' || ($density >= 0.5 && $density <= 2.5), 'Keyword density is ' . $density . '% (ideal 0.5–2.5%).'),
            $this->check($focusKeyword === '' || $density <= 3.0, 'Keyword appears naturally (no stuffing; density ≤ 3%).'),
            $this->check($related === [] || $relatedUsed >= 1, 'Related / priority keywords used in content (' . $relatedUsed . ' found).'),
            $this->check($related === [] || $longTailUsed || $focusKeyword === '', 'Long-tail or multi-word keyword used in content.'),
            $this->check($focusKeyword === '' || $this->keywordInImageAlt($contentHtml, $focusKeyword), 'Focus keyword used in image alt text.'),
            $this->check($wordCount >= 300, 'Content length is ' . number_format($wordCount) . ' words (aim for 300+).'),
        ];
    }

    private function titleMetaChecks(PagesSEO $seo, string $title, string $description, string $focusKeyword): array
    {
        $dupTitle = PagesSEO::query()
            ->where('id', '!=', $seo->id)
            ->where('title', $title)
            ->where('title', '!=', '')
            ->exists();
        $dupMeta = PagesSEO::query()
            ->where('id', '!=', $seo->id)
            ->where('meta_description', $description)
            ->where('meta_description', '!=', '')
            ->exists();

        $ctaPattern = '/\b(enroll|register|apply|contact|learn more|book now|get started|discover|join)\b/i';
        $powerWords = '/\b(best|proven|certified|professional|exclusive|complete|ultimate|leading)\b/i';

        return [
            $this->check($title === '' || (strlen($title) >= 30 && strlen($title) <= 60), 'SEO title length is ' . strlen($title) . ' characters (ideal 30–60).'),
            $this->check($description === '' || (strlen($description) >= 120 && strlen($description) <= 160), 'Meta description length is ' . strlen($description) . ' characters (ideal 120–160).'),
            $this->check($focusKeyword === '' || $title === '' || Str::startsWith(Str::lower($title), Str::lower($focusKeyword)), 'SEO title starts with the focus keyword.'),
            $this->check($title === '' || preg_match('/\d/', $title), 'Numbers in title (recommended when relevant).'),
            $this->check($title === '' || preg_match($powerWords, $title), 'Power words detected in title.'),
            $this->check(! $dupTitle, 'No duplicate SEO title across other records.'),
            $this->check(! $dupMeta, 'No duplicate meta description across other records.'),
            $this->check($description === '' || preg_match($ctaPattern, $description), 'Meta description contains a call-to-action.'),
        ];
    }

    private function contentQualityChecks(string $plainText, int $wordCount, string $html, PagesSEO $seo, array $readability): array
    {
        $hasLists = (bool) preg_match('/<(ul|ol)\b/i', $html);
        $hasTable = (bool) preg_match('/<table\b/i', $html);
        $hasQuote = (bool) preg_match('/<(blockquote|q)\b/i', $html);
        $hasFaq = (bool) preg_match('/\bfaq\b/i', $html);
        $hasToc = (bool) preg_match('/\b(table of contents|toc|on this page)\b/i', $html)
            || (bool) preg_match('/<nav[^>]+class=["\'][^"\']*toc/i', $html);

        return [
            $this->check($wordCount >= 300, 'Sufficient content length (' . number_format($wordCount) . ' words).'),
            $this->check($wordCount >= 150, 'Thin content warning: ' . number_format($wordCount) . ' words (150+ recommended).'),
            $this->check($readability['avg_sentence_words'] <= 25, 'Average sentence length is ' . $readability['avg_sentence_words'] . ' words (aim ≤ 25).'),
            $this->check($readability['avg_paragraph_words'] <= 150, 'Average paragraph length is reasonable (' . $readability['avg_paragraph_words'] . ' words).'),
            $this->check($hasLists, 'Lists detected in content.'),
            $this->check($wordCount < 800 || $hasToc, 'Table of contents detected for long content (or page under 800 words).'),
            $this->check($hasTable, 'Tables detected in content (if applicable).'),
            $this->check($hasQuote, 'Quotes or blockquotes detected.'),
            $this->check($hasFaq, 'FAQ content detected.'),
            $this->check($seo->updated_at !== null, 'Content freshness: updated date available.'),
        ];
    }

    private function uxReadabilityChecks(array $readability, string $plainText): array
    {
        return [
            $this->check($readability['flesch'] >= 50, 'Readability score is ' . $readability['flesch'] . '/100 (aim 50+).'),
            $this->check($readability['passive_percent'] <= 20, 'Passive voice is ' . $readability['passive_percent'] . '% (aim ≤ 20%).'),
            $this->check($readability['transition_percent'] >= 15, 'Transition words used in ' . $readability['transition_percent'] . '% of sentences (aim 15%+).'),
            $this->check($readability['sentence_count'] >= 3, 'Enough sentences for readability analysis (' . $readability['sentence_count'] . ').'),
            $this->check($plainText !== '', 'Readable text content is available.'),
        ];
    }

    private function headingChecks(array $headings, string $focusKeyword, string $html, ?array $live): array
    {
        $h1Count = $live['h1_count'] ?? $headings['h1'];
        $h1HasKeyword = $live['h1_has_keyword'] ?? $this->keywordInH1($html, $focusKeyword);

        return [
            $this->check($h1Count === 1, 'One primary H1 on page (' . $h1Count . ' found).'),
            $this->check($focusKeyword === '' || $h1HasKeyword, 'Focus keyword used in H1 heading.'),
            $this->check($headings['h2'] >= 1, 'H2 headings present (' . $headings['h2'] . ').'),
            $this->check($headings['h3'] >= 0, 'H3 headings counted (' . $headings['h3'] . ').'),
            $this->check(! $headings['skipped_levels'], 'Heading hierarchy is correct (no skipped levels).'),
            $this->check($focusKeyword === '' || $this->keywordInSubheadings($html, $focusKeyword), 'Focus keyword found in H2/H3 headings.'),
            $this->check($headings['max_heading_len'] <= 70, 'Heading lengths are reasonable (max ' . $headings['max_heading_len'] . ' chars).'),
        ];
    }

    private function linkChecks(array $links, string $html): array
    {
        return [
            $this->check($links['internal'] >= 2, 'Internal links: ' . $links['internal'] . ' (aim 2+).'),
            $this->check($links['external'] >= 1, 'External links: ' . $links['external'] . ' (aim 1+).'),
            $this->check($links['unique_anchors'] >= 2, 'Link anchor text diversity (' . $links['unique_anchors'] . ' unique anchors).'),
            $this->check($links['external_blank'] >= 1 || $links['external'] === 0, 'External links open in a new tab when present.'),
            $this->check($links['external_nofollow'] >= 0, 'External nofollow usage reviewed (' . $links['external_nofollow'] . ' nofollow).'),
        ];
    }

    private function imageChecks(array $images): array
    {
        return [
            $this->check($images['total'] > 0, 'Images found: ' . $images['total'] . '.'),
            $this->check($images['missing_alt'] === 0, 'Missing alt attributes: ' . $images['missing_alt'] . '.'),
            $this->check($images['alt_coverage'] >= 80, 'Alt text coverage is ' . $images['alt_coverage'] . '% (aim 80%+).'),
            $this->check($images['optimized_names'] >= max(1, (int) floor($images['total'] * 0.5)), 'Optimized image filenames (' . $images['optimized_names'] . '/' . $images['total'] . ').'),
            $this->check($images['total'] === 0 || $images['lazy_loaded'] > 0, 'Lazy loading on images (' . $images['lazy_loaded'] . '/' . $images['total'] . ').'),
            $this->check($images['webp_count'] >= 0, 'WebP images: ' . $images['webp_count'] . '.'),
            $this->check($images['with_dimensions'] >= max(0, (int) floor($images['total'] * 0.5)), 'Width/height attributes on images (' . $images['with_dimensions'] . ').'),
            $this->check($images['oversized'] <= 2, 'Oversized image warnings: ' . $images['oversized'] . ' (aim ≤ 2).'),
        ];
    }

    private function technicalChecks(string $urlSlug, ?array $live, bool $includeLive): array
    {
        if (! $includeLive || $live === null) {
            return [
                $this->check($urlSlug === '' || $urlSlug === strtolower($urlSlug), 'URL uses lowercase letters.'),
                $this->check($urlSlug === '' || ! preg_match('/[^a-z0-9\-\/]/', $urlSlug), 'URL has no special characters.'),
                $this->check($urlSlug === '' || ! str_contains($urlSlug, '_'), 'URL uses hyphens instead of underscores.'),
                $this->check($urlSlug === '' || ! $this->urlHasStopWords($urlSlug), 'URL avoids common stop words (the, and, of).'),
                $this->check(substr_count(trim($urlSlug, '/'), '/') <= 4, 'URL depth is reasonable (' . substr_count(trim($urlSlug, '/'), '/') . ' levels).'),
                $this->check(strlen($urlSlug) <= 75, 'URL length is ' . strlen($urlSlug) . ' characters (≤ 75).'),
            ];
        }

        return [
            $this->check($live['https'], 'HTTPS enabled.'),
            $this->check($live['canonical'] !== '', 'Canonical URL exists.'),
            $this->check($live['robots'] !== 'noindex', 'Page is not blocked by robots noindex.'),
            $this->check($live['og_title'] !== '', 'Open Graph title tag present.'),
            $this->check($live['og_description'] !== '', 'Open Graph description present.'),
            $this->check($live['og_image'] !== '', 'Open Graph image present.'),
            $this->check($live['twitter_card'] !== '', 'Twitter Card tag present.'),
            $this->check($live['lang'] !== '', 'Language attribute on <html> present.'),
            $this->check($live['charset'], 'Charset meta tag present.'),
            $this->check($live['favicon'], 'Favicon linked.'),
            $this->check(! $live['mixed_content'], 'No mixed HTTP content detected.'),
            $this->check($urlSlug === '' || $urlSlug === strtolower($urlSlug), 'URL uses lowercase letters.'),
            $this->check(strlen($urlSlug) <= 75, 'URL length is ' . strlen($urlSlug) . ' characters.'),
        ];
    }

    private function performanceChecks(?string $liveHtml, ?array $live): array
    {
        $sizeKb = $liveHtml ? (int) round(strlen($liveHtml) / 1024) : 0;

        return [
            $this->check($live === null || $live['text_ratio'] >= 8, 'Text-to-HTML ratio is ' . ($live['text_ratio'] ?? '—') . '% (aim 8%+).'),
            $this->check($sizeKb === 0 || $sizeKb <= 2048, 'Estimated page HTML size is ' . $sizeKb . ' KB (aim ≤ 2048 KB).'),
            $this->check($live === null || $live['script_count'] <= 25, 'Script tags: ' . ($live['script_count'] ?? '—') . ' (aim ≤ 25).'),
            $this->check($live === null || $live['style_count'] <= 15, 'Stylesheet links: ' . ($live['style_count'] ?? '—') . ' (aim ≤ 15).'),
        ];
    }

    private function mobileChecks(?array $live, bool $includeLive): array
    {
        if (! $includeLive || $live === null) {
            return [
                $this->check(true, 'Mobile checks run when live page is scanned (open SEO Edit).'),
            ];
        }

        return [
            $this->check($live['viewport'], 'Mobile viewport meta tag present.'),
            $this->check($live['viewport'], 'Responsive viewport configured.'),
        ];
    }

    private function schemaChecks(?array $live, PagesSEO $seo, bool $includeLive): array
    {
        if (! $includeLive || $live === null) {
            return [
                $this->check($seo->course_id !== null || $seo->page_id !== null, 'Schema checks run on live page (open SEO Edit).'),
            ];
        }

        return [
            $this->check($live['schema_any'], 'Structured data (JSON-LD) detected.'),
            $this->check($live['schema_course'] || ! $seo->course_id, 'Course schema present (for course pages).'),
            $this->check($live['schema_faq'] || ! $this->seoHasFaqs($seo), 'FAQ schema detected (if FAQs exist).'),
            $this->check($live['schema_breadcrumb'], 'Breadcrumb schema detected.'),
            $this->check($live['schema_organization'], 'Organization schema detected.'),
            $this->check($seo->updated_at !== null, 'EEAT signal: content update date available.'),
            $this->check($seo->thumbnail !== null && $seo->thumbnail !== '', 'Social/OG thumbnail configured in admin.'),
        ];
    }

    private function seoHasFaqs(PagesSEO $seo): bool
    {
        if ($seo->course_id) {
            $course = $seo->course ?? $seo->course()->with('courseFaq')->first();
            if (! $course) {
                return false;
            }

            return (int) $course->faq_section === 1 && $course->courseFaq->isNotEmpty();
        }

        if ($seo->page_id) {
            $page = $seo->page ?? $seo->page()->with('faqs')->first();

            return $page && $page->faqs->isNotEmpty();
        }

        return false;
    }

    private function parseLiveDocument(string $html, string $focusKeyword): array
    {
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) {
            $title = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $metaDescription = '';
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $metaDescription = trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $h1Matches);
        $h1Texts = array_map(fn ($c) => $this->stripToText($c), $h1Matches[1] ?? []);
        $h1HasKeyword = $focusKeyword === '' || collect($h1Texts)->contains(fn ($t) => Str::contains(Str::lower($t), Str::lower($focusKeyword)));

        $canonical = '';
        if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
            $canonical = $m[1];
        }

        $robots = '';
        if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m)) {
            $robots = Str::lower($m[1]);
        }

        $og = fn (string $prop) => preg_match('/<meta[^>]+property=["\']og:' . preg_quote($prop, '/') . '["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m) ? $m[1] : '';

        $twitterCard = preg_match('/<meta[^>]+name=["\']twitter:card["\'][^>]+content=["\']([^"\']*)["\']/i', $html, $m) ? $m[1] : '';

        $lang = preg_match('/<html[^>]+lang=["\']([^"\']+)["\']/i', $html, $m) ? $m[1] : '';
        $charset = (bool) preg_match('/<meta[^>]+charset=/i', $html);
        $favicon = (bool) preg_match('/<link[^>]+rel=["\'](?:shortcut )?icon["\']/i', $html);
        $viewport = (bool) preg_match('/<meta[^>]+name=["\']viewport["\']/i', $html);
        $https = Str::startsWith(config('app.url'), 'https://');

        $mixed = (bool) preg_match('/\bsrc=["\']http:\/\/[^"\']+["\']/i', $html);

        $schemaBlocks = [];
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $jsonMatches)) {
            $schemaBlocks = $jsonMatches[1];
        }
        $schemaText = implode(' ', $schemaBlocks);

        return [
            'title' => $title,
            'meta_description' => $metaDescription,
            'h1_count' => count($h1Texts),
            'h1_has_keyword' => $h1HasKeyword,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_title' => $og('title'),
            'og_description' => $og('description'),
            'og_image' => $og('image'),
            'twitter_card' => $twitterCard,
            'lang' => $lang,
            'charset' => $charset,
            'favicon' => $favicon,
            'viewport' => $viewport,
            'https' => $https,
            'mixed_content' => $mixed,
            'text_ratio' => strlen($html) > 0 ? round((strlen($this->stripToText($html)) / strlen($html)) * 100, 1) : 0,
            'internal_links' => $this->countLinks($html, 'internal'),
            'script_count' => preg_match_all('/<script\b/i', $html),
            'style_count' => preg_match_all('/<link[^>]+rel=["\']stylesheet["\']/i', $html),
            'schema_any' => $schemaBlocks !== [],
            'schema_course' => Str::contains($schemaText, 'Course'),
            'schema_faq' => Str::contains($schemaText, 'FAQPage'),
            'schema_breadcrumb' => Str::contains($schemaText, 'BreadcrumbList'),
            'schema_organization' => Str::contains($schemaText, 'Organization'),
        ];
    }

    private function headingMetrics(string $html): array
    {
        preg_match_all('/<h([1-6])\b[^>]*>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER);
        $counts = ['h1' => 0, 'h2' => 0, 'h3' => 0];
        $maxLen = 0;
        $levels = [];
        foreach ($matches as $match) {
            $level = (int) $match[1];
            $levels[] = $level;
            if ($level === 1) {
                $counts['h1']++;
            } elseif ($level === 2) {
                $counts['h2']++;
            } elseif ($level === 3) {
                $counts['h3']++;
            }
            $maxLen = max($maxLen, strlen($this->stripToText($match[2])));
        }

        $skipped = false;
        for ($i = 1; $i < count($levels); $i++) {
            if ($levels[$i] - $levels[$i - 1] > 1) {
                $skipped = true;
                break;
            }
        }

        return [
            'h1' => $counts['h1'],
            'h2' => $counts['h2'],
            'h3' => $counts['h3'],
            'skipped_levels' => $skipped,
            'max_heading_len' => $maxLen,
        ];
    }

    private function imageMetrics(string $contentHtml, ?array $live): array
    {
        $html = $contentHtml;
        preg_match_all('/<img\b[^>]*>/i', $html, $imgMatches);
        $tags = $imgMatches[0] ?? [];
        $total = count($tags);
        $missingAlt = 0;
        $optimized = 0;
        $lazy = 0;
        $webp = 0;
        $dimensions = 0;
        $oversized = 0;

        foreach ($tags as $tag) {
            $alt = '';
            if (preg_match('/\balt=["\']([^"\']*)["\']/i', $tag, $m)) {
                $alt = trim($m[1]);
            }
            if ($alt === '' || Str::lower($alt) === 'image') {
                $missingAlt++;
            }

            if (preg_match('/\bsrc=["\']([^"\']+)["\']/i', $tag, $srcMatch)) {
                $src = $srcMatch[1];
                $base = basename(parse_url($src, PHP_URL_PATH) ?? $src);
                if (preg_match('/^[a-z0-9]+(-[a-z0-9]+)*\.(jpg|jpeg|png|webp|gif)$/i', $base)) {
                    $optimized++;
                }
                if (Str::endsWith(Str::lower($src), '.webp')) {
                    $webp++;
                }
            }

            if (preg_match('/\bloading=["\']lazy["\']/i', $tag)) {
                $lazy++;
            }
            if (preg_match('/\b(width|height)=/i', $tag)) {
                $dimensions++;
            }
        }

        $altCoverage = $total > 0 ? (int) round((($total - $missingAlt) / $total) * 100) : 100;

        return [
            'total' => $total,
            'missing_alt' => $missingAlt,
            'alt_coverage' => $altCoverage,
            'optimized_names' => $optimized,
            'lazy_loaded' => $lazy,
            'webp_count' => $webp,
            'with_dimensions' => $dimensions,
            'oversized' => $oversized,
            'summary' => $total . ' images, ' . ($total - $missingAlt) . ' with alt, ' . $optimized . ' optimized names',
        ];
    }

    private function linkMetrics(string $html, string $baseHtml): array
    {
        if (! preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $baseHtml, $matches, PREG_SET_ORDER)) {
            return ['internal' => 0, 'external' => 0, 'unique_anchors' => 0, 'external_blank' => 0, 'external_nofollow' => 0];
        }

        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $internal = 0;
        $external = 0;
        $externalBlank = 0;
        $externalNofollow = 0;
        $anchors = [];

        foreach ($matches as $match) {
            $href = $match[1];
            $anchor = Str::lower(trim($this->stripToText($match[2])));
            if ($anchor !== '') {
                $anchors[$anchor] = true;
            }

            $tag = $match[0];
            $isExternal = Str::startsWith($href, 'http') && $host && ! Str::contains($href, $host);
            if ($isExternal) {
                $external++;
                if (preg_match('/\btarget=["\']_blank["\']/i', $tag)) {
                    $externalBlank++;
                }
                if (preg_match('/\brel=["\'][^"\']*nofollow/i', $tag)) {
                    $externalNofollow++;
                }
            } else {
                $internal++;
            }
        }

        return [
            'internal' => $internal,
            'external' => $external,
            'unique_anchors' => count($anchors),
            'external_blank' => $externalBlank,
            'external_nofollow' => $externalNofollow,
        ];
    }

    private function readabilityMetrics(string $plainText): array
    {
        if ($plainText === '') {
            return [
                'flesch' => 0,
                'sentence_count' => 0,
                'avg_sentence_words' => 0,
                'avg_paragraph_words' => 0,
                'passive_percent' => 0,
                'transition_percent' => 0,
            ];
        }

        $sentences = preg_split('/[.!?]+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $sentenceCount = max(1, count($sentences));
        $words = preg_split('/\s+/u', $plainText, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = max(1, count($words));
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += $this->estimateSyllables($word);
        }

        $flesch = (int) round(206.835 - (1.015 * ($wordCount / $sentenceCount)) - (84.6 * ($syllables / $wordCount)));
        $flesch = max(0, min(100, $flesch));

        $paragraphs = preg_split('/\n{2,}/', $plainText) ?: [$plainText];
        $paragraphWords = array_map(fn ($p) => count(preg_split('/\s+/u', trim($p), -1, PREG_SPLIT_NO_EMPTY) ?: []), $paragraphs);
        $avgParagraph = (int) round(array_sum($paragraphWords) / max(1, count($paragraphWords)));

        $passive = preg_match_all('/\b(am|is|are|was|were|been|being)\s+\w+ed\b/i', $plainText);
        $passivePercent = (int) round(($passive / $sentenceCount) * 100);

        $transitions = ['however', 'therefore', 'moreover', 'furthermore', 'additionally', 'in addition', 'for example', 'as a result', 'in conclusion', 'meanwhile', 'similarly', 'also', 'because', 'finally'];
        $transitionHits = 0;
        foreach ($sentences as $sentence) {
            foreach ($transitions as $word) {
                if (Str::contains(Str::lower($sentence), $word)) {
                    $transitionHits++;
                    break;
                }
            }
        }
        $transitionPercent = (int) round(($transitionHits / $sentenceCount) * 100);

        return [
            'flesch' => $flesch,
            'sentence_count' => $sentenceCount,
            'avg_sentence_words' => (int) round($wordCount / $sentenceCount),
            'avg_paragraph_words' => $avgParagraph,
            'passive_percent' => $passivePercent,
            'transition_percent' => $transitionPercent,
        ];
    }

    private function estimateSyllables(string $word): int
    {
        $word = Str::lower(preg_replace('/[^a-z]/', '', $word));
        if ($word === '') {
            return 1;
        }
        $count = preg_match_all('/[aeiouy]+/', $word);

        return max(1, (int) $count);
    }

    private function parseKeywordList(string $keywords): array
    {
        if (trim($keywords) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[,;|]+/', $keywords) ?: [])));
    }

    private function keywordInFirstTenPercent(string $text, string $keyword): bool
    {
        $length = max((int) floor(strlen($text) * 0.1), 1);

        return Str::contains(Str::lower(substr($text, 0, $length)), Str::lower($keyword));
    }

    private function keywordInLastTenPercent(string $text, string $keyword): bool
    {
        $length = max((int) floor(strlen($text) * 0.1), 1);

        return Str::contains(Str::lower(substr($text, -$length)), Str::lower($keyword));
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

    private function keywordInH1(string $html, string $keyword): bool
    {
        if ($keyword === '' || ! preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $html, $matches)) {
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
        if ($keyword === '' || ! preg_match_all('/<img[^>]+alt=["\']([^"\']*)["\']/i', $html, $matches)) {
            return false;
        }
        foreach ($matches[1] as $alt) {
            if (Str::contains(Str::lower($alt), Str::lower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function urlHasStopWords(string $slug): bool
    {
        $parts = preg_split('/[\/\-]+/', strtolower(trim($slug, '/'))) ?: [];

        return (bool) array_intersect($parts, ['the', 'and', 'of', 'a', 'an', 'for', 'to', 'in', 'on', 'at']);
    }

    private function keywordOccurrences(string $text, string $keyword): int
    {
        return $keyword === '' ? 0 : substr_count(Str::lower($text), Str::lower($keyword));
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

    private function stripToText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }

    private function weightedScore(array $groups): int
    {
        $total = 0.0;
        foreach ($groups as $group) {
            $checks = $group['checks'] ?? [];
            $weight = (float) (self::WEIGHTS[$group['key']] ?? 0);
            $passed = count(array_filter($checks, fn ($c) => $c['ok']));
            $total += ($passed / max(count($checks), 1)) * $weight;
        }

        return (int) round(min(100, max(0, $total)));
    }

    private function sectionScore(array $checks): int
    {
        if ($checks === []) {
            return 0;
        }
        $passed = count(array_filter($checks, fn ($c) => $c['ok']));

        return (int) round(($passed / count($checks)) * 100);
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
