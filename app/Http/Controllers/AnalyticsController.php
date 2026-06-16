<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->query('days', 28);
        $days = in_array($days, [7, 28, 90], true) ? $days : 28;

        $end = Carbon::today()->endOfDay();
        $start = Carbon::today()->subDays($days - 1)->startOfDay();
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        $baseQuery = fn () => PageView::query()
            ->where('url', 'not like', '%/user-behavior%')
            ->where('url', 'not like', '%/admin/%');

        $currentVisitors = (int) $baseQuery()
            ->whereBetween('created_at', [$start, $end])
            ->sum('view_count');

        $previousVisitors = (int) $baseQuery()
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->sum('view_count');

        $growth = $previousVisitors > 0
            ? round((($currentVisitors - $previousVisitors) / $previousVisitors) * 100, 1)
            : ($currentVisitors > 0 ? 100 : 0);

        $dailyLabels = [];
        $dailyValues = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $dailyLabels[] = $date->format('M j');
            $dailyValues[] = (int) $baseQuery()
                ->whereDate('created_at', $date->toDateString())
                ->sum('view_count');
        }

        $periodViews = $baseQuery()
            ->whereBetween('created_at', [$start, $end])
            ->get(['referrer', 'country', 'platform', 'browser', 'url', 'view_count']);

        $channels = $this->aggregateChannels($periodViews);
        $locations = $this->aggregateLocations($periodViews);
        $devices = $this->aggregateDevices($periodViews);
        $topPages = $this->aggregateTopPages($periodViews);

        $latestPageViews = $baseQuery()
            ->with('userBehaviors')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.analytics.index', compact(
            'days',
            'currentVisitors',
            'previousVisitors',
            'growth',
            'dailyLabels',
            'dailyValues',
            'channels',
            'locations',
            'devices',
            'topPages',
            'latestPageViews',
            'start',
            'end'
        ));
    }

    private function aggregateChannels($views): array
    {
        $totals = [
            'Direct' => 0,
            'Organic Search' => 0,
            'Organic Social' => 0,
            'Referral' => 0,
        ];

        foreach ($views as $view) {
            $channel = $this->detectChannel($view->referrer, $view->url);
            $totals[$channel] += max(1, (int) $view->view_count);
        }

        return $this->toChartSlices($totals, ['#f4b400', '#4285f4', '#9c27b0', '#34a853']);
    }

    private function detectChannel(?string $referrer, ?string $url): string
    {
        if (empty($referrer)) {
            return 'Direct';
        }

        $ref = strtolower($referrer);
        $host = parse_url($ref, PHP_URL_HOST) ?? '';

        $appHost = strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? '');

        if ($host === '' || ($appHost && str_contains($host, $appHost))) {
            return 'Direct';
        }

        if (preg_match('/google\.|bing\.|yahoo\.|duckduckgo\.|baidu\./', $host)) {
            return 'Organic Search';
        }

        if (preg_match('/facebook\.|fb\.|twitter\.|t\.co|instagram\.|linkedin\.|tiktok\.|youtube\.|pinterest\./', $host)) {
            return 'Organic Social';
        }

        return 'Referral';
    }

    private function aggregateLocations($views): array
    {
        $totals = [];

        foreach ($views as $view) {
            $label = $view->country ?: 'Unknown';
            $totals[$label] = ($totals[$label] ?? 0) + max(1, (int) $view->view_count);
        }

        arsort($totals);
        $totals = array_slice($totals, 0, 6, true);

        return $this->toChartSlices($totals, ['#4285f4', '#34a853', '#f4b400', '#ea4335', '#9c27b0', '#00acc1']);
    }

    private function aggregateDevices($views): array
    {
        $totals = ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0, 'Other' => 0];

        foreach ($views as $view) {
            $platform = strtolower($view->platform ?? '');
            $ua = strtolower($view->browser ?? '');

            if (str_contains($platform, 'android') || str_contains($ua, 'mobile')) {
                $totals['Mobile'] += max(1, (int) $view->view_count);
            } elseif (str_contains($platform, 'ipad') || str_contains($ua, 'tablet')) {
                $totals['Tablet'] += max(1, (int) $view->view_count);
            } elseif (in_array($platform, ['windows', 'mac', 'macos', 'linux', 'chrome os'], true) || $platform !== '') {
                $totals['Desktop'] += max(1, (int) $view->view_count);
            } else {
                $totals['Other'] += max(1, (int) $view->view_count);
            }
        }

        return $this->toChartSlices(array_filter($totals), ['#4285f4', '#34a853', '#f4b400', '#9e9e9e']);
    }

    private function aggregateTopPages($views): array
    {
        $pages = [];

        foreach ($views as $view) {
            $path = $this->shortUrl($view->url);
            $pages[$path] = ($pages[$path] ?? 0) + max(1, (int) $view->view_count);
        }

        arsort($pages);

        return array_slice($pages, 0, 10, true);
    }

    private function shortUrl(?string $url): string
    {
        if (!$url) {
            return '/';
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;

        return strlen($path) > 60 ? substr($path, 0, 57) . '...' : $path;
    }

    private function toChartSlices(array $totals, array $colors): array
    {
        $total = array_sum($totals);

        if ($total === 0) {
            return [
                'labels' => ['No data'],
                'values' => [1],
                'percents' => [100],
                'colors' => ['#e0e0e0'],
                'total' => 0,
            ];
        }

        $labels = [];
        $values = [];
        $percents = [];
        $sliceColors = [];
        $i = 0;

        foreach ($totals as $label => $value) {
            if ($value <= 0) {
                continue;
            }
            $labels[] = $label;
            $values[] = $value;
            $percents[] = round(($value / $total) * 100);
            $sliceColors[] = $colors[$i % count($colors)];
            $i++;
        }

        return compact('labels', 'values', 'percents', 'sliceColors') + ['total' => $total, 'colors' => $sliceColors];
    }
}
