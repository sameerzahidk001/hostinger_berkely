@extends('admin.layout.app')
@section('title', $pageTitle ?? 'Dashboard')

@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container { width: 100% !important; min-width: 220px; }
.select2-container .select2-selection--single {
    height: 34px;
    border: 1px solid #e5e6e7;
    border-radius: 1px;
}
.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 32px;
    padding-left: 12px;
}
.select2-container .select2-selection--single .select2-selection__arrow { height: 32px; }
#activities-panel .ibox-title h5 {
    margin: 0;
    font-weight: 700;
    text-transform: uppercase;
}
#activities-panel .btn {
    font-weight: 700;
    text-transform: uppercase;
}
#activities-panel .activity-module-tabs > li > a {
    color: #fff !important;
    border: none !important;
    border-radius: 3px 3px 0 0;
    margin-right: 4px;
    padding: 10px 18px;
    font-weight: 700;
    text-transform: uppercase;
}
#activities-panel .activity-module-tabs > li > a.tab-staff { background: #6fa8a8; }
#activities-panel .activity-module-tabs > li > a.tab-student { background: #c48bb8; }
#activities-panel .activity-module-tabs > li.active > a.tab-staff,
#activities-panel .activity-module-tabs > li > a.tab-staff:hover,
#activities-panel .activity-module-tabs > li > a.tab-staff:focus { background: #5a9494; color: #fff !important; }
#activities-panel .activity-module-tabs > li.active > a.tab-student,
#activities-panel .activity-module-tabs > li > a.tab-student:hover,
#activities-panel .activity-module-tabs > li > a.tab-student:focus { background: #b077a4; color: #fff !important; }
#activities-panel .activity-filter-row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 8px;
    margin-bottom: 12px;
}
#activities-panel .activity-filter-row .form-group { margin: 0; }
#activities-panel #user_id + .select2-container,
#activities-panel #student_user_id + .select2-container { width: 220px !important; min-width: 220px !important; max-width: 280px !important; }
#content-reports-panel #content_report_user + .select2-container {
    width: 280px !important;
    min-width: 280px !important;
    max-width: 280px !important;
}
#content-reports-panel #contentReportUserForm .select2-selection__rendered {
    max-width: 240px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
#activities-panel .activity-filter-search {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}
#activities-panel .activity-filter-exports {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-left: auto;
}
</style>
@endpush

@section('content')
@if(!($activityLogsEnabled ?? true))
<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="alert alert-warning">
            <strong>Login/log out tracking is not active.</strong>
            Run <code>database/sql/create-user-activity-logs.sql</code> on the live database (or
            <code>php artisan migrate --path=database/migrations/2026_06_19_000001_create_user_activity_logs_table.php --force</code>),
            then sign in/out again to record session activity.
        </div>
    </div>
</div>
@endif
@if(($showSiteStats ?? false) && !($summary['pages_status_enabled'] ?? true))
<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="alert alert-warning">
            <strong>Page disable is not active on the database.</strong>
            Run <code>database/sql/add-pages-status-column.sql</code> on live (or
            <code>bash deploy-hostinger.sh</code>) before Active/Disabled pages will save or appear on the dashboard.
        </div>
    </div>
</div>
@endif
@php
    $panelBaseUrl = $panelBaseUrl ?? route('admin.home');
