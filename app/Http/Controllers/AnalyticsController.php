<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use App\Services\StudyMaterialService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    private const PERIODS = ['today', '7', '28', '90', '180', '365', 'lifetime'];
    private const TABS = ['traffic', 'visits', 'pages'];

    public function __construct()
    {
        $this->middleware('long.running');
    }

    public function index(Request $request)
    {
        $lms = app(StudyMaterialService::class);
        if ($lms->isInstructorActor() && ! instructor_can_view_site_analytics()) {
            return redirect()->route('user.home')->with('fail', 'Analytics is not available on this account.');
        }

        if (! Schema::hasTable('page_views')) {
            return view('admin.analytics.index', $this->emptyAnalyticsPayload($request));
        }

        try {
            $period = (string) $request->query('days', '28');
            if (! in_array($period, self::PERIODS, true)) {
                $period = '28';
            }

            $tab = (string) $request->query('tab', 'traffic');
            if (! in_array($tab, self::TABS, true)) {
                $tab = 'traffic';
            }

            $range = $this->resolvePeriodRange($period);
            $start = $range['start'];
            $end = $range['end'];
            $search = trim((string) $request->query('q', ''));
            $fromInput = trim((string) $request->query('from', ''));
            $toInput = trim((string) $request->query('to', ''));

            $filterStart = $start->copy();
            $filterEnd = $end->copy();
            if ($fromInput !== '') {
                try {
                    $filterStart = Carbon::parse($fromInput)->startOfDay();
                } catch (\Throwable $e) {
                }
            }
            if ($toInput !== '') {
                try {
                    $filterEnd = Carbon::parse($toInput)->endOfDay();
                } catch (\Throwable $e) {
                }
            }

            $currentVisitors = (int) $this->publicViewsQuery()
                ->whereBetween('created_at', [$start, $end])
                ->sum('view_count');

            $previousVisitors = 0;
            $growth = 0;
            if ($period !== 'lifetime') {
                $previousVisitors = (int) $this->publicViewsQuery()
                    ->whereBetween('created_at', [$range['prevStart'], $range['prevEnd']])
                    ->sum('view_count');
                $growth = $previousVisitors > 0
                    ? round((($currentVisitors - $previousVisitors) / $previousVisitors) * 100, 1)
                    : ($currentVisitors > 0 ? 100 : 0);
            }

            $emptyPage = new LengthAwarePaginator([], 0, 100, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
            $emptyChart = [
                'labels' => ['No data'],
                'values' => [1],
                'percents' => [100],
                'colors' => ['#e0e0e0'],
                'sliceColors' => ['#e0e0e0'],
                'total' => 0,
            ];

            $dailyLabels = [];
            $dailyValues = [];
            $channels = $emptyChart;
            $locations = $emptyChart;
            $devices = $emptyChart;
            $liveCount = 0;
            $liveUsers = collect();
            $latestPageViews = $emptyPage;
            $mostVisited = $emptyPage;

            if ($tab === 'traffic') {
                [$dailyLabels, $dailyValues] = $this->buildChartSeries($start, $end);
                $channels = $this->aggregateChannels($start, $end);
                $locations = $this->aggregateLocations($start, $end);
                $devices = $this->aggregateDevices($start, $end);

                $liveSince = now()->subMinutes(5);
                $liveCount = (int) $this->publicViewsQuery()->where('updated_at', '>=', $liveSince)->count();
                $liveUsers = $this->publicViewsQuery()
                    ->where('updated_at', '>=', $liveSince)
                    ->orderByDesc('updated_at')
                    ->limit(25)
                    ->get(['url', 'ip_address', 'country', 'updated_at', 'view_count']);
            }

            if ($tab === 'visits') {
                $visitsQuery = $this->publicViewsQuery()
                    ->whereBetween('created_at', [$filterStart, $filterEnd]);
                $this->applySearch($visitsQuery, $search);
                $latestPageViews = $visitsQuery
                    ->orderByDesc('updated_at')
                    ->paginate(100)
                    ->withQueryString();
            }

            if ($tab === 'pages') {
                $pagesQuery = $this->publicViewsQuery()
                    ->whereBetween('created_at', [$filterStart, $filterEnd]);
                if ($search !== '') {
                    $pagesQuery->where('url', 'like', '%' . addcslashes($search, '%_\\') . '%');
                }
                $mostVisited = $pagesQuery
                    ->selectRaw('url, SUM(view_count) as visits, MAX(updated_at) as last_visit')
                    ->groupBy('url')
                    ->orderByDesc('visits')
                    ->paginate(100)
                    ->withQueryString();
            }

            return view('admin.analytics.index', [
                'period' => $period,
                'tab' => $tab,
                'periodLabel' => $range['label'],
                'comparisonLabel' => $period === 'lifetime' ? 'the start of tracking' : $range['comparisonLabel'],
                'currentVisitors' => $currentVisitors,
                'previousVisitors' => $previousVisitors,
                'growth' => $growth,
                'dailyLabels' => $dailyLabels,
                'dailyValues' => $dailyValues,
                'channels' => $channels,
                'locations' => $locations,
                'devices' => $devices,
                'latestPageViews' => $latestPageViews,
                'mostVisited' => $mostVisited,
                'liveCount' => $liveCount,
                'liveUsers' => $liveUsers,
                'search' => $search,
                'fromInput' => $fromInput !== '' ? $filterStart->toDateString() : '',
                'toInput' => $toInput !== '' ? $filterEnd->toDateString() : '',
                'start' => $start,
                'end' => $end,
            ]);
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
        $tab = (string) $request->query('tab', 'traffic');
        if (! in_array($tab, self::TABS, true)) {
            $tab = 'traffic';
        }

        $emptyChart = [
            'labels' => ['No data'],
            'values' => [1],
            'percents' => [100],
            'colors' => ['#e0e0e0'],
            'sliceColors' => ['#e0e0e0'],
            'total' => 0,
        ];

        $emptyPage = new LengthAwarePaginator([], 0, 100, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return [
            'period' => $period,
            'tab' => $tab,
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
            'latestPageViews' => $emptyPage,
            'mostVisited' => $emptyPage,
            'liveCount' => 0,
            'liveUsers' => collect(),
            'search' => '',
            'fromInput' => '',
            'toInput' => '',
            'start' => Carbon::today()->startOfDay(),
            'end' => Carbon::today()->endOfDay(),
        ];
    }

    private function publicViewsQuery()
    {
        $query = PageView::query()
            ->where('url', 'not like', '%/user-behavior%')
            ->where('url', 'not like', '%/admin/%')
            ->where('url', 'not like', '%/skin%')
            ->where('url', 'not like', '%/vendor/%')
            ->where('url', 'not like', '%/livewire%')
            ->where('url', 'not like', '%.js')
            ->where('url', 'not like', '%.css')
            ->where('url', 'not like', '%.map')
            ->where('url', 'not like', '%/frontend/%');

        return $query;
    }

    private function applySearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%' . addcslashes($search, '%_\\') . '%';
        $query->where(function ($q) use ($like) {
            $q->where('url', 'like', $like)
                ->orWhere('ip_address', 'like', $like)
                ->orWhere('country', 'like', $like);
        });
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
            $first = $this->publicViewsQuery()->min('created_at');
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

    private function buildChartSeries(Carbon $start, Carbon $end): array
    {
        $spanDays = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $driver = DB::connection()->getDriverName();

        if ($spanDays > 180) {
            $bucket = $driver === 'mysql'
                ? "DATE_FORMAT(created_at, '%Y-%m')"
                : "strftime('%Y-%m', created_at)";
            $rows = $this->publicViewsQuery()
                ->whereBetween('created_at', [$start, $end])
                ->selectRaw($bucket . ' as bucket, SUM(view_count) as total')
                ->groupByRaw($bucket)
                ->orderByRaw($bucket)
                ->pluck('total', 'bucket');

            $labels = [];
            $values = [];
            $cursor = $start->copy()->startOfMonth();
            while ($cursor <= $end) {
                $key = $cursor->format('Y-m');
                $labels[] = $cursor->format('M Y');
                $values[] = (int) ($rows[$key] ?? 0);
                $cursor->addMonth();
            }

            return [$labels, $values];
        }

        $bucket = $driver === 'mysql'
            ? 'DATE(created_at)'
            : "date(created_at)";
        $rows = $this->publicViewsQuery()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw($bucket . ' as bucket, SUM(view_count) as total')
            ->groupByRaw($bucket)
            ->orderByRaw($bucket)
            ->pluck('total', 'bucket');

        $labels = [];
        $values = [];
        for ($i = 0; $i < $spanDays; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('M j');
            $values[] = (int) ($rows[$date->toDateString()] ?? 0);
        }

        return [$labels, $values];
    }

    private function aggregateChannels(Carbon $start, Carbon $end): array
    {
        $totals = [
            'Direct' => 0,
            'Organic Search' => 0,
            'Organic Social' => 0,
            'Referral' => 0,
        ];

        $appHost = addcslashes(strtolower(parse_url((string) config('app.url'), PHP_URL_HOST) ?? ''), '%_\\');
        $channelSql = "CASE
            WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
            WHEN '" . $appHost . "' != '' AND LOWER(referrer) LIKE '%" . $appHost . "%' THEN 'Direct'
            WHEN LOWER(referrer) LIKE '%google.%' OR LOWER(referrer) LIKE '%bing.%' OR LOWER(referrer) LIKE '%yahoo.%' OR LOWER(referrer) LIKE '%duckduckgo.%' OR LOWER(referrer) LIKE '%baidu.%' THEN 'Organic Search'
            WHEN LOWER(referrer) LIKE '%facebook.%' OR LOWER(referrer) LIKE '%instagram.%' OR LOWER(referrer) LIKE '%linkedin.%' OR LOWER(referrer) LIKE '%tiktok.%' OR LOWER(referrer) LIKE '%youtube.%' OR LOWER(referrer) LIKE '%twitter.%' OR LOWER(referrer) LIKE '%t.co%' OR LOWER(referrer) LIKE '%pinterest.%' THEN 'Organic Social'
            ELSE 'Referral'
        END";

        if (! Schema::hasColumn('page_views', 'referrer')) {
            $totals['Direct'] = (int) $this->publicViewsQuery()
                ->whereBetween('created_at', [$start, $end])
                ->sum('view_count');

            return $this->toChartSlices($totals, ['#f4b400', '#4285f4', '#9c27b0', '#34a853']);
        }

        $driver = DB::connection()->getDriverName();
        if ($driver !== 'mysql') {
            $channelSql = "CASE
                WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                ELSE 'Referral'
            END";
        }

        $rows = $this->publicViewsQuery()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw($channelSql . ' as channel, SUM(view_count) as total')
            ->groupByRaw($channelSql)
            ->pluck('total', 'channel');

        foreach ($rows as $channel => $total) {
            if (! array_key_exists((string) $channel, $totals)) {
                $totals['Referral'] += (int) $total;
                continue;
            }
            $totals[$channel] += (int) $total;
        }

        return $this->toChartSlices($totals, ['#f4b400', '#4285f4', '#9c27b0', '#34a853']);
    }

    private function aggregateLocations(Carbon $start, Carbon $end): array
    {
        $countryExpr = "COALESCE(NULLIF(country, ''), 'Unknown')";
        $rows = $this->publicViewsQuery()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw($countryExpr . ' as country, SUM(view_count) as total')
            ->groupByRaw($countryExpr)
            ->orderByDesc('total')
            ->get();

        $totals = [];
        foreach ($rows as $row) {
            $totals[$row->country] = (int) $row->total;
        }

        return $this->toChartSlices($totals, ['#4285f4', '#34a853', '#f4b400', '#ea4335', '#9c27b0', '#00acc1', '#ff6d00', '#5c6bc0']);
    }

    private function aggregateDevices(Carbon $start, Carbon $end): array
    {
        $rows = $this->publicViewsQuery()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('platform, browser, SUM(view_count) as total')
            ->groupBy('platform', 'browser')
            ->get();

        $totals = ['Desktop' => 0, 'Mobile' => 0, 'Tablet' => 0, 'Other' => 0];
        foreach ($rows as $row) {
            $platform = strtolower($row->platform ?? '');
            $ua = strtolower($row->browser ?? '');
            $count = (int) $row->total;
            if (str_contains($platform, 'android') || str_contains($ua, 'mobile')) {
                $totals['Mobile'] += $count;
            } elseif (str_contains($platform, 'ipad') || str_contains($ua, 'tablet')) {
                $totals['Tablet'] += $count;
            } elseif (in_array($platform, ['windows', 'mac', 'macos', 'linux', 'chrome os'], true) || $platform !== '') {
                $totals['Desktop'] += $count;
            } else {
                $totals['Other'] += $count;
            }
        }

        return $this->toChartSlices(array_filter($totals), ['#4285f4', '#34a853', '#f4b400', '#9e9e9e']);
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
