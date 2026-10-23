@php
    $reg = $instructorOverviewStats['registration'] ?? [];
    $locations = $instructorOverviewStats['locations'] ?? [
        'labels' => ['No data'],
        'values' => [1],
        'percents' => [100],
        'colors' => ['#e0e0e0'],
        'total' => 0,
    ];
    $details = $instructorOverviewDetails ?? [];
    $cards = [
        ['key' => 'reg_total', 'value' => (int) ($reg['total'] ?? 0), 'label' => 'Total Registered Instructors'],
        ['key' => 'reg_on_website', 'value' => (int) ($reg['on_website'] ?? 0), 'label' => 'Instructors Showing on Website'],
        ['key' => 'reg_today', 'value' => (int) ($reg['today'] ?? 0), 'label' => 'Registered Instructors Today'],
        ['key' => 'reg_yesterday', 'value' => (int) ($reg['yesterday'] ?? 0), 'label' => 'Registered Instructors Yesterday'],
        ['key' => 'reg_this_week', 'value' => (int) ($reg['this_week'] ?? 0), 'label' => 'Registered Instructors This Week'],
        ['key' => 'reg_last_week', 'value' => (int) ($reg['last_week'] ?? 0), 'label' => 'Registered Instructors Last Week'],
        ['key' => 'reg_this_month', 'value' => (int) ($reg['this_month'] ?? 0), 'label' => 'Registered Instructors This Month'],
        ['key' => 'reg_last_month', 'value' => (int) ($reg['last_month'] ?? 0), 'label' => 'Registered Instructors Last Month'],
        ['key' => 'reg_this_year', 'value' => (int) ($reg['this_year'] ?? 0), 'label' => 'Registered Instructors This Year'],
        ['key' => 'reg_custom', 'value' => null, 'label' => 'Registered Instructors - Custom Date Range', 'is_custom' => true],
    ];
