@extends('admin.layout.app')
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
    .site-kit-chart-wrap {
        position: relative;
        height: 280px;
    }
    .site-kit-donut-wrap {
        position: relative;
        height: 220px;
        max-width: 220px;
        margin: 0 auto;
    }
    .breakdown-tabs .btn {
        border-radius: 20px;
        margin-right: 6px;
        margin-bottom: 8px;
    }
    .breakdown-tabs .btn.active {
        background: #e8f0fe;
        color: #1a73e8;
        border-color: #d2e3fc;
    }
    .channel-legend {
        list-style: none;
        padding: 0;
        margin: 16px 0 0;
    }
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
    .site-kit-source {
        font-size: 12px;
        color: #5f6368;
        margin-top: 12px;
    }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }
    .top-page-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f1f3f4;
        font-size: 14px;
    }
    .date-range-select {
        min-width: 140px;
    }
</style>
@endpush

@section('content')
<div class="row wrapper border-bottom white-bg page-heading" style="padding-bottom:0;">
    <div class="col-lg-12">
        <div class="site-kit-header">
            <div class="site-kit-logo">
                <span class="g-icon">G</span>
                <span>Site Kit</span>
            </div>
            <form method="GET" class="form-inline">
                <select name="days" class="form-control date-range-select" onchange="this.form.submit()">
                    <option value="7" @selected($days === 7)>Last 7 days</option>
                    <option value="28" @selected($days === 28)>Last 28 days</option>
                    <option value="90" @selected($days === 90)>Last 90 days</option>
                </select>
            </form>
        </div>

        <div class="site-kit-tabs">
            <a href="#traffic" class="site-kit-tab active" data-tab="traffic">Traffic</a>
            <a href="#content" class="site-kit-tab" data-tab="content">Content</a>
        </div>
    </div>
</div>

<div class="wrapper wrapper-content" style="padding-top:0;">
    {{-- TRAFFIC TAB --}}
    <div id="panel-traffic" class="tab-panel active">
        <div class="site-kit-card">
            <h4 style="margin-top:0;font-weight:500;">Find out how your audience is growing</h4>
            <p class="text-muted">Track your site's traffic over time.</p>

            <div class="row">
                <div class="col-md-8">
                    <div class="text-muted" style="font-size:13px;">All Visitors</div>
                    <div class="site-kit-metric">{{ number_format($currentVisitors) }}</div>
                    @php
                        $growthClass = $growth > 0 ? 'up' : ($growth < 0 ? 'down' : 'flat');
                        $growthSign = $growth > 0 ? '+' : '';
                    @endphp
                    <div class="site-kit-growth {{ $growthClass }}">
                        {{ $growthSign }}{{ $growth }}% compared to the previous {{ $days }} days
                    </div>
                    <div class="site-kit-chart-wrap m-t-md">
                        <canvas id="trafficLineChart"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="breakdown-tabs text-center m-b-sm">
                        <button type="button" class="btn btn-sm btn-default breakdown-btn active" data-breakdown="channels">Channels</button>
                        <button type="button" class="btn btn-sm btn-default breakdown-btn" data-breakdown="locations">Locations</button>
                        <button type="button" class="btn btn-sm btn-default breakdown-btn" data-breakdown="devices">Devices</button>
                    </div>

                    <div class="breakdown-panel" id="breakdown-channels">
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
                    <div class="breakdown-panel" id="breakdown-locations" style="display:none;">
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
                    <div class="breakdown-panel" id="breakdown-devices" style="display:none;">
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
                    <div class="site-kit-source text-right">Source: Analytics</div>
                </div>
            </div>
        </div>

        <div class="site-kit-card">
            <h5 style="margin-top:0;">All page visits</h5>
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
                                <td><a href="{{ $row->url }}" target="_blank">{{ Str::limit($row->url, 50) }}</a></td>
                                <td>{{ $row->ip_address }}</td>
                                <td>{{ $row->created_at?->format('M d, Y H:i') }}</td>
                                <td>{{ $row->updated_at?->format('M d, Y H:i') }}</td>
                                <td>{{ analytics_channel($row->referrer, $row->url) }}</td>
                                <td>{{ $row->country ?: '-' }}</td>
                                <td>{{ $row->view_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">Browse the public site to collect analytics data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {!! $latestPageViews->links() !!}
        </div>
    </div>

    {{-- CONTENT TAB --}}
    <div id="panel-content" class="tab-panel">
        <div class="site-kit-card">
            <h4 style="margin-top:0;font-weight:500;">Find out how your content is performing</h4>
            <p class="text-muted">Top pages by views in the last {{ $days }} days.</p>
            @forelse($topPages as $page => $count)
                <div class="top-page-row">
                    <span>{{ $page }}</span>
                    <strong>{{ number_format($count) }} views</strong>
                </div>
            @empty
                <p class="text-muted">No page data yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.site-kit-tab').forEach(function (tab) {
        tab.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelectorAll('.site-kit-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('panel-' + this.dataset.tab).classList.add('active');
        });
    });

    document.querySelectorAll('.breakdown-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.breakdown-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.breakdown-panel').forEach(p => p.style.display = 'none');
            this.classList.add('active');
            document.getElementById('breakdown-' + this.dataset.breakdown).style.display = 'block';
        });
    });

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
