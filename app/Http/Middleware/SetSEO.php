<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Artesaos\SEOTools\Facades\SEOTools;
use App\Models\{Page, Course, SiteSettings, PagesSEO};
use Illuminate\Support\Str;

class SetSEO
{
    public function handle($request, Closure $next)
    {
        $currentUrl = trim($request->path(), '/');

        if (Str::startsWith($currentUrl, 'course/')) {
            $course_slug = Str::after($currentUrl, 'course/');
            $seoData = Course::where('slug', $course_slug)->with(['seo', 'courseFaq'])->first();
        } else {
            $seoData = $this->resolvePageSeo($currentUrl);
        }

        if (!$seoData) {
            $defaultSlug = 'general'; // Set your default slug here
            $seoData = Page::where('url', $defaultSlug)->with(['seo', 'faqs'])->first();
        }

        $seo = $seoData?->seo;

        if ($seoData && $seo) {
            $title = (string) $seo->title;
            $description = (string) $seo->meta_description;
            $keywords = (string) $seo->keywords;
            $imageUrl = $seo->thumbnail ? asset($seo->thumbnail) : '';
            $imageAlt = trim((string) ($seo->thumbnail_alt ?? '')) ?: $title;
            $pageUrl = url()->current();

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
            $this->applyOrganizationSchema();
            $this->applyStructuredDataSchemas($seoData, $seo);
            $this->applyBreadcrumbSchema($this->buildBreadcrumbCrumbs($seoData, $currentUrl));
        }

        return $next($request);
    }

    private function applyOrganizationSchema(): void
    {
        $settings = SiteSettings::first();
        $name = (string) (config('seotools.meta.defaults.title') ?: config('app.name'));
        $url = (string) config('app.url');
        $logo = $settings?->logo ? asset('images/' . $settings->logo) : null;

        SEOTools::jsonLdMulti()->newJsonLd();
        SEOTools::jsonLdMulti()->setType('Organization');
        SEOTools::jsonLdMulti()->setTitle($name);
        SEOTools::jsonLdMulti()->setUrl($url);

        if ($logo) {
            SEOTools::jsonLdMulti()->addImage($logo);
        }

        $sameAs = array_values(array_filter([
            $settings?->facebook_url ?? null,
            $settings?->twitter_url ?? null,
            $settings?->linkedin_url ?? null,
            $settings?->instagram_url ?? null,
            $settings?->youtube_url ?? null,
        ]));

        if ($sameAs !== []) {
            SEOTools::jsonLdMulti()->addValue('sameAs', $sameAs);
        }
    }

    private function applyStructuredDataSchemas(Page|Course $seoData, ?PagesSEO $seo): void
    {
        if ($seoData instanceof Course) {
            $this->applyCourseSchema($seoData, $seo);

            if ((int) $seoData->faq_section === 1 && $seoData->courseFaq->isNotEmpty()) {
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

        SEOTools::jsonLdMulti()->newJsonLd();
        SEOTools::jsonLdMulti()->setType('Course');
        SEOTools::jsonLdMulti()->setTitle((string) $course->title);
        SEOTools::jsonLdMulti()->setUrl(url()->current());

        if ($description !== '') {
            SEOTools::jsonLdMulti()->setDescription($description);
        }

        if ($imageUrl) {
            SEOTools::jsonLdMulti()->addImage($imageUrl);
        }

        SEOTools::jsonLdMulti()->addValue('provider', [
            '@type' => 'Organization',
            'name' => (string) (config('seotools.meta.defaults.title') ?: config('app.name')),
            'url' => (string) config('app.url'),
        ]);
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
            if ($question === '' || $answer === '') {
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

        SEOTools::jsonLdMulti()->newJsonLd();
        SEOTools::jsonLdMulti()->setType('FAQPage');
        SEOTools::jsonLdMulti()->addValue('mainEntity', $mainEntity);
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

        SEOTools::jsonLdMulti()->newJsonLd();
        SEOTools::jsonLdMulti()->setType('BreadcrumbList');
        SEOTools::jsonLdMulti()->addValue('itemListElement', $items);
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
            $crumbs[] = ['title' => 'Course', 'url' => route('welcome')];
            $crumbs[] = [
                'title' => (string) ($seoData->title ?? $seoData->seo?->title ?? 'Course'),
                'url' => url()->current(),
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
            'url' => url()->current(),
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
            $twitter->setCreator($handle);
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
