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
            'loc' => $this->siteUrl('course-index'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => '0.6',
        ];

        $pageQuery = Page::query()
            ->select(['id', 'url', 'parent_id', 'category_id', 'updated_at'])
            ->with(['seo', 'parent:id,url,parent_id,category_id'])
            ->withCount('sections');
        if (Schema::hasColumn('pages', 'status')) {
            $pageQuery->addSelect('status');
        }
        if (pages_status_enabled() && Schema::hasColumn('pages', 'status')) {
            $pageQuery->where('status', 1);
        }

        $pageQuery->orderBy('id')->chunkById(40, function ($pages) use (&$urls, $homeId) {
            $pages->load('sections');
            foreach ($pages as $page) {
                try {
                    if ($homeId && (int) $page->id === (int) $homeId) {
                        continue;
                    }

                    if ($this->isNoIndex($page->seo)) {
                        continue;
                    }

                    $path = trim((string) $page->full_url, '/');
                    if ($path === '' || seo_is_utility_path($path) || in_array($path, ['home', 'page-not-found'], true)) {
                        continue;
                    }

                    if ((int) ($page->sections_count ?? 0) === 0 || seo_page_is_empty($page)) {
                        continue;
                    }

                    if (! seo_path_is_menu_linked($path)) {
                        continue;
                    }

                    $urls[] = [
                        'loc' => $this->siteUrl($path),
                        'lastmod' => optional($page->updated_at)?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });

        $courseQuery = Course::query()->select(['id', 'slug', 'updated_at'])->with('seo');
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
