@php
    $reg = $instructorOverviewStats['registration'] ?? [];
    $details = $instructorOverviewDetails ?? [];
    $overviewFrom = $instructorOverviewDateFrom ?? now()->startOfMonth()->toDateString();
    $overviewTo = $instructorOverviewDateTo ?? now()->toDateString();
    $overviewAction = $panelBaseUrl ?? route('admin.dashboard.instructors');
    $cards = [
        ['key' => 'reg_total', 'value' => (int) ($reg['total'] ?? 0), 'label' => 'Total Registered'],
        ['key' => 'reg_today', 'value' => (int) ($reg['today'] ?? 0), 'label' => 'Registered Today'],
        ['key' => 'reg_yesterday', 'value' => (int) ($reg['yesterday'] ?? 0), 'label' => 'Registered Yesterday'],
        ['key' => 'reg_this_week', 'value' => (int) ($reg['this_week'] ?? 0), 'label' => 'Registered This Week'],
        ['key' => 'reg_last_week', 'value' => (int) ($reg['last_week'] ?? 0), 'label' => 'Registered Last Week'],
        ['key' => 'reg_this_month', 'value' => (int) ($reg['this_month'] ?? 0), 'label' => 'Registered This Month'],
        ['key' => 'reg_last_month', 'value' => (int) ($reg['last_month'] ?? 0), 'label' => 'Registered Last Month'],
        ['key' => 'reg_this_year', 'value' => (int) ($reg['this_year'] ?? 0), 'label' => 'Registered This Year'],
        ['key' => 'reg_custom', 'value' => (int) ($reg['custom'] ?? 0), 'label' => 'Registered Custom Date Range'],
    ];
@endphp
<div class="row" style="padding: 0 10px 12px;" id="instructors-overview-panel">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5 style="margin:0;font-weight:700;text-transform:uppercase;letter-spacing:.02em;">Instructors Overview</h5>
            </div>
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
                    #instructors-overview-panel .instructor-overview-range {
                        display: flex;
                        flex-wrap: wrap;
                        align-items: flex-end;
                        gap: 8px;
                        margin-bottom: 14px;
                    }
                    #instructors-overview-panel .instructor-overview-range .form-group {
                        margin: 0;
                    }
                </style>

                <ul class="nav nav-tabs instructor-overview-tabs" role="tablist" style="margin-bottom:16px;border-bottom:none;">
                    <li class="active">
                        <a href="#instructor-overview-registration" data-toggle="tab">Overview</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="instructor-overview-registration">
                        <form method="GET" action="{{ $overviewAction }}" class="instructor-overview-range">
                            @foreach(request()->except(['overview_date_from', 'overview_date_to', 'page']) as $qKey => $qVal)
                                @if(is_array($qVal))
                                    @continue
                                @endif
                                <input type="hidden" name="{{ $qKey }}" value="{{ $qVal }}">
                            @endforeach
                            <div class="form-group">
                                <label for="overview_date_from" class="m-r-xs">From</label>
                                <input type="date" id="overview_date_from" name="overview_date_from" class="form-control" value="{{ $overviewFrom }}">
                            </div>
                            <div class="form-group">
                                <label for="overview_date_to" class="m-r-xs">To</label>
                                <input type="date" id="overview_date_to" name="overview_date_to" class="form-control" value="{{ $overviewTo }}">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Search</button>
                        </form>
                        <p class="text-muted small m-b-sm">
                            Custom range: {{ $overviewFrom }} - {{ $overviewTo }}
                        </p>
                        <div class="row instructor-stat-row">
                            @foreach($cards as $card)
                                <div class="col-sm-3">
                                    <a href="javascript:void(0)"
                                       class="instructor-stat js-instructor-metric"
                                       data-metric="{{ $card['key'] }}"
                                       data-title="{{ $card['label'] }}">
                                        <div class="instructor-stat-value">{{ $card['value'] }}</div>
                                        <div class="instructor-stat-label">{{ $card['label'] }}</div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-muted small m-t-sm m-b-none">Click a box to view the list of people in that count.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.dashboard.partials.people-metric-modal', [
    'modalId' => 'instructorMetricModal',
    'details' => $details,
    'triggerSelector' => '#instructors-overview-panel .js-instructor-metric',
])
