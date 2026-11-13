<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Artesaos\SEOTools\Facades\SEOTools;
use App\Models\{Page, Course, SiteSettings, PagesSEO};
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class SetSEO
{
    /** @var array<int, array<string, mixed>> */
    private array $jsonLdGraphs = [];

    public function handle($request, Closure $next)
    {
        if (app()->isProduction()) {
            $preferredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($preferredHost && strcasecmp((string) $request->getHost(), $preferredHost) !== 0) {
                return redirect()->away(rtrim((string) config('app.url'), '/') . $request->getRequestUri(), 301);
            }
        }

        if ($request->is('admin', 'admin/*') || $request->is('user', 'user/*') || $request->is('instructor', 'instructor/*') || $request->is('livewire', 'livewire/*') || $request->is('sitemap.xml') || $request->is('robots.txt')) {
            return $next($request);
        }

        $currentUrl = trim($request->path(), '/');
        $canonical = seo_preferred_canonical($request);
        SEOTools::setCanonical($canonical);
        View::share('seoJsonLdGraphs', []);

        if (seo_is_utility_path($currentUrl) || $request->is('login') || $request->is('register') || $request->is('password*')) {
            SEOTools::metatags()->addMeta('robots', 'noindex, follow');

            return $next($request);
        }

        if (Str::startsWith($currentUrl, 'course/')) {
            $course_slug = Str::after($currentUrl, 'course/');
            $seoData = Course::where('slug', $course_slug)->with(['seo', 'courseFaq'])->first();
        } else {
            $seoData = $this->resolvePageSeo($currentUrl);
        }

        if (! $seoData) {
            if ($currentUrl === 'course-index') {
                SEOTools::metatags()->addMeta('robots', 'noindex, follow');

                return $next($request);
            }

            SEOTools::metatags()->addMeta('robots', 'noindex, follow');

            return $next($request);
        }

        $seo = $seoData?->seo;

        if ($seoData instanceof Page) {
            $homeId = SiteSettings::value('home');
            if ($homeId && (int) $seoData->id === (int) $homeId) {
                $canonical = rtrim((string) config('app.url'), '/') . '/';
                SEOTools::setCanonical($canonical);
            } else {
                $preferredPath = trim((string) $seoData->full_url, '/');
                if ($preferredPath !== '' && ! in_array($preferredPath, ['home'], true)) {
                    $canonical = rtrim((string) config('app.url'), '/') . '/' . $preferredPath;
                    SEOTools::setCanonical($canonical);
                }
            }
        }

        $robots = seo_robots_content($seo);
        // Thin/empty pages stay out of Google. Menu membership does not control indexing —
        // Admin / Content Writer set index vs noindex on the SEO form (WordPress-style).
        if ($seoData instanceof Page && seo_page_is_empty($seoData)) {
            $robots = 'noindex, follow';
        }
        SEOTools::metatags()->addMeta('robots', $robots);

        $titleHint = $seoData instanceof Course
            ? (string) ($seoData->title ?? '')
            : (string) ($seoData->page_name ?? '');
        View::share('seoDocLang', seo_document_language($currentUrl, $titleHint));
        View::share('seoHreflangLinks', seo_hreflang_alternates($currentUrl, $titleHint));

        if ($seoData && $seo) {
            $title = (string) $seo->title;
            $description = (string) $seo->meta_description;
            $keywords = (string) $seo->keywords;
            $imageUrl = $seo->thumbnail ? asset($seo->thumbnail) : '';
            $imageAlt = trim((string) ($seo->thumbnail_alt ?? '')) ?: $title;
            $pageUrl = $canonical;

            SEOTools::setTitle($title);
            SEOTools::setDescription($description);
            SEOTools::setCanonical($pageUrl);
            if ($keywords !== '') {
                SEOTools::metatags()->setKeywords($keywords);
            }

            SEOTools::opengraph()->setTitle($title);
            SEOTools::opengraph()->setDescription($description);
            SEOTools::opengraph()->setUrl($pageUrl);
            if ($imageUrl !== '') {
                SEOTools::opengraph()->addImage($imageUrl);
            }
            SEOTools::opengraph()->addProperty('type', 'article');

            $this->applyTwitterMeta($title, $description, $imageUrl, $imageAlt);

            SEOTools::metatags()->addMeta('pinterest-rich-pin', 'true');

            $whatsappUrl = 'https://wa.me/?text=' . urlencode($title . ' - ' . $pageUrl);
            SEOTools::opengraph()->addProperty('whatsapp-share-url', $whatsappUrl);
        }

        if ($seoData) {
            $pageTitle = $titleHint !== '' ? $titleHint : (string) ($seo?->title ?? config('app.name'));
            $pageDesc = trim(strip_tags((string) ($seo?->meta_description ?? ''))) ?: seo_organization_description();
            $this->applyOrganizationSchema();
            $this->applyWebPageSchema($pageTitle, $pageDesc, $canonical);
            $this->applyStructuredDataSchemas($seoData, $seo);
            $this->applyBreadcrumbSchema($this->buildBreadcrumbCrumbs($seoData, $currentUrl));
        }

        $response = $next($request);
        if (method_exists($response, 'getContent') && method_exists($response, 'setContent')) {
            $type = (string) $response->headers->get('Content-Type', '');
            if ($type === '' || str_contains($type, 'html')) {
                $html = $response->getContent();
                if (is_string($html) && $html !== '') {
                    $response->setContent(seo_sanitize_public_html($html));
                }
            }
        }

        return $response;
    }

    private function pushJsonLd(array $graph): void
    {
        $this->jsonLdGraphs[] = $graph;
        View::share('seoJsonLdGraphs', $this->jsonLdGraphs);
    }

    private function applyOrganizationSchema(): void
    {
        $settings = SiteSettings::first();
        $org = [
            '@type' => 'Organization',
            '@id' => rtrim((string) config('app.url'), '/') . '/#organization',
            'name' => seo_organization_name(),
            'url' => rtrim((string) config('app.url'), '/'),
            'description' => seo_organization_description(),
        ];
        if ($settings?->logo) {
            $org['logo'] = asset('images/' . $settings->logo);
        }
        $sameAs = array_values(array_filter([
            $settings?->facebook_url ?? null,
            $settings?->twitter_url ?? null,
            $settings?->linkedin_url ?? null,
            $settings?->instagram_url ?? null,
            $settings?->youtube_url ?? null,
        ]));
        if ($sameAs !== []) {
            $org['sameAs'] = $sameAs;
        }

        $this->pushJsonLd($org);
    }

    private function applyWebPageSchema(string $title, string $description, string $url): void
    {
        $graph = [
            '@type' => 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $title,
            'isPartOf' => ['@id' => rtrim((string) config('app.url'), '/') . '/#organization'],
        ];
        if ($description !== '') {
            $graph['description'] = $description;
        }

        $this->pushJsonLd($graph);
    }

    private function applyStructuredDataSchemas(Page|Course $seoData, ?PagesSEO $seo): void
    {
        if ($seoData instanceof Course) {
            $this->applyCourseSchema($seoData, $seo);

            if ((int) $seoData->faq_section === 1 && $seoData->relationLoaded('courseFaq') && $seoData->courseFaq->isNotEmpty()) {
                $this->applyFaqSchema(
                    $seoData->courseFaq->map(fn ($faq) => [
                        'question' => (string) $faq->title,
                        'answer' => (string) $faq->short_description,
                    ])->all()
                );
            }

            return;
        }

        if ($seoData->relationLoaded('faqs') && $seoData->faqs->isNotEmpty()) {
            $this->applyFaqSchema(
                $seoData->faqs->map(fn ($faq) => [
                    'question' => (string) $faq->question,
                    'answer' => (string) $faq->answer,
                ])->all()
            );
        }
    }

    private function applyCourseSchema(Course $course, ?PagesSEO $seo): void
    {
        $description = trim(strip_tags((string) ($course->short_description ?: $seo?->meta_description ?: '')));
        $imageUrl = $seo?->thumbnail ? asset($seo->thumbnail) : ($course->thumbnail ? asset($course->thumbnail) : null);
        $graph = [
            '@type' => 'Course',
            'name' => (string) $course->title,
            'url' => seo_preferred_canonical(request()),
            'provider' => [
                '@type' => 'Organization',
                'name' => seo_organization_name(),
                'url' => rtrim((string) config('app.url'), '/'),
            ],
        ];
        if ($description !== '') {
            $graph['description'] = $description;
        }
        if ($imageUrl) {
            $graph['image'] = $imageUrl;
        }

        $this->pushJsonLd($graph);
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $faqs
     */
    private function applyFaqSchema(array $faqs): void
    {
        $mainEntity = [];
        foreach ($faqs as $faq) {
            $question = trim(strip_tags($faq['question']));
            $answer = trim(strip_tags($faq['answer']));
            if ($question === '' || $answer === '' || mb_strlen($answer) < 12) {
                continue;
            }

            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($mainEntity === []) {
            return;
        }

        $this->pushJsonLd([
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ]);
    }

    private function applyBreadcrumbSchema(array $crumbs): void
    {
        if (count($crumbs) < 2) {
            return;
        }

        $items = [];
        foreach ($crumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['title'],
                'item' => $crumb['url'],
            ];
        }

        $this->pushJsonLd([
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ]);
    }

    /**
     * @return array<int, array{title: string, url: string}>
     */
    private function buildBreadcrumbCrumbs(Page|Course $seoData, string $currentUrl): array
    {
        $crumbs = [
            ['title' => 'Home', 'url' => route('welcome')],
        ];

        if ($seoData instanceof Course) {
            $crumbs[] = ['title' => 'Courses', 'url' => seo_courses_hub_url()];
            $crumbs[] = [
                'title' => (string) ($seoData->title ?? $seoData->seo?->title ?? 'Course'),
                'url' => seo_preferred_canonical(request()),
            ];

            return $crumbs;
        }

        $path = trim($currentUrl, '/');
        $homeId = SiteSettings::value('home');
        if ($path === '' || ($homeId && (int) $seoData->id === (int) $homeId)) {
            return [];
        }

        $categoryPerma = SiteSettings::value('category_perma') ?? 'category';
        if (str_contains($path, '/')) {
            [$prefix] = explode('/', $path, 2);
            if ($prefix === $categoryPerma) {
                $basePageId = SiteSettings::value('categories');
                if ($basePageId) {
                    $basePage = Page::find($basePageId);
                    if ($basePage) {
                        $crumbs[] = [
                            'title' => (string) $basePage->page_name,
                            'url' => url($basePage->full_url),
                        ];
                    }
                }
            }
        }

        $crumbs[] = [
            'title' => (string) $seoData->page_name,
            'url' => seo_preferred_canonical(request()),
        ];

        return $crumbs;
    }

    private function applyTwitterMeta(string $title, string $description, string $imageUrl, string $imageAlt): void
    {
        $card = $imageUrl !== '' ? 'summary_large_image' : 'summary';
        $handle = site_twitter_handle();

        $twitter = SEOTools::twitter();
        $twitter->setType($card);
        $twitter->setTitle($title);
        $twitter->setDescription($description);

        if ($handle) {
            $twitter->setSite($handle);
            $twitter->addValue('creator', $handle);
        }

        if ($imageUrl !== '') {
            $twitter->setImage($imageUrl);
            $twitter->addValue('image:alt', $imageAlt);
        }
    }

    private function resolvePageSeo(string $path): ?Page
    {
        if ($path === '') {
            $homeId = SiteSettings::value('home');
            if ($homeId) {
                return Page::where('id', $homeId)->with(['seo', 'faqs'])->first();
            }

            return null;
        }

        $categoryPerma = SiteSettings::value('category_perma') ?? 'category';

        if (str_contains($path, '/')) {
            [$prefix, $slug] = explode('/', $path, 2);

            if ($prefix === $categoryPerma && $slug !== '') {
                $page = Page::where('url', $slug)->with(['seo', 'faqs'])->first();
                if ($page) {
                    return $page;
                }
            }
        }

        return Page::where('url', $path)->with(['seo', 'faqs'])->first();
    }

}
