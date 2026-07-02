<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    private const PERIODS = ['today', '7', '28', '90', '180', '365', 'lifetime'];

    public function __construct()
    {
        $this->middleware('long.running');
    }

    public function index(Request $request)
    {
        if (! Schema::hasTable('page_views')) {
            return view('admin.analytics.index', $this->emptyAnalyticsPayload($request));
        }

        try {
        $period = (string) $request->query('days', '28');
        if (! in_array($period, self::PERIODS, true)) {
            $period = '28';
        }

        $range = $this->resolvePeriodRange($period);
        $start = $range['start'];
        $end = $range['end'];
        $prevStart = $range['prevStart'];
        $prevEnd = $range['prevEnd'];
        $periodLabel = $range['label'];
        $comparisonLabel = $range['comparisonLabel'];

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

        [$dailyLabels, $dailyValues] = $this->buildChartSeries($baseQuery, $start, $end);

        $periodViews = $baseQuery()
            ->whereBetween('created_at', [$start, $end])
            ->get($this->pageViewSelectColumns());

        $this->backfillMissingLocations($periodViews);

        $channels = $this->aggregateChannels($periodViews);
        $locations = $this->aggregateLocations($periodViews);
        $devices = $this->aggregateDevices($periodViews);
        $topPages = $this->aggregateTopPages($periodViews);

        $latestPageViews = $baseQuery()
            ->with('userBehaviors')
            ->orderByDesc('updated_at')
            ->paginate(15)
            ->withQueryString();

        $this->backfillMissingLocations($latestPageViews->getCollection());

        return view('admin.analytics.index', compact(
            'period',
            'periodLabel',
            'comparisonLabel',
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
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Analytics page failed: ' . $e->getMessage());

            return view('admin.analytics.index', $this->emptyAnalyticsPayload($request));
        }
    }

    private function emptyAnalyticsPayload(Request $request): array
    {
        $period = (string) $request->query('days', '28');
        if (! in_array($period, self::PERIODS, true)) {
            $period = '28';
        }

        $emptyChart = [
            'labels' => ['No data'],
            'values' => [1],
            'percents' => [100],
            'colors' => ['#e0e0e0'],
            'sliceColors' => ['#e0e0e0'],
            'total' => 0,
        ];

        return [
            'period' => $period,
            'periodLabel' => $period,
            'comparisonLabel' => 'the previous period',
            'currentVisitors' => 0,
            'previousVisitors' => 0,
            'growth' => 0,
            'dailyLabels' => [],
            'dailyValues' => [],
            'channels' => $emptyChart,
            'locations' => $emptyChart,
            'devices' => $emptyChart,
            'topPages' => [],
            'latestPageViews' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15),
            'start' => \Carbon\Carbon::today()->startOfDay(),
            'end' => \Carbon\Carbon::today()->endOfDay(),
        ];
    }

    private function resolvePeriodRange(string $period): array
    {
        $end = Carbon::today()->endOfDay();

        if ($period === 'today') {
            $start = Carbon::today()->startOfDay();

            return [
                'start' => $start,
                'end' => $end,
                'prevStart' => Carbon::yesterday()->startOfDay(),
                'prevEnd' => Carbon::yesterday()->endOfDay(),
                'label' => 'today',
                'comparisonLabel' => 'yesterday',
            ];
        }

        if ($period === 'lifetime') {
            $first = PageView::query()->min('created_at');
            $start = $first ? Carbon::parse($first)->startOfDay() : Carbon::today()->startOfDay();
            $spanDays = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($spanDays - 1)->startOfDay();

            return [
                'start' => $start,
                'end' => $end,
                'prevStart' => $prevStart,
                'prevEnd' => $prevEnd,
                'label' => 'lifetime',
                'comparisonLabel' => 'the previous ' . $spanDays . ' days',
            ];
        }

        $days = (int) $period;
        $start = Carbon::today()->subDays($days - 1)->startOfDay();
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'prevStart' => $prevStart,
            'prevEnd' => $prevEnd,
            'label' => (string) $days,
            'comparisonLabel' => 'the previous ' . $days . ' days',
        ];
    }

    private function buildChartSeries(callable $baseQuery, Carbon $start, Carbon $end): array
    {
        $spanDays = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $labels = [];
        $values = [];

        if ($spanDays > 180) {
            $cursor = $start->copy()->startOfMonth();
            while ($cursor <= $end) {
                $monthStart = $cursor->copy()->startOfMonth();
                $monthEnd = $cursor->copy()->endOfMonth();
                if ($monthEnd > $end) {
                    $monthEnd = $end->copy();
                }
                if ($monthStart < $start) {
                    $monthStart = $start->copy();
                }

                $labels[] = $cursor->format('M Y');
                $values[] = (int) $baseQuery()
                    ->whereBetween('created_at', [$monthStart, $monthEnd])
                    ->sum('view_count');

                $cursor->addMonth();
            }

            return [$labels, $values];
        }

        if ($spanDays > 60) {
            $cursor = $start->copy()->startOfWeek();
            while ($cursor <= $end) {
                $weekStart = $cursor->copy()->startOfDay();
                $weekEnd = $cursor->copy()->endOfWeek()->endOfDay();
                if ($weekEnd > $end) {
                    $weekEnd = $end->copy();
                }
                if ($weekStart < $start) {
                    $weekStart = $start->copy();
                }

                $labels[] = 'Wk ' . $weekStart->format('M j');
                $values[] = (int) $baseQuery()
                    ->whereBetween('created_at', [$weekStart, $weekEnd])
                    ->sum('view_count');

                $cursor->addWeek();
            }

            return [$labels, $values];
        }

        for ($i = 0; $i < $spanDays; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('M j');
            $values[] = (int) $baseQuery()
                ->whereDate('created_at', $date->toDateString())
                ->sum('view_count');
        }

        return [$labels, $values];
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
        if (! $url) {
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

    private function backfillMissingLocations($views): void
    {
        $filled = 0;

        foreach ($views as $view) {
            if ($filled >= 5) {
                break;
            }

            if (! empty($view->country) || empty($view->ip_address)) {
                continue;
            }

            $location = resolve_ip_location($view->ip_address);
            if (empty($location['country'])) {
                continue;
            }

            PageView::whereKey($view->id)->update($location);
            $view->country = $location['country'];
            $view->city = $location['city'];
            $view->region = $location['region'];
            $view->postal = $location['postal'];
            $view->location = $location['location'];
            $filled++;
        }
    }

    private function pageViewSelectColumns(): array
    {
        $columns = ['id', 'ip_address', 'country', 'platform', 'browser', 'url', 'view_count'];

        if (Schema::hasColumn('page_views', 'referrer')) {
            array_splice($columns, 2, 0, ['referrer']);
        }

        return $columns;
    }
}
