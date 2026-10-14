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
        $urls = $this->publishedUrls();

        $xml = view('sitemap.xml', compact('urls'))->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow:',
            '',
            'Sitemap: ' . url('/sitemap.xml'),
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
            'loc' => url('/'),
            'lastmod' => now()->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        $pageQuery = Page::query()->with('seo');
        if (pages_status_enabled() && Schema::hasColumn('pages', 'status')) {
            $pageQuery->where('status', 1);
        }

        foreach ($pageQuery->orderBy('id')->get() as $page) {
            if ($homeId && (int) $page->id === (int) $homeId) {
                continue;
            }

            if ($this->isNoIndex($page->seo)) {
                continue;
            }

            $path = trim((string) $page->full_url, '/');
            if ($path === '') {
                continue;
            }

            $urls[] = [
                'loc' => url($path),
                'lastmod' => optional($page->updated_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        $courseQuery = Course::query()->with('seo');
        foreach ($courseQuery->orderBy('id')->get() as $course) {
            if ($this->isNoIndex($course->seo)) {
                continue;
            }

            if (empty($course->slug)) {
                continue;
            }

            $urls[] = [
                'loc' => url('course/' . $course->slug),
                'lastmod' => optional($course->updated_at)?->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        return $urls;
    }

    private function isNoIndex($seo): bool
    {
        if (! $seo) {
            return false;
        }

        return strtolower((string) ($seo->robots_index ?? 'index')) === 'noindex';
    }
}
