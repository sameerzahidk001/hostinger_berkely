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
                'lastmod' => null,
                'changefreq' => 'daily',
                'priority' => '1.0',
            ]];
        }

        $xml = view('sitemap.xml', compact('urls'))->render();
        $response = response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=0, must-revalidate');
        $lastmod = $urls[0]['lastmod'] ?? null;
        if ($lastmod) {
            $response->header('Last-Modified', \Illuminate\Support\Carbon::parse($lastmod)->toRfc7231String());
        }

        return $response;
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
        $homeUpdatedAt = $this->latestTimestamp(array_merge(
            [
                Page::query()->where('id', $homeId)->value('updated_at'),
                Schema::hasTable('site_settings')
                    ? \Illuminate\Support\Facades\DB::table('site_settings')->max('updated_at')
                    : null,
            ],
            $this->pageRelatedTimestamps($homeId)
        ));

        $urls[] = [
            'loc' => $this->siteUrl('/'),
            'lastmod' => $homeUpdatedAt,
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];
        $coursesHub = Page::query()->with('seo')->where('url', 'courses')->first()
            ?: (SiteSettings::value('categories') ? Page::query()->with('seo')->find(SiteSettings::value('categories')) : null);
        $coursesPath = 'courses';
        if ($coursesHub) {
            $hubPath = trim((string) $coursesHub->full_url, '/');
            if ($hubPath !== '') {
                $coursesPath = $hubPath;
            }
        }
        if (! ($coursesHub && ($this->isNoIndex($coursesHub->seo) || $this->publicWouldNoindex($coursesHub)))) {
            $urls[] = [
                'loc' => $this->siteUrl($coursesPath),
                'lastmod' => $this->latestTimestamp([
                    Schema::hasTable('courses') ? \Illuminate\Support\Facades\DB::table('courses')->max('updated_at') : null,
                    optional($coursesHub?->updated_at)?->toDateTimeString(),
                ]),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ];
        }

        $pageQuery = Page::query()
            ->select(['id', 'url', 'parent_id', 'category_id', 'updated_at'])
            ->with(['seo', 'parent:id,url,parent_id,category_id', 'sections'])
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

                    $path = trim((string) $page->full_url, '/');
                    if ($path === '' || seo_is_utility_path($path) || in_array($path, ['home', 'page-not-found', 'course-index', 'courses'], true)) {
                        continue;
                    }

                    // Keep sitemap aligned with public robots (SEO form + thin/empty pages).
                    if ($this->isNoIndex($page->seo) || $this->publicWouldNoindex($page)) {
                        continue;
                    }

                    $urls[] = [
                        'loc' => $this->siteUrl($path),
                        'lastmod' => $this->latestTimestamp(array_merge(
                            [optional($page->updated_at)?->toDateTimeString()],
                            $this->pageRelatedTimestamps($page->id)
                        )),
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
        if (Schema::hasColumn('courses', 'status')) {
            $courseQuery->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            });
        }
        $courseQuery->orderBy('id')->chunkById(100, function ($courses) use (&$urls) {
            foreach ($courses as $course) {
                try {
                    if (empty($course->slug)) {
                        continue;
                    }
                    // Match public robots: explicit noindex courses stay out of the sitemap.
                    if ($this->isNoIndex($course->seo)) {
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
        $raw = strtolower(preg_replace('/[^a-z]/', '', (string) ($seo->robots_index ?? 'index')) ?? 'index');

        return str_contains($raw, 'noindex');
    }

    private function publicWouldNoindex(Page $page): bool
    {
        return function_exists('seo_page_is_empty') && seo_page_is_empty($page);
    }

    /**
     * @return array<int, mixed>
     */
    private function pageRelatedTimestamps(mixed $pageId): array
    {
        if (! $pageId) {
            return [];
        }

        $out = [];
        if (Schema::hasTable('page_sections')) {
            $out[] = \Illuminate\Support\Facades\DB::table('page_sections')->where('page_id', $pageId)->max('updated_at');
        }
        if (Schema::hasTable('pages_s_e_o_s')) {
            $out[] = \Illuminate\Support\Facades\DB::table('pages_s_e_o_s')->where('page_id', $pageId)->max('updated_at');
        }

        return $out;
    }

    private function latestTimestamp(array $values): ?string
    {
        $max = null;
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            try {
                $ts = \Illuminate\Support\Carbon::parse($value)->getTimestamp();
            } catch (\Throwable $e) {
                continue;
            }
            if ($max === null || $ts > $max) {
                $max = $ts;
            }
        }

        return $max ? \Illuminate\Support\Carbon::createFromTimestamp($max)->toAtomString() : null;
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
