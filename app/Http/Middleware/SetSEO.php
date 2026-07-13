<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Page;
use App\Models\SiteSettings;
use Artesaos\SEOTools\Facades\SEOTools;
use Closure;
use Illuminate\Support\Str;

class SetSEO
{
    /** Paths that should not receive public SEO tags. */
    private array $skipPrefixes = [
        'admin',
        'user',
        'instructor',
        'login',
        'register',
        'password',
        'email',
        'cart',
        'checkout',
        'sanctum',
        'api',
        'approval-notice',
        'optimize',
        'sitemap.xml',
        'robots.txt',
    ];

    public function handle($request, Closure $next)
    {
        $currentUrl = trim($request->path(), '/');

        if ($this->shouldSkip($currentUrl)) {
            return $next($request);
        }

        $seoOwner = $this->resolveSeoOwner($currentUrl);
        $seo = $seoOwner?->seo;

        if (! $seo) {
            return $next($request);
        }

        $canonical = url()->current();
        $title = (string) ($seo->title ?? '');
        $description = (string) ($seo->meta_description ?? '');
        $image = $this->resolveImage($seo->thumbnail ?? null);
        $isCourse = $seoOwner instanceof Course;
        $robots = $this->resolveRobots($seoOwner);

        SEOTools::setTitle($title);
        SEOTools::setDescription($description);
        SEOTools::setCanonical($canonical);
        SEOTools::metatags()->setKeywords($seo->keywords);
        SEOTools::metatags()->setRobots($robots);

        SEOTools::opengraph()->setTitle($title);
        SEOTools::opengraph()->setDescription($description);
        SEOTools::opengraph()->setUrl($canonical);
        SEOTools::opengraph()->addProperty('type', $isCourse ? 'website' : 'article');
        SEOTools::opengraph()->addProperty('site_name', config('app.name', 'BERKELEYME'));
        if ($image) {
            SEOTools::opengraph()->addImage($image);
        }

        SEOTools::twitter()->setType('summary_large_image');
        SEOTools::twitter()->setTitle($title);
        SEOTools::twitter()->setDescription($description);
        SEOTools::twitter()->setUrl($canonical);
        if ($image) {
            SEOTools::twitter()->setImage($image);
        }

        SEOTools::jsonLd()->setTitle($title);
        SEOTools::jsonLd()->setDescription($description);
        SEOTools::jsonLd()->setType($isCourse ? 'Course' : 'WebPage');
        SEOTools::jsonLd()->setUrl($canonical);
        if ($image) {
            SEOTools::jsonLd()->addImage($image);
        }

        SEOTools::jsonLdMulti()->newJsonLd();
        SEOTools::jsonLdMulti()->setType('Organization');
        SEOTools::jsonLdMulti()->setTitle(config('app.name', 'Berkeley School of Business, Arts & Sciences'));
        SEOTools::jsonLdMulti()->setUrl(url('/'));
        SEOTools::jsonLdMulti()->addValue('name', config('app.name', 'Berkeley School of Business, Arts & Sciences'));
        $logo = SiteSettings::value('logo');
        if ($logo) {
            SEOTools::jsonLdMulti()->addImage(media_url($logo) ?? asset('images/' . ltrim((string) $logo, '/')));
        }

        return $next($request);
    }

    private function shouldSkip(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        foreach ($this->skipPrefixes as $prefix) {
            if ($path === $prefix || Str::startsWith($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }

    private function resolveSeoOwner(string $path): Page|Course|null
    {
        if (Str::startsWith($path, 'course/')) {
            $courseSlug = Str::after($path, 'course/');

            return Course::where('slug', $courseSlug)->with('seo')->first();
        }

        if ($path === '') {
            $homeId = SiteSettings::value('home');

            return $homeId
                ? Page::with('seo')->find($homeId)
                : null;
        }

        $page = $this->resolvePageSeo($path);
        if ($page) {
            return $page;
        }

        return Page::where('url', 'general')->with('seo')->first();
    }

    private function resolvePageSeo(string $path): ?Page
    {
        $categoryPerma = SiteSettings::value('category_perma') ?? 'category';

        if (str_contains($path, '/')) {
            [$prefix, $slug] = explode('/', $path, 2);

            if ($prefix === $categoryPerma && $slug !== '') {
                $page = Page::where('url', $slug)->with('seo')->first();
                if ($page) {
                    return $page;
                }
            }

            $leaf = basename($path);
            $page = Page::where('url', $leaf)->with('seo')->first();
            if ($page) {
                return $page;
            }
        }

        return Page::where('url', $path)->with('seo')->first();
    }

    private function resolveImage(mixed $thumbnail): ?string
    {
        if (! is_string($thumbnail) || trim($thumbnail) === '') {
            return null;
        }

        return media_url($thumbnail);
    }

    private function resolveRobots(Page|Course $owner): string
    {
        if ($owner instanceof Page && pages_status_enabled()) {
            if ((int) normalize_page_status($owner->status ?? 1) === 0) {
                return 'noindex, follow';
            }
        }

        return 'index, follow';
    }
}
