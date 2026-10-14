@php
    $reg = $studentOverviewStats['registration'] ?? [];
    $enr = $studentOverviewStats['enrollment'] ?? [];
    $emptyChart = [
        'labels' => ['No data'],
        'values' => [1],
        'percents' => [100],
        'colors' => ['#e0e0e0'],
        'total' => 0,
    ];
    $locations = $studentOverviewStats['locations'] ?? $emptyChart;
    $enrolledCourses = $studentOverviewStats['enrolled_courses'] ?? $emptyChart;
    $details = $studentOverviewStatsDetails ?? ($studentOverviewDetails ?? []);
    $cards = [
        ['key' => 'reg_total', 'value' => (int) ($reg['total'] ?? 0), 'label' => 'Total Registered Students'],
        ['key' => 'reg_today', 'value' => (int) ($reg['today'] ?? 0), 'label' => 'Registered Students Today'],
        ['key' => 'reg_yesterday', 'value' => (int) ($reg['yesterday'] ?? 0), 'label' => 'Registered Students Yesterday'],
        ['key' => 'reg_this_week', 'value' => (int) ($reg['this_week'] ?? 0), 'label' => 'Registered Students This Week'],
        ['key' => 'reg_last_week', 'value' => (int) ($reg['last_week'] ?? 0), 'label' => 'Registered Students Last Week'],
        ['key' => 'reg_this_month', 'value' => (int) ($reg['this_month'] ?? 0), 'label' => 'Registered Students This Month'],
        ['key' => 'reg_last_month', 'value' => (int) ($reg['last_month'] ?? 0), 'label' => 'Registered Students Last Month'],
        ['key' => 'reg_this_year', 'value' => (int) ($reg['this_year'] ?? 0), 'label' => 'Registered Students This Year'],
        ['key' => 'reg_custom', 'value' => null, 'label' => 'Registered Students - Custom Date Range', 'is_custom' => true],
        ['key' => 'enr_enrolled_total', 'value' => (int) ($enr['enrolled_total'] ?? 0), 'label' => 'Enrolled Total Students'],
        ['key' => 'enr_enrolled_paid', 'value' => (int) ($enr['enrolled_paid'] ?? 0), 'label' => 'Invoice Fully Paid Students'],
        ['key' => 'enr_partially_paid_students', 'value' => (int) ($enr['partially_paid_students'] ?? 0), 'label' => 'Partially Paid Students'],
        ['key' => 'enr_invoice_unpaid_students', 'value' => (int) ($enr['invoice_unpaid_students'] ?? 0), 'label' => 'Invoice Issued And Unpaid Students'],
    ];
