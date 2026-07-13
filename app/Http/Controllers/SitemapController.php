<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Page;
use App\Models\SiteSettings;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $categoryPerma = SiteSettings::value('category_perma') ?? 'category';
        $homeId = SiteSettings::value('home');

        $pagesQuery = Page::query()->orderBy('updated_at', 'desc');
        if (pages_status_enabled()) {
            $pagesQuery->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            });
        }

        $pages = $pagesQuery->get()->map(function (Page $page) use ($homeId, $categoryPerma) {
            $loc = $homeId && (int) $page->id === (int) $homeId
                ? url('/')
                : url('/' . ltrim((string) $page->full_url, '/'));

            return [
                'loc' => $loc,
                'lastmod' => optional($page->updated_at)->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => ($homeId && (int) $page->id === (int) $homeId) ? '1.0' : '0.7',
                'category_perma' => $categoryPerma,
            ];
        });

        $courses = Course::query()
            ->orderBy('updated_at', 'desc')
            ->get(['id', 'slug', 'updated_at'])
            ->map(function (Course $course) {
                return [
                    'loc' => url('/course/' . $course->slug),
                    'lastmod' => optional($course->updated_at)->toAtomString(),
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ];
            });

        $urls = $pages->concat($courses)->unique('loc')->values();

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