@endphp
@if(!empty($pageTitle) && ($pageTitle ?? '') !== 'Dashboard')
<div class="row wrapper border-bottom white-bg page-heading" style="margin:0;">
    <div class="col-lg-12">
        <h2>{{ $pageTitle }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.dashboard.content-writers') }}">Dashboard</a></li>
            <li class="active"><strong>{{ $pageTitle }}</strong></li>
        </ol>
    </div>
</div>
@endif
@if($showStudentOverview ?? false)
    @include('admin.dashboard.partials.students-overview', [
        'studentOverviewStats' => $studentOverviewStats ?? [],
        'studentOverviewDetails' => $studentOverviewDetails ?? [],
    ])
@endif
@if($showInstructorOverview ?? false)
    @include('admin.dashboard.partials.instructors-overview', [
        'instructorOverviewStats' => $instructorOverviewStats ?? [],
        'instructorOverviewDetails' => $instructorOverviewDetails ?? [],
        'instructorOverviewDateFrom' => $instructorOverviewDateFrom ?? null,
        'instructorOverviewDateTo' => $instructorOverviewDateTo ?? null,
        'panelBaseUrl' => $panelBaseUrl ?? route('admin.dashboard.instructors'),
    ])
@endif
<div class="wrapper wrapper-content animated fadeInRight" style="padding: 20px 10px 0px;">
    <div class="row">
        {{-- Colored summary cards duplicate Content performance reports; hide when reports are shown. --}}
        @if(!($showContentReports ?? false) && !($showStudentOverview ?? false) && !($showInstructorOverview ?? false))
        @if($showMyStats ?? false)
        <div class="col-lg-3 col-md-6">
            <div class="widget style1 navy-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-files-o fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>My Courses</span>
                        <h2 class="font-bold">{{ $summary['my_courses'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="widget style1 lazur-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-sitemap fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>My Pages</span>
                        <h2 class="font-bold">{{ $summary['my_pages'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($showSiteStats ?? false)
        <div class="col-lg-3 col-md-6">
            <div class="widget style1 yellow-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-files-o fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Active Courses</span>
                        <h2 class="font-bold">{{ $summary['active_courses_site'] ?? $summary['total_courses_site'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('admin.course.disabled') }}" class="widget style1 red-bg" style="display:block;color:#fff;">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-ban fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Disabled Courses</span>
                        <h2 class="font-bold">{{ $summary['disabled_courses_site'] ?? 0 }}</h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="widget style1 blue-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-sitemap fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Active Pages</span>
                        <h2 class="font-bold">{{ $summary['active_pages_site'] ?? $summary['total_pages_site'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('admin.pages.disabled') }}" class="widget style1 lazur-bg" style="display:block;color:#fff;">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-eye-slash fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Disabled Pages</span>
                        <h2 class="font-bold">{{ $summary['disabled_pages_site'] ?? 0 }}</h2>
                    </div>
                </div>
            </a>
        </div>
        @if(($includePayments ?? false) && !($showInvoiceStats ?? false))
        <div class="col-lg-3 col-md-6">
            <div class="widget style1 red-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-file-text-o fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Total Invoices</span>
                        <h2 class="font-bold">{{ $summary['invoice_total'] ?? $summary['total_payments_site'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        @endif
        @endif
        @endif

        @if($showInvoiceStats ?? false)
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('admin.payments.index') }}" class="widget style1 red-bg" style="display:block;color:#fff;">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-file-text-o fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Unpaid Invoices</span>
                        <h2 class="font-bold">{{ $summary['invoice_unpaid'] }}</h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('admin.payments.index') }}" class="widget style1 yellow-bg" style="display:block;color:#fff;">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-adjust fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Partially Paid</span>
                        <h2 class="font-bold">{{ $summary['invoice_partial'] }}</h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('admin.payments.index') }}" class="widget style1 navy-bg" style="display:block;color:#fff;">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-check-circle fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Paid Invoices</span>
                        <h2 class="font-bold">{{ $summary['invoice_paid'] }}</h2>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6">
            <a href="{{ route('admin.payments.index') }}" class="widget style1 lazur-bg" style="display:block;color:#fff;">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-list-alt fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>All Invoices</span>
                        <h2 class="font-bold">{{ $summary['invoice_total'] }}</h2>
                    </div>
                </div>
            </a>
        </div>
        @endif
    </div>
</div>

@if($showContentReports ?? false)
    @include('admin.dashboard.content-reports')
@endif

@php
    $activityTab = request('activity_tab', ($studentActivitiesOnly ?? false) ? 'student' : 'staff');
    if (! in_array($activityTab, ['staff', 'student'], true)) {
        $activityTab = 'staff';
    }
    if (! ($showStudentTable ?? false)) {
        $activityTab = 'staff';
    }
    // Date, Activity, Description, Links (+ optional User / Session).
    $staffColspan = 4
        + (($showUserColumn ?? false) ? 1 : 0)
        + (($showSessionColumn ?? false) ? 1 : 0);
    $preserveReportUser = request('report_user');
    $activityRoleFilter = $activityRoleFilter ?? request('role');
    $staffClearQuery = array_filter([
        'report_user' => $preserveReportUser,
        'activity_tab' => 'staff',
        'student_user_id' => request('student_user_id'),
        'role' => $activityRoleFilter,
        'date_from' => now()->toDateString(),
        'date_to' => now()->toDateString(),
    ], fn ($v) => $v !== null && $v !== '');
    $studentClearQuery = array_filter([
        'report_user' => $preserveReportUser,
        'activity_tab' => 'student',
        'role' => $activityRoleFilter ?? request('role'),
        'user_id' => request('user_id'),
    ], fn ($v) => $v !== null && $v !== '');
    $staffExportQuery = array_filter(array_merge(request()->query(), [
        'type' => 'staff',
        'role' => $activityRoleFilter ?? request('role'),
        'date_from' => $activityDateFrom ?? request('date_from', now()->toDateString()),
        'date_to' => $activityDateTo ?? request('date_to', now()->toDateString()),
    ]), fn ($v) => $v !== null && $v !== '');
@endphp

<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="ibox float-e-margins" style="margin-bottom: 16px;" id="activities-panel" data-viewer-tz="{{ $activityTimezone ?? viewer_timezone() }}">
            <div class="ibox-title">
                <h5>Activities</h5>
            </div>
            <div class="ibox-content">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif
                <style>
                    tr.activity-gap-row td { border-top: 3px solid #e74c3c !important; }
                    .activity-gap-marker { color: #e74c3c; font-weight: 700; letter-spacing: .5px; font-size: 11px; white-space: nowrap; }
                    .activity-gap-cell { background: #fff6f6; }
                    .activity-gap-line { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
                    .activity-gap-comment-form { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; flex: 1; min-width: 240px; }
                    .activity-gap-comment-form .form-control { max-width: 420px; }
                    .activity-gap-saved { margin-top: 4px; color: #676a6c; font-size: 12px; }
                    .activity-gap-saved-text { color: #e74c3c; font-weight: 700; }
                    .activity-gap-edit { display: inline-block; }
                    .activity-gap-edit > summary { list-style: none; cursor: pointer; display: inline-block; }
                    .activity-gap-edit > summary::-webkit-details-marker { display: none; }
                    .activity-gap-edit-fields { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 6px; }
                    .activity-tz-note { margin: 0 0 8px; color: #676a6c; }
                </style>
                @if(($showStudentTable ?? false) && !($studentActivitiesOnly ?? false))
                <ul class="nav nav-tabs activity-module-tabs" role="tablist" style="margin-bottom:16px;border-bottom:none;">
                    <li class="{{ $activityTab === 'staff' ? 'active' : '' }}">
                        <a href="#activity-staff" data-toggle="tab" class="tab-staff">{{ $activityTitle ?? 'Staff activity' }}</a>
                    </li>
                    <li class="{{ $activityTab === 'student' ? 'active' : '' }}">
                        <a href="#activity-student" data-toggle="tab" class="tab-student">{{ $studentActivityTitle ?? 'Student activity' }}</a>
                    </li>
                </ul>
                @endif

                <div class="tab-content">
                    @if(!($studentActivitiesOnly ?? false))
                    <div role="tabpanel" class="tab-pane {{ $activityTab === 'staff' || !($showStudentTable ?? false) ? 'active' : '' }}" id="activity-staff">
                        <form method="GET" action="{{ $panelBaseUrl }}" class="activity-filter-row" id="staffActivityFilterForm">
                            <input type="hidden" name="activity_tab" value="staff">
                            @if($preserveReportUser)
                                <input type="hidden" name="report_user" value="{{ $preserveReportUser }}">
                            @endif
                            @if(!empty($activityRoleFilter))
                                <input type="hidden" name="role" value="{{ $activityRoleFilter }}">
                            @endif
                            @if(request()->filled('student_user_id'))
                                <input type="hidden" name="student_user_id" value="{{ request('student_user_id') }}">
                            @endif
                            @if($showUserFilter ?? false)
                            <div class="form-group">
                                <label for="user_id" class="m-r-xs">User</label>
                                <select id="user_id" name="user_id" class="form-control activity-filter-select" style="width:220px;">
                                    <option value="" @selected(!request()->filled('user_id'))>{{ $userFilterEmptyLabel ?? 'None' }}</option>
                                    @foreach($filterUsers ?? [] as $filterUser)
                                        @php $roleName = $filterUser->roles->first()?->name; @endphp
                                        <option value="{{ $filterUser->id }}" @selected((string) request('user_id') === (string) $filterUser->id)>
                                            {{ $filterUser->name }} ({{ role_display_name($roleName) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="form-group">
                                <label for="staff_date_from" class="m-r-xs">From</label>
                                <input type="date" id="staff_date_from" name="date_from" class="form-control" value="{{ $activityDateFrom ?? request('date_from', now()->toDateString()) }}">
                            </div>
                            <div class="form-group">
                                <label for="staff_date_to" class="m-r-xs">To</label>
                                <input type="date" id="staff_date_to" name="date_to" class="form-control" value="{{ $activityDateTo ?? request('date_to', now()->toDateString()) }}">
                            </div>
                            <div class="activity-filter-search">
                                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                                <a href="{{ url($panelBaseUrl) . (empty($staffClearQuery) ? '' : ('?' . http_build_query($staffClearQuery))) }}" class="btn btn-default btn-sm">RESET</a>
                            </div>
                            <div class="activity-filter-exports">
                                <a href="{{ route('admin.home.activity-export', array_merge($staffExportQuery, ['format' => 'excel'])) }}"
                                    class="btn btn-success btn-sm">
                                    <i class="fa fa-file-excel-o"></i> Export Excel
                                </a>
                                <a href="{{ route('admin.home.activity-export', array_merge($staffExportQuery, ['format' => 'pdf'])) }}"
                                    class="btn btn-danger btn-sm">
                                    <i class="fa fa-file-pdf-o"></i> Export PDF
                                </a>
                            </div>
                        </form>
                        <p class="m-b-xs" style="font-weight:600;">
                            {{ $activityTitle ?? 'Activity history' }}
                            · Report for: {{ $activityDateFrom ?? request('date_from', now()->toDateString()) }} - {{ $activityDateTo ?? request('date_to', now()->toDateString()) }}
                        </p>
                        <p class="text-muted small m-b-sm">Leave the user dropdown on <strong>{{ $userFilterEmptyLabel ?? 'None' }}</strong> to show everyone in this list. Dates default to today.</p>
                        <p class="activity-tz-note"><strong>Time zone:</strong> {{ $activityTimezone ?? viewer_timezone() }}@if(!empty($activityTimezoneLabel)) ({{ $activityTimezoneLabel }})@endif — based on your location. Date &amp; Time values below use this zone.</p>
                        <style>
                            #staffActivityTable,
                            #studentActivityTable {
                                width: 100% !important;
                                table-layout: auto;
                            }
                            #staffActivityTable thead th,
                            #studentActivityTable thead th {
                                position: sticky;
                                top: 0;
                                background: #f5f5f5;
                                z-index: 2;
                                white-space: nowrap;
                            }
                            #staffActivityTable td:nth-child(4),
                            #studentActivityTable td:nth-child(4) {
                                word-break: break-word;
                            }
                        </style>
                        <div class="table-responsive" style="width:100%;max-height:70vh;overflow:auto;">
                            <table class="table table-striped table-bordered table-hover js-sortable-table" id="staffActivityTable">
                                <thead>
                                    <tr>
                                        <th data-sort-col="0" style="width: 170px;cursor:pointer;">Date &amp; Time <span class="sort-ind"></span></th>
                                        @if($showUserColumn ?? false)
                                            <th data-sort-col="1" style="width: 180px;cursor:pointer;">User <span class="sort-ind"></span></th>
                                        @endif
                                        @php
                                            $staffActCol = ($showUserColumn ?? false) ? 2 : 1;
                                            $staffDescCol = $staffActCol + 1;
                                            $staffSessionCol = $staffDescCol + 1;
                                            $staffVisitCol = ($showSessionColumn ?? false) ? ($staffSessionCol + 1) : $staffDescCol + 1;
                                        @endphp
                                        <th data-sort-col="{{ $staffActCol }}" style="width: 160px;cursor:pointer;">Activity <span class="sort-ind"></span></th>
                                        <th data-sort-col="{{ $staffDescCol }}" style="cursor:pointer;">Description <span class="sort-ind"></span></th>
                                        @if($showSessionColumn ?? false)
                                            <th data-sort-col="{{ $staffSessionCol }}" style="width: 140px;cursor:pointer;">Session <span class="sort-ind"></span></th>
                                        @endif
                                        <th data-sort-col="{{ $staffVisitCol }}" style="width: 110px;cursor:pointer;">Links <span class="sort-ind"></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $prevStaffActAt = null; @endphp
                                    @forelse($activities as $activity)
                                        @php
                                            $visitUrl = $activity['visit_url'] ?? null;
                                            $editUrl = $activity['edit_url'] ?? null;
                                            $fallbackUrl = $activity['url'] ?? null;
                                            if (! $editUrl && $fallbackUrl && (
                                                str_contains((string) $fallbackUrl, '/course/')
                                                || str_contains((string) $fallbackUrl, '/pages/')
                                                || str_contains((string) $fallbackUrl, '/courses-pages-seo/')
                                                || str_contains((string) $fallbackUrl, '/seo/')
                                                || str_contains((string) $fallbackUrl, '/training-calendar/')
                                                || str_contains((string) $fallbackUrl, '/course-agendas/')
                                                || str_contains((string) $fallbackUrl, '/fee/')
                                            )) {
                                                $editUrl = $fallbackUrl;
                                            }
                                            $curStaffAt = $activity['occurred_at'] ?? null;
                                            $gapFromStaff = $prevStaffActAt;
                                            $staffHasGap = $gapFromStaff && $curStaffAt && abs($curStaffAt->getTimestamp() - $gapFromStaff->getTimestamp()) > 600;
                                            $prevStaffActAt = $curStaffAt ?: $prevStaffActAt;
                                        @endphp
                                        @if(!empty($staffHasGap))
                                            @include('admin.dashboard.partials.activity-gap-row', [
                                                'scope' => 'staff',
                                                'fromAt' => $gapFromStaff,
                                                'toAt' => $curStaffAt,
                                                'colspan' => $staffColspan,
                                                'activityGapComments' => $activityGapComments ?? [],
                                            ])
                                        @endif
                                        <tr class="{{ !empty($staffHasGap) ? 'activity-gap-row' : '' }}">
                                            <td data-sort="{{ $activity['occurred_at']->timestamp }}">{{ format_in_viewer_tz($activity['occurred_at']) }}</td>
                                            @if($showUserColumn ?? false)
                                                <td>{{ $activity['user_name'] ?? '—' }}</td>
                                            @endif
                                            <td>{{ activity_action_label($activity['action'] ?? '') }}</td>
                                            <td>{{ $activity['item'] }}</td>
                                            @if($showSessionColumn ?? false)
                                                <td>
                                                    @if(!empty($activity['session_id']))
                                                        <span class="text-muted small" title="{{ $activity['session_id'] }}">{{ \Illuminate\Support\Str::limit($activity['session_id'], 12) }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td>
                                                @if(!empty($visitUrl))
                                                    <a href="{{ $visitUrl }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">Visit</a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                                <br>
                                                @if(!empty($editUrl))
                                                    <a href="{{ $editUrl }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">Edit</a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $staffColspan }}" class="text-center text-muted">
                                                No activity found for the selected filters.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            {!! $activities->appends(array_merge(request()->query(), ['activity_tab' => 'staff']))->links() !!}
                        </div>
                    </div>
                    @endif

                    @if($showStudentTable ?? false)
                    <div role="tabpanel" class="tab-pane {{ ($activityTab === 'student' || ($studentActivitiesOnly ?? false)) ? 'active' : '' }}" id="activity-student">
                        <form method="GET" action="{{ $panelBaseUrl }}" class="activity-filter-row" id="studentActivityFilterForm">
                            <input type="hidden" name="activity_tab" value="student">
                            @if($preserveReportUser)
                                <input type="hidden" name="report_user" value="{{ $preserveReportUser }}">
                            @endif
                            @if(request()->filled('role'))
                                <input type="hidden" name="role" value="{{ request('role') }}">
                            @endif
                            @if(request()->filled('user_id'))
                                <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                            @endif
                            <div class="form-group">
                                <label for="student_user_id" class="m-r-xs">Student</label>
                                <select id="student_user_id" name="student_user_id" class="form-control activity-filter-select" style="width:220px;">
                                    <option value="" @selected(!request()->filled('student_user_id'))>None</option>
                                    @foreach($studentFilterUsers ?? [] as $studentUser)
                                        <option value="{{ $studentUser->id }}" @selected((string) request('student_user_id') === (string) $studentUser->id)>
                                            {{ $studentUser->name }} ({{ $studentUser->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="student_date_from" class="m-r-xs">From</label>
                                <input type="date" id="student_date_from" name="date_from" class="form-control" value="{{ $activityDateFrom ?? request('date_from', now()->toDateString()) }}">
                            </div>
                            <div class="form-group">
                                <label for="student_date_to" class="m-r-xs">To</label>
                                <input type="date" id="student_date_to" name="date_to" class="form-control" value="{{ $activityDateTo ?? request('date_to', now()->toDateString()) }}">
                            </div>
                            <div class="activity-filter-search">
                                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                                <a href="{{ url($panelBaseUrl) . (empty($studentClearQuery) ? '' : ('?' . http_build_query($studentClearQuery))) }}" class="btn btn-default btn-sm">RESET</a>
                            </div>
                            <div class="activity-filter-exports">
                                <a href="{{ route('admin.home.activity-export', array_merge(request()->query(), ['type' => 'student', 'format' => 'excel'])) }}"
                                    class="btn btn-success btn-sm">
                                    <i class="fa fa-file-excel-o"></i> Export Excel
                                </a>
                                <a href="{{ route('admin.home.activity-export', array_merge(request()->query(), ['type' => 'student', 'format' => 'pdf'])) }}"
                                    class="btn btn-danger btn-sm">
                                    <i class="fa fa-file-pdf-o"></i> Export PDF
                                </a>
                            </div>
                        </form>
                        <p class="m-b-xs" style="font-weight:600;">
                            Report for: {{ $activityDateFrom ?? request('date_from', now()->toDateString()) }} - {{ $activityDateTo ?? request('date_to', now()->toDateString()) }}
                        </p>
                        <p class="text-muted small m-b-sm">Leave Student on <strong>None</strong> to see all students. Use dates only when needed.</p>
                        <p class="activity-tz-note"><strong>Time zone:</strong> {{ $activityTimezone ?? viewer_timezone() }}@if(!empty($activityTimezoneLabel)) ({{ $activityTimezoneLabel }})@endif — based on your location. Date &amp; Time values below use this zone.</p>
                        <div class="table-responsive" style="width:100%;overflow:auto;">
                            <table class="table table-striped table-bordered table-hover js-sortable-table" id="studentActivityTable">
                                <thead>
                                    <tr>
                                        <th data-sort-col="0" style="width: 170px;cursor:pointer;">Date &amp; Time <span class="sort-ind"></span></th>
                                        <th data-sort-col="1" style="width: 180px;cursor:pointer;">User <span class="sort-ind"></span></th>
                                        <th data-sort-col="2" style="width: 160px;cursor:pointer;">Activity <span class="sort-ind"></span></th>
                                        <th data-sort-col="3" style="cursor:pointer;">Description <span class="sort-ind"></span></th>
                                        @if($showSessionColumn ?? false)
                                            <th data-sort-col="4" style="width: 140px;cursor:pointer;">Session <span class="sort-ind"></span></th>
                                        @endif
                                        <th data-sort-col="{{ ($showSessionColumn ?? false) ? 5 : 4 }}" style="width: 90px;cursor:pointer;">Link <span class="sort-ind"></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $prevStudentActAt = null;
                                        $studentColspan = 5 + (($showSessionColumn ?? false) ? 1 : 0);
                                    @endphp
                                    @forelse($studentActivities ?? [] as $activity)
                                        @php
                                            $curStudentAt = $activity['occurred_at'] ?? null;
                                            $gapFromStudent = $prevStudentActAt;
                                            $studentHasGap = $gapFromStudent && $curStudentAt && abs($curStudentAt->getTimestamp() - $gapFromStudent->getTimestamp()) > 600;
                                            $prevStudentActAt = $curStudentAt ?: $prevStudentActAt;
                                        @endphp
                                        @if(!empty($studentHasGap))
                                            @include('admin.dashboard.partials.activity-gap-row', [
                                                'scope' => 'student',
                                                'fromAt' => $gapFromStudent,
                                                'toAt' => $curStudentAt,
                                                'colspan' => $studentColspan,
                                                'activityGapComments' => $activityGapComments ?? [],
                                            ])
                                        @endif
                                        <tr class="{{ !empty($studentHasGap) ? 'activity-gap-row' : '' }}">
                                            <td data-sort="{{ $activity['occurred_at']->timestamp }}">{{ format_in_viewer_tz($activity['occurred_at']) }}</td>
                                            <td>{{ $activity['user_name'] ?? '—' }}</td>
                                            <td>
                                                @if(!empty($activity['url']))
                                                    <a href="{{ $activity['url'] }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ activity_action_label($activity['action'] ?? '') }}</a>
                                                @else
                                                    {{ activity_action_label($activity['action'] ?? '') }}
                                                @endif
                                            </td>
                                            <td>{{ $activity['item'] }}</td>
                                            @if($showSessionColumn ?? false)
                                                <td>
                                                    @if(!empty($activity['session_id']))
                                                        <span class="text-muted small" title="{{ $activity['session_id'] }}">{{ \Illuminate\Support\Str::limit($activity['session_id'], 12) }}</span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td>
                                                @if(!empty($activity['url']))
                                                    <a href="{{ $activity['url'] }}" target="_blank" rel="noopener">Open</a>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ ($showSessionColumn ?? false) ? 6 : 5 }}" class="text-center text-muted">
                                                No student activity found for the selected filters.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if($studentActivities)
                                {!! $studentActivities->appends(array_merge(request()->query(), ['activity_tab' => 'student']))->links() !!}
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    setTimeout(function() {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            showMethod: 'slideDown',
            timeOut: 4000
        };
    }, 300);
    toastr.success('Welcome, {{ addslashes(panel_profile_name() ?: 'User') }}');

    function initTypeFind($el, placeholder, width) {
        if (!$el.length || $el.hasClass('select2-hidden-accessible')) {
            return;
        }
        $el.select2({
            placeholder: placeholder,
            allowClear: true,
            width: width || '100%',
            minimumResultsForSearch: 0
        });
    }

    initTypeFind($('#user_id'), @json($userFilterEmptyLabel ?? 'Type to find user'), '220px');
    initTypeFind($('#student_user_id'), 'Type to find student', '220px');
    initTypeFind($('#content_report_user'), 'Type to find user', '280px');

    $('#content_report_user').on('change', function () {
        var $form = $(this).closest('form');
        if ($form.length) {
            $form.trigger('submit');
        }
    });

    // Keep Activities tab in the URL so pagination stays on the same tab.
    $('#activities-panel a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('href') || '';
        var tab = target.indexOf('student') !== -1 ? 'student' : 'staff';
        var url = new URL(window.location.href);
        url.searchParams.set('activity_tab', tab);
        window.history.replaceState({}, '', url.toString());
    });

    // Client-side column sort for activity tables (current page).
    $('.js-sortable-table').each(function () {
        var $table = $(this);
        var sortCol = 0;
        var sortDir = 'desc';

        $table.on('click', 'thead th[data-sort-col]', function () {
            var col = parseInt($(this).data('sort-col'), 10);
            if (sortCol === col) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortCol = col;
                sortDir = 'asc';
            }

            var $rows = $table.find('tbody tr').get().filter(function (row) {
                return $(row).find('td').length > 1;
            });

            $rows.sort(function (a, b) {
                var $a = $(a).children('td').eq(sortCol);
                var $b = $(b).children('td').eq(sortCol);
                var av = $a.attr('data-sort') || $a.text().trim().toLowerCase();
                var bv = $b.attr('data-sort') || $b.text().trim().toLowerCase();
                var an = parseFloat(av);
                var bn = parseFloat(bv);
                var cmp;
                if (!isNaN(an) && !isNaN(bn) && String(av).match(/^\d+(\.\d+)?$/)) {
                    cmp = an - bn;
                } else {
                    cmp = String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' });
                }
                return sortDir === 'asc' ? cmp : -cmp;
            });

            $.each($rows, function (_, row) {
                $table.children('tbody').append(row);
            });

            $table.find('thead th .sort-ind').text('');
            $(this).find('.sort-ind').text(sortDir === 'asc' ? ' ▲' : ' ▼');
        });
    });
});
</script>
@endpush
