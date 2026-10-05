@extends(lms_portal_layout())
@section('title', 'Site Analytics')
@push('style')
<style>
    .site-kit-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 20px;
    }
    .site-kit-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 22px;
        font-weight: 600;
        color: #3c4043;
    }
    .site-kit-logo .g-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4285f4, #34a853, #fbbc05, #ea4335);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
    }
    .site-kit-tabs {
        border-bottom: 1px solid #dadce0;
        margin-bottom: 24px;
    }
    .site-kit-tabs a {
        display: inline-block;
        padding: 12px 20px;
        color: #5f6368;
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -1px;
        font-weight: 500;
    }
    .site-kit-tabs a.active {
        color: #1a73e8;
        border-bottom-color: #1a73e8;
    }
    .site-kit-card {
        background: #fff;
        border: 1px solid #dadce0;
        border-radius: 8px;
        padding: 24px;
        margin-bottom: 20px;
    }
    .site-kit-metric {
        font-size: 48px;
        font-weight: 400;
        color: #202124;
        line-height: 1.1;
        margin: 8px 0;
    }
    .site-kit-growth.up { color: #188038; }
    .site-kit-growth.down { color: #d93025; }
    .site-kit-growth.flat { color: #5f6368; }
    .site-kit-chart-wrap { position: relative; height: 280px; }
    .site-kit-donut-wrap { position: relative; height: 220px; max-width: 220px; margin: 0 auto; }
    .channel-legend { list-style: none; padding: 0; margin: 16px 0 0; max-height: 280px; overflow: auto; }
    .channel-legend li {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 13px;
        color: #3c4043;
    }
    .channel-legend .dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .date-range-select { min-width: 140px; }
    .url-cell { word-break: break-all; max-width: 520px; }
</style>
@endpush

@section('content')
@php
    $tab = $tab ?? 'traffic';
    $analyticsRoute = request()->routeIs('user.analytics') ? 'user.analytics' : 'admin.analytics';
    $tabUrl = function (string $name) use ($period, $analyticsRoute) {
        return route($analyticsRoute, ['days' => $period, 'tab' => $name]);
    };
@endphp
<div class="row wrapper border-bottom white-bg page-heading" style="padding-bottom:0;">
    <div class="col-lg-12">
        <div class="site-kit-header">
            <div class="site-kit-logo">
                <span class="g-icon">G</span>
                <span>Site Kit</span>
            </div>
            <form method="GET" class="form-inline">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <select name="days" class="form-control date-range-select" onchange="this.form.submit()">
                    <option value="today" @selected($period === 'today')>Today</option>
                    <option value="7" @selected($period === '7')>Last 7 days</option>
                    <option value="28" @selected($period === '28')>Last 28 days</option>
                    <option value="90" @selected($period === '90')>Last 90 days</option>
                    <option value="180" @selected($period === '180')>Last 180 days</option>
                    <option value="365" @selected($period === '365')>Last 365 days</option>
                    <option value="lifetime" @selected($period === 'lifetime')>Lifetime</option>
                </select>
            </form>
        </div>
        <div class="site-kit-tabs">
            <a href="{{ $tabUrl('traffic') }}" class="{{ $tab === 'traffic' ? 'active' : '' }}">Traffic</a>
            <a href="{{ $tabUrl('visits') }}" class="{{ $tab === 'visits' ? 'active' : '' }}">Page visits</a>
            <a href="{{ $tabUrl('pages') }}" class="{{ $tab === 'pages' ? 'active' : '' }}">Most visited pages</a>
        </div>
    </div>
</div>

<div class="wrapper wrapper-content" style="padding-top:0;">
    @if($tab === 'traffic')
        <div class="site-kit-card">
            <h4 style="margin-top:0;font-weight:500;">Find out how your audience is growing</h4>
            <p class="text-muted">Track your site's traffic over time.</p>
            <div class="text-muted" style="font-size:13px;">All Visitors</div>
            <div class="site-kit-metric">{{ number_format($currentVisitors) }}</div>
            @php
                $growthClass = $growth > 0 ? 'up' : ($growth < 0 ? 'down' : 'flat');
                $growthSign = $growth > 0 ? '+' : '';
            @endphp
            <div class="site-kit-growth {{ $growthClass }}">
                {{ $growthSign }}{{ $growth }}% compared to {{ $comparisonLabel }}
            </div>
            <div class="site-kit-chart-wrap m-t-md">
                <canvas id="trafficLineChart"></canvas>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="site-kit-card">
                    <h5 style="margin-top:0;">Channels</h5>
                    <div class="site-kit-donut-wrap"><canvas id="donutChannels"></canvas></div>
                    <ul class="channel-legend">
                        @foreach($channels['labels'] as $i => $label)
                            <li>
                                <span><span class="dot" style="background:{{ $channels['colors'][$i] ?? '#ccc' }}"></span>{{ $label }}</span>
                                <strong>{{ $channels['percents'][$i] ?? 0 }}%</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="site-kit-card">
                    <h5 style="margin-top:0;">Locations</h5>
                    <div class="site-kit-donut-wrap"><canvas id="donutLocations"></canvas></div>
                    <ul class="channel-legend">
                        @foreach($locations['labels'] as $i => $label)
                            <li>
                                <span><span class="dot" style="background:{{ $locations['colors'][$i] ?? '#ccc' }}"></span>{{ $label }}</span>
                                <strong>{{ $locations['percents'][$i] ?? 0 }}%</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-md-4">
                <div class="site-kit-card">
                    <h5 style="margin-top:0;">Devices</h5>
                    <div class="site-kit-donut-wrap"><canvas id="donutDevices"></canvas></div>
                    <ul class="channel-legend">
                        @foreach($devices['labels'] as $i => $label)
                            <li>
                                <span><span class="dot" style="background:{{ $devices['colors'][$i] ?? '#ccc' }}"></span>{{ $label }}</span>
                                <strong>{{ $devices['percents'][$i] ?? 0 }}%</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="site-kit-card">
            <h5 style="margin-top:0;">Live users <span class="label label-primary">{{ $liveCount }}</span></h5>
            <p class="text-muted">Active in the last 5 minutes.</p>
            <div class="table-responsive">
                <table class="table table-hover" style="margin-bottom:0;">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>IP</th>
                            <th>Country</th>
                            <th>Last seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($liveUsers as $row)
                            <tr>
                                <td class="url-cell"><a href="{{ $row->url }}" target="_blank" rel="noopener">{{ $row->url }}</a></td>
                                <td>{{ $row->ip_address }}</td>
                                <td>{{ $row->country ?: '—' }}</td>
                                <td>{{ $row->updated_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">No live users right now.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($tab === 'visits')
        <div class="site-kit-card">
            <h4 style="margin-top:0;font-weight:500;">All page visits</h4>
            <form method="GET" class="form-inline m-b-md" style="margin-bottom:16px;">
                <input type="hidden" name="tab" value="visits">
                <input type="hidden" name="days" value="{{ $period }}">
                <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Search URL, IP or country" style="min-width:260px;">
                <input type="date" name="from" class="form-control" value="{{ $fromInput }}">
                <input type="date" name="to" class="form-control" value="{{ $toInput }}">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ $tabUrl('visits') }}" class="btn btn-default">Clear</a>
            </form>
            <div class="table-responsive">
                <table class="table table-hover" style="margin-bottom:0;">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>IP</th>
                            <th>First Visit</th>
                            <th>Last Visit</th>
                            <th>Channel</th>
                            <th>Country</th>
                            <th>Visits</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestPageViews as $row)
                            <tr>
                                <td class="url-cell"><a href="{{ $row->url }}" target="_blank" rel="noopener">{{ $row->url }}</a></td>
                                <td>{{ $row->ip_address }}</td>
                                <td>{{ $row->created_at?->format('M d, Y H:i') }}</td>
                                <td>{{ $row->updated_at?->format('M d, Y H:i') }}</td>
                                <td>{{ analytics_channel($row->referrer ?? null, $row->url) }}</td>
                                <td>{{ $row->country ?: '—' }}</td>
                                <td>{{ $row->view_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No visits in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {!! $latestPageViews->links() !!}
        </div>
    @endif

    @if($tab === 'pages')
        <div class="site-kit-card">
            <h4 style="margin-top:0;font-weight:500;">Most visited pages</h4>
            <form method="GET" class="form-inline m-b-md" style="margin-bottom:16px;">
                <input type="hidden" name="tab" value="pages">
                <input type="hidden" name="days" value="{{ $period }}">
                <input type="text" name="q" class="form-control" value="{{ $search }}" placeholder="Search URL" style="min-width:260px;">
                <input type="date" name="from" class="form-control" value="{{ $fromInput }}">
                <input type="date" name="to" class="form-control" value="{{ $toInput }}">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ $tabUrl('pages') }}" class="btn btn-default">Clear</a>
            </form>
            <div class="table-responsive">
                <table class="table table-hover" style="margin-bottom:0;">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Views</th>
                            <th>Last visit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($mostVisited as $row)
                            <tr>
                                <td class="url-cell"><a href="{{ $row->url }}" target="_blank" rel="noopener">{{ $row->url }}</a></td>
                                <td>{{ number_format((int) $row->visits) }}</td>
                                <td>{{ $row->last_visit ? \Carbon\Carbon::parse($row->last_visit)->format('M d, Y H:i') : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No page data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {!! $mostVisited->links() !!}
        </div>
    @endif
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const lineCtx = document.getElementById('trafficLineChart');
    if (lineCtx && typeof Chart !== 'undefined') {
        new Chart(lineCtx, {
            type: 'line',
            data: {
                labels: @json($dailyLabels),
                datasets: [{
                    label: 'Visitors',
                    data: @json($dailyValues),
                    borderColor: '#1a73e8',
                    backgroundColor: 'rgba(26, 115, 232, 0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function makeDonut(canvasId, payload) {
        const el = document.getElementById(canvasId);
        if (!el || typeof Chart === 'undefined') return;
        new Chart(el, {
            type: 'doughnut',
            data: {
                labels: payload.labels,
                datasets: [{
                    data: payload.values,
                    backgroundColor: payload.colors,
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: { legend: { display: false } }
            }
        });
    }

    makeDonut('donutChannels', @json($channels));
    makeDonut('donutLocations', @json($locations));
    makeDonut('donutDevices', @json($devices));
});
</script>
@endpush