@endphp
<div class="row" style="padding: 0 10px 12px;" id="students-overview-panel">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-content">
                <style>
                    #students-overview-panel .student-overview-tabs > li > a {
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
                    #students-overview-panel .student-overview-tabs > li.active > a,
                    #students-overview-panel .student-overview-tabs > li > a:hover {
                        background: #155a8a;
                        color: #fff !important;
                    }
                    #students-overview-panel .student-stat-value {
                        font-size: 28px;
                        font-weight: 700;
                        line-height: 1.15;
                        margin: 0 0 6px;
                        color: #ed5565;
                    }
                    #students-overview-panel .student-stat-value.is-custom-icon {
                        color: #1c84c6;
                        font-size: 26px;
                    }
                    #students-overview-panel .student-stat-row {
                        display: flex;
                        flex-wrap: wrap;
                    }
                    #students-overview-panel .student-stat-row > [class*="col-"] {
                        display: flex;
                        float: none;
                        margin-bottom: 12px;
                        width: 20%;
                        max-width: 20%;
                        flex: 0 0 20%;
                    }
                    @media (max-width: 1199px) {
                        #students-overview-panel .student-stat-row > [class*="col-"] {
                            width: 33.333%;
                            max-width: 33.333%;
                            flex: 0 0 33.333%;
                        }
                    }
                    @media (max-width: 767px) {
                        #students-overview-panel .student-stat-row > [class*="col-"] {
                            width: 50%;
                            max-width: 50%;
                            flex: 0 0 50%;
                        }
                    }
                    #students-overview-panel a.student-stat {
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
                    #students-overview-panel a.student-stat:hover {
                        border-color: #1c84c6;
                        background: #fff;
                    }
                    #students-overview-panel .student-stat-label {
                        font-size: 11px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: .02em;
                        line-height: 1.35;
                    }
                    #students-overview-panel .student-charts {
                        margin-top: 22px;
                        padding-top: 18px;
                        border-top: 1px solid #e7eaec;
                        display: flex;
                        flex-wrap: wrap;
                        gap: 28px;
                    }
                    #students-overview-panel .student-chart-block {
                        flex: 1 1 420px;
                        min-width: 280px;
                    }
                    #students-overview-panel .student-chart-title {
                        margin: 0 0 14px;
                        font-size: 15px;
                        font-weight: 700;
                        color: #676a6c;
                    }
                    #students-overview-panel .student-chart-body {
                        display: flex;
                        align-items: flex-start;
                        gap: 20px;
                        flex-wrap: wrap;
                    }
                    #students-overview-panel .student-chart-donut {
                        position: relative;
                        flex: 0 0 220px;
                        width: 220px;
                        height: 220px;
                        margin: 0;
                    }
                    #students-overview-panel .student-chart-side {
                        flex: 1 1 200px;
                        min-width: 180px;
                        max-width: 420px;
                    }
                    #students-overview-panel .student-chart-legend {
                        list-style: none;
                        padding: 0;
                        margin: 0;
                        max-height: 280px;
                        overflow: auto;
                    }
                    #students-overview-panel .student-chart-legend li {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        gap: 12px;
                        padding: 6px 0;
                        border-bottom: 1px solid #f0f0f0;
                        font-size: 13px;
                        color: #676a6c;
                    }
                    #students-overview-panel .student-chart-legend li:last-child {
                        border-bottom: none;
                    }
                    #students-overview-panel .student-chart-legend .dot {
                        display: inline-block;
                        width: 10px;
                        height: 10px;
                        border-radius: 50%;
                        margin-right: 8px;
                        vertical-align: middle;
                    }
                    #students-overview-panel .student-chart-legend .label-text {
                        min-width: 0;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                        max-width: 280px;
                        display: inline-block;
                        vertical-align: middle;
                    }
                </style>

                <ul class="nav nav-tabs student-overview-tabs" role="tablist" style="margin-bottom:16px;border-bottom:none;">
                    <li class="active">
                        <a href="#student-overview-registration" data-toggle="tab">Students Overview</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="student-overview-registration">
                        <div class="row student-stat-row">
                            @foreach($cards as $card)
                                <div class="col-sm-3">
                                    <a href="javascript:void(0)"
                                       class="student-stat js-student-metric"
                                       data-metric="{{ $card['key'] }}"
                                       data-title="{{ $card['label'] }}"
                                       data-custom="{{ !empty($card['is_custom']) ? '1' : '0' }}">
                                        @if(!empty($card['is_custom']))
                                            <div class="student-stat-value is-custom-icon student-stat-custom-value"><i class="fa fa-calendar"></i></div>
                                        @else
                                            <div class="student-stat-value">{{ $card['value'] }}</div>
                                        @endif
                                        <div class="student-stat-label">{{ $card['label'] }}</div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-muted small m-t-sm m-b-none">Click a box to view the list. Custom Date Range has no default count — pick From/To inside the popup.</p>

                        <div class="student-charts">
                            <div class="student-chart-block">
                                <h4 class="student-chart-title">Locations</h4>
                                <div class="student-chart-body">
                                    <div>
                                        <div class="student-chart-donut">
                                            <canvas id="studentLocationsDonut"></canvas>
                                        </div>
                                        @if((int) ($locations['total'] ?? 0) > 0)
                                            <p class="text-center text-muted small m-b-none" style="margin-top:6px;">{{ (int) $locations['total'] }} students</p>
                                        @endif
                                    </div>
                                    <div class="student-chart-side">
                                        <ul class="student-chart-legend">
                                            @foreach(($locations['labels'] ?? []) as $i => $label)
                                                <li>
                                                    <span>
                                                        <span class="dot" style="background:{{ $locations['colors'][$i] ?? '#ccc' }}"></span>
                                                        <span class="label-text" title="{{ $label }}">{{ $label }}</span>
                                                    </span>
                                                    <strong>{{ (int) ($locations['values'][$i] ?? 0) }}</strong>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="student-chart-block">
                                <h4 class="student-chart-title">Enrolled Courses</h4>
                                <div class="student-chart-body">
                                    <div>
                                        <div class="student-chart-donut">
                                            <canvas id="studentEnrolledCoursesDonut"></canvas>
                                        </div>
                                        @if((int) ($enrolledCourses['total'] ?? 0) > 0)
                                            <p class="text-center text-muted small m-b-none" style="margin-top:6px;">{{ (int) $enrolledCourses['total'] }} enrollments</p>
                                        @endif
                                    </div>
                                    <div class="student-chart-side">
                                        <ul class="student-chart-legend">
                                            @foreach(($enrolledCourses['labels'] ?? []) as $i => $label)
                                                <li>
                                                    <span>
                                                        <span class="dot" style="background:{{ $enrolledCourses['colors'][$i] ?? '#ccc' }}"></span>
                                                        <span class="label-text" title="{{ $label }}">{{ $label }}</span>
                                                    </span>
                                                    <strong>{{ (int) ($enrolledCourses['values'][$i] ?? 0) }}</strong>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    function makeStudentDonut(canvasId, payload) {
        var el = document.getElementById(canvasId);
        if (!el) return;
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
                responsive: true,
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
    }

    makeStudentDonut('studentLocationsDonut', @json($locations));
    makeStudentDonut('studentEnrolledCoursesDonut', @json($enrolledCourses));
});
</script>

@include('admin.dashboard.partials.people-metric-modal', [
    'modalId' => 'studentMetricModal',
    'details' => $details,
    'triggerSelector' => '#students-overview-panel .js-student-metric',
    'enableCustomRange' => true,
    'customMetricKey' => 'reg_custom',
    'customFrom' => '',
    'customTo' => '',
    'customFetchUrl' => route('admin.dashboard.students.people'),
    'exportUrl' => route('admin.dashboard.students.people-export'),
    'titlePrefix' => 'STUDENTS',
    'showPhone' => true,
])
