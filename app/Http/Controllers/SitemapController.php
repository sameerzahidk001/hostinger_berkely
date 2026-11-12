<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Page;
use App\Models\SiteSettings;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;

class SitemapController extends Controller
{
    public function index(): Response
    {
        try {
            $urls = $this->publishedUrls();
        } catch (\Throwable $e) {
            report($e);
            $urls = [[
                'loc' => $this->siteUrl('/'),
                'lastmod' => now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ]];
        }

        $xml = view('sitemap.xml', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $sitemap = rtrim((string) config('app.url'), '/') . '/sitemap.xml';
        $lines = [
            'User-agent: *',
            'Allow: /images/',
            'Allow: /frontend/images/',
            'Allow: /admin/images/',
            'Allow: /admin/courses/',
            'Allow: /storage/',
            'Allow: /*.jpg$',
            'Allow: /*.jpeg$',
            'Allow: /*.png$',
            'Allow: /*.gif$',
            'Allow: /*.webp$',
            'Allow: /*.svg$',
            'Disallow: /search',
            'Disallow: /search-courses',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /admin/',
            'Disallow: /user/',
            'Disallow: /instructor/',
            '',
            'Sitemap: ' . $sitemap,
        ];

        return response(implode("\n", $lines) . "\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @return array<int, array{loc:string,lastmod:?string,changefreq:string,priority:string}>
     */
    private function publishedUrls(): array
    {
        $urls = [];
        $homeId = SiteSettings::value('home');

        $urls[] = [
            'loc' => $this->siteUrl('/'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];
        $urls[] = [
            'loc' => $this->siteUrl('courses'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => '0.6',
        ];

        $pageQuery = Page::query()
            ->select(['id', 'url', 'parent_id', 'category_id', 'updated_at'])
            ->with(['seo', 'parent:id,url,parent_id,category_id'])
            ->withCount('sections');
        if (Schema::hasColumn('pages', 'page_name')) {
            $pageQuery->addSelect('page_name');
        }
        if (Schema::hasColumn('pages', 'status')) {
            $pageQuery->addSelect('status');
        }
        if (pages_status_enabled() && Schema::hasColumn('pages', 'status')) {
            $pageQuery->where('status', 1);
        }

        $pageQuery->orderBy('id')->chunkById(40, function ($pages) use (&$urls, $homeId) {
            foreach ($pages as $page) {
                try {
                    if ($homeId && (int) $page->id === (int) $homeId) {
                        continue;
                    }

                    if ($this->isNoIndex($page->seo)) {
                        continue;
                    }

                    $path = trim((string) $page->full_url, '/');
                    if ($path === '' || seo_is_utility_path($path) || in_array($path, ['home', 'page-not-found', 'course-index', 'courses'], true)) {
                        continue;
                    }

                    // Thin pages are still noindexed at request time in SetSEO.
                    if ((int) ($page->sections_count ?? 0) === 0) {
                        continue;
                    }

                    $urls[] = [
                        'loc' => $this->siteUrl($path),
                        'lastmod' => optional($page->updated_at)?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                        'images' => $this->imagesForSeo($page->seo, $page->page_name ?? null),
                    ];
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        $courseCols = ['id', 'slug', 'title', 'updated_at'];
        foreach (['thumbnail', 'post_image', 'overview_img'] as $col) {
            if (Schema::hasColumn('courses', $col)) {
                $courseCols[] = $col;
            }
        }
        $courseQuery = Course::query()->select($courseCols)->with('seo');
        $courseQuery->orderBy('id')->chunkById(100, function ($courses) use (&$urls) {
            foreach ($courses as $course) {
                try {
                    if ($this->isNoIndex($course->seo)) {
                        continue;
                    }
                    if (empty($course->slug)) {
                        continue;
                    }

                    $urls[] = [
                        'loc' => $this->siteUrl('course/' . $course->slug),
                        'lastmod' => optional($course->updated_at)?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                        'images' => $this->imagesForCourse($course),
                    ];
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        return $urls;
    }

    private function isNoIndex($seo): bool
    {
        if (! $seo) {
            return false;
        }

        return strtolower((string) ($seo->robots_index ?? 'index')) === 'noindex';
    }

    private function imagesForCourse(Course $course): array
    {
        $title = trim((string) ($course->seo->title ?? $course->title ?? ''));
        $alt = trim((string) ($course->seo->thumbnail_alt ?? '')) ?: $title;
        $paths = [
            $course->seo->thumbnail ?? null,
            $course->thumbnail ?? null,
            $course->post_image ?? null,
            $course->overview_img ?? null,
        ];

        return $this->uniqueImageEntries($paths, $title, $alt);
    }

    private function imagesForSeo($seo, ?string $fallbackTitle = null): array
    {
        if (! $seo) {
            return [];
        }
        $title = trim((string) ($seo->title ?? $fallbackTitle ?? ''));
        $alt = trim((string) ($seo->thumbnail_alt ?? '')) ?: $title;

        return $this->uniqueImageEntries([$seo->thumbnail ?? null], $title, $alt);
    }

    /**
     * @param  array<int, mixed>  $paths
     * @return array<int, array{loc:string,title:?string,caption:?string}>
     */
    private function uniqueImageEntries(array $paths, ?string $title, ?string $alt): array
    {
        $out = [];
        $seen = [];
        foreach ($paths as $path) {
            $url = $this->publicImageUrl($path);
            if ($url === null || isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;
            $out[] = [
                'loc' => $url,
                'title' => $title !== '' ? $title : null,
                'caption' => $alt !== '' ? $alt : null,
            ];
        }

        return $out;
    }

    private function publicImageUrl(mixed $path): ?string
    {
        if (! is_string($path) && ! is_array($path)) {
            return null;
        }
        $url = function_exists('media_url') ? media_url($path) : null;
        if (! is_string($url) || $url === '') {
            return null;
        }
        if (str_starts_with($url, 'data:')) {
            return null;
        }
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = rtrim((string) config('app.url'), '/') . '/' . ltrim($url, '/');
        }

        return $url;
    }

    private function siteUrl(string $path = '/'): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $path = '/' . ltrim($path, '/');
        if ($path === '/') {
            return $base . '/';
        }

        return $base . rtrim($path, '/');
    }
}