@endphp
<div class="row" style="padding: 0 10px 12px;" id="instructors-overview-panel">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-content">
                <style>
                    #instructors-overview-panel .instructor-overview-tabs > li > a {
                        color: #fff !important;
                        border: none !important;
                        border-radius: 3px 3px 0 0;
                        margin-right: 4px;
                        padding: 10px 18px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: .02em;
                        background: #1c84c6;
                    }
                    #instructors-overview-panel .instructor-overview-tabs > li.active > a,
                    #instructors-overview-panel .instructor-overview-tabs > li > a:hover {
                        background: #155a8a;
                        color: #fff !important;
                    }
                    #instructors-overview-panel .instructor-stat-value {
                        font-size: 28px;
                        font-weight: 700;
                        line-height: 1.15;
                        margin: 0 0 6px;
                        color: #ed5565;
                    }
                    #instructors-overview-panel .instructor-stat-value.is-custom-icon {
                        color: #1c84c6;
                        font-size: 26px;
                    }
                    #instructors-overview-panel .instructor-stat-row {
                        display: flex;
                        flex-wrap: wrap;
                    }
                    #instructors-overview-panel .instructor-stat-row > [class*="col-"] {
                        display: flex;
                        float: none;
                        margin-bottom: 12px;
                        width: 20%;
                        max-width: 20%;
                        flex: 0 0 20%;
                    }
                    @media (max-width: 1199px) {
                        #instructors-overview-panel .instructor-stat-row > [class*="col-"] {
                            width: 33.333%;
                            max-width: 33.333%;
                            flex: 0 0 33.333%;
                        }
                    }
                    @media (max-width: 767px) {
                        #instructors-overview-panel .instructor-stat-row > [class*="col-"] {
                            width: 50%;
                            max-width: 50%;
                            flex: 0 0 50%;
                        }
                    }
                    #instructors-overview-panel a.instructor-stat {
                        display: flex;
                        flex-direction: column;
                        justify-content: flex-start;
                        width: 100%;
                        min-height: 96px;
                        border: 1px solid #e7eaec;
                        border-radius: 4px;
                        padding: 12px 14px;
                        background: #f9f9f9;
                        color: #676a6c;
                        text-decoration: none !important;
                        cursor: pointer;
                    }
                    #instructors-overview-panel a.instructor-stat:hover {
                        border-color: #1c84c6;
                        background: #fff;
                    }
                    #instructors-overview-panel .instructor-stat-label {
                        font-size: 11px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: .02em;
                        line-height: 1.35;
                    }
                    #instructors-overview-panel .instructor-locations {
                        margin-top: 22px;
                        padding-top: 18px;
                        border-top: 1px solid #e7eaec;
                    }
                    #instructors-overview-panel .instructor-locations-title {
                        margin: 0 0 14px;
                        font-size: 15px;
                        font-weight: 700;
                        color: #676a6c;
                    }
                    #instructors-overview-panel .instructor-locations-body {
                        display: flex;
                        align-items: flex-start;
                        gap: 20px;
                        flex-wrap: wrap;
                    }
                    #instructors-overview-panel .instructor-locations-donut {
                        position: relative;
                        flex: 0 0 220px;
                        width: 220px;
                        height: 220px;
                        margin: 0;
                    }
                    #instructors-overview-panel .instructor-locations-side {
                        flex: 1 1 200px;
                        min-width: 180px;
                        max-width: 420px;
                    }
                    #instructors-overview-panel .instructor-locations-legend {
                        list-style: none;
                        padding: 0;
                        margin: 0;
                        max-height: 280px;
                        overflow: auto;
                    }
                    #instructors-overview-panel .instructor-locations-legend li {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        gap: 12px;
                        padding: 6px 0;
                        border-bottom: 1px solid #f0f0f0;
                        font-size: 13px;
                        color: #676a6c;
                    }
                    #instructors-overview-panel .instructor-locations-legend li:last-child {
                        border-bottom: none;
                    }
                    #instructors-overview-panel .instructor-locations-legend .dot {
                        display: inline-block;
                        width: 10px;
                        height: 10px;
                        border-radius: 50%;
                        margin-right: 8px;
                        vertical-align: middle;
                    }
                </style>

                <ul class="nav nav-tabs instructor-overview-tabs" role="tablist" style="margin-bottom:16px;border-bottom:none;">
                    <li class="active">
                        <a href="#instructor-overview-registration" data-toggle="tab">Instructors Overview</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="instructor-overview-registration">
                        <div class="row instructor-stat-row">
                            @foreach($cards as $card)
                                <div class="col-sm-3">
                                    <a href="javascript:void(0)"
                                       class="instructor-stat js-instructor-metric"
                                       data-metric="{{ $card['key'] }}"
                                       data-title="{{ $card['label'] }}"
                                       data-custom="{{ !empty($card['is_custom']) ? '1' : '0' }}">
                                        @if(!empty($card['is_custom']))
                                            <div class="instructor-stat-value is-custom-icon instructor-stat-custom-value"><i class="fa fa-calendar"></i></div>
                                        @else
                                            <div class="instructor-stat-value">{{ $card['value'] }}</div>
                                        @endif
                                        <div class="instructor-stat-label">{{ $card['label'] }}</div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-muted small m-t-sm m-b-none">Click a box to view the list. Custom Date Range asks for From/To inside the popup (no default dates).</p>

                        <div class="instructor-locations">
                            <h4 class="instructor-locations-title">Locations</h4>
                            <div class="instructor-locations-body">
                                <div>
                                    <div class="instructor-locations-donut">
                                        <canvas id="instructorLocationsDonut"></canvas>
                                    </div>
                                    @if((int) ($locations['total'] ?? 0) > 0)
                                        <p class="text-center text-muted small m-b-none" style="margin-top:6px;">{{ (int) $locations['total'] }} instructors</p>
                                    @endif
                                </div>
                                <div class="instructor-locations-side">
                                    <ul class="instructor-locations-legend">
                                        @foreach(($locations['labels'] ?? []) as $i => $label)
                                            <li>
                                                <span>
                                                    <span class="dot" style="background:{{ $locations['colors'][$i] ?? '#ccc' }}"></span>
                                                    {{ $label }}
                                                </span>
                                                <strong>{{ (int) ($locations['values'][$i] ?? 0) }}</strong>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var el = document.getElementById('instructorLocationsDonut');
    if (!el || typeof Chart === 'undefined') return;
    var payload = @json($locations);
    new Chart(el.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: payload.labels || [],
            datasets: [{
                data: payload.values || [],
                backgroundColor: payload.colors || [],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            cutoutPercentage: 68,
            legend: { display: false },
            tooltips: {
                callbacks: {
                    label: function (tooltipItem, data) {
                        var label = (data.labels && data.labels[tooltipItem.index]) || '';
                        var value = (data.datasets[0].data && data.datasets[0].data[tooltipItem.index]) || 0;
                        return label + ': ' + value;
                    }
                }
            }
        }
    });
});
</script>

@include('admin.dashboard.partials.people-metric-modal', [
    'modalId' => 'instructorMetricModal',
    'details' => $details,
    'triggerSelector' => '#instructors-overview-panel .js-instructor-metric',
    'enableCustomRange' => true,
    'customMetricKey' => 'reg_custom',
    'customFrom' => '',
    'customTo' => '',
    'customFetchUrl' => route('admin.dashboard.instructors.people'),
    'exportUrl' => route('admin.dashboard.instructors.people-export'),
    'titlePrefix' => 'INSTRUCTORS',
    'showPhone' => true,
])
