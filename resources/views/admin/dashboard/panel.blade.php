@extends('admin.layout.app')
@section('title', 'Dashboard')

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
<div class="wrapper wrapper-content animated fadeInRight" style="padding: 20px 10px 0px;">
    <div class="row">
        {{-- Colored summary cards duplicate Content performance reports; hide when reports are shown. --}}
        @if(!($showContentReports ?? false))
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
    $activityTab = request('activity_tab', 'staff');
    if (! in_array($activityTab, ['staff', 'student'], true)) {
        $activityTab = 'staff';
    }
    if (! ($showStudentTable ?? false)) {
        $activityTab = 'staff';
    }
    $staffColspan = 4
        + (($showUserColumn ?? false) ? 1 : 0)
        + (($showSessionColumn ?? false) ? 1 : 0);
    $preserveReportUser = request('report_user');
    $staffClearQuery = array_filter([
        'report_user' => $preserveReportUser,
        'activity_tab' => 'staff',
        'student_user_id' => request('student_user_id'),
    ], fn ($v) => $v !== null && $v !== '');
    $studentClearQuery = array_filter([
        'report_user' => $preserveReportUser,
        'activity_tab' => 'student',
        'role' => request('role'),
        'user_id' => request('user_id'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="ibox float-e-margins" style="margin-bottom: 16px;" id="activities-panel">
            <div class="ibox-title">
                <h5 style="margin:0;">Activities</h5>
            </div>
            <div class="ibox-content">
                @if($showStudentTable ?? false)
                <ul class="nav nav-tabs" role="tablist" style="margin-bottom:16px;">
                    <li class="{{ $activityTab === 'staff' ? 'active' : '' }}">
                        <a href="#activity-staff" data-toggle="tab">{{ $activityTitle ?? 'Staff activity' }}</a>
                    </li>
                    <li class="{{ $activityTab === 'student' ? 'active' : '' }}">
                        <a href="#activity-student" data-toggle="tab">{{ $studentActivityTitle ?? 'Student activity' }}</a>
                    </li>
                </ul>
                @endif

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane {{ $activityTab === 'staff' || !($showStudentTable ?? false) ? 'active' : '' }}" id="activity-staff">
                        <form method="GET" action="{{ route('admin.home') }}" class="form-inline" style="flex-wrap:wrap;margin-bottom:12px;" id="staffActivityFilterForm">
                            <input type="hidden" name="activity_tab" value="staff">
                            @if($preserveReportUser)
                                <input type="hidden" name="report_user" value="{{ $preserveReportUser }}">
                            @endif
                            @if(request()->filled('student_user_id'))
                                <input type="hidden" name="student_user_id" value="{{ request('student_user_id') }}">
                            @endif
                            @if($showUserFilter ?? false)
                            <div class="form-group m-r-sm m-b-sm">
                                <label for="role" class="m-r-xs">Role</label>
                                <select id="role" name="role" class="form-control activity-filter-select">
                                    <option value="" @selected(!request()->filled('role'))>None</option>
                                    <option value="content_writer" @selected(request('role') === 'content_writer')>Content Writers</option>
                                    <option value="accountant" @selected(request('role') === 'accountant')>Accountants</option>
                                    <option value="instructor" @selected(request('role') === 'instructor')>Instructors</option>
                                </select>
                            </div>
                            <div class="form-group m-r-sm m-b-sm">
                                <label for="user_id" class="m-r-xs">User</label>
                                <select id="user_id" name="user_id" class="form-control activity-filter-select" style="min-width: 220px;">
                                    <option value="" @selected(!request()->filled('user_id'))>None</option>
                                    @foreach($filterUsers ?? [] as $filterUser)
                                        @php $roleName = $filterUser->roles->first()?->name; @endphp
                                        <option value="{{ $filterUser->id }}" @selected((string) request('user_id') === (string) $filterUser->id)>
                                            {{ $filterUser->name }} ({{ role_display_name($roleName) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="form-group m-r-sm m-b-sm">
                                <label for="staff_date_from" class="m-r-xs">From</label>
                                <input type="date" id="staff_date_from" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="form-group m-r-sm m-b-sm">
                                <label for="staff_date_to" class="m-r-xs">To</label>
                                <input type="date" id="staff_date_to" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <button type="submit" class="btn btn-primary m-r-sm m-b-sm">Filter</button>
                            <a href="{{ route('admin.home', $staffClearQuery) }}" class="btn btn-default m-b-sm">Clear</a>
                            <a href="{{ route('admin.home.activity-export', array_merge(request()->query(), ['type' => 'staff'])) }}"
                                class="btn btn-success btn-sm m-b-sm" style="margin-left:auto;">
                                <i class="fa fa-file-excel-o"></i> Export to Excel
                            </a>
                        </form>
                        <p class="text-muted small m-b-sm">Leave any dropdown on <strong>None</strong> to skip that filter. Use dates only when needed.</p>
                        <div class="m-b-md">
                            <span class="label label-primary m-r-xs">Courses created: {{ $summary['courses_created'] }}</span>
                            <span class="label label-info m-r-xs">Pages created: {{ $summary['pages_created'] }}</span>
                            @if($includePayments ?? false)
                                <span class="label label-success m-r-xs">Invoices recorded: {{ $summary['invoices_recorded'] ?? $summary['payments_recorded'] }}</span>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 170px;">Date &amp; Time</th>
                                        @if($showUserColumn ?? false)
                                            <th style="width: 180px;">User</th>
                                        @endif
                                        <th style="width: 160px;">Activity</th>
                                        <th>Item</th>
                                        @if($showSessionColumn ?? false)
                                            <th style="width: 140px;">Session</th>
                                        @endif
                                        <th style="width: 90px;">Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($activities as $activity)
                                        <tr>
                                            <td>{{ $activity['occurred_at']->format('M j, Y g:i A') }}</td>
                                            @if($showUserColumn ?? false)
                                                <td>{{ $activity['user_name'] ?? '—' }}</td>
                                            @endif
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

                    @if($showStudentTable ?? false)
                    <div role="tabpanel" class="tab-pane {{ $activityTab === 'student' ? 'active' : '' }}" id="activity-student">
                        <form method="GET" action="{{ route('admin.home') }}" class="form-inline" style="flex-wrap:wrap;margin-bottom:12px;" id="studentActivityFilterForm">
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
                            <div class="form-group m-r-sm m-b-sm">
                                <label for="student_user_id" class="m-r-xs">Student</label>
                                <select id="student_user_id" name="student_user_id" class="form-control activity-filter-select" style="min-width: 220px;">
                                    <option value="" @selected(!request()->filled('student_user_id'))>None</option>
                                    @foreach($studentFilterUsers ?? [] as $studentUser)
                                        <option value="{{ $studentUser->id }}" @selected((string) request('student_user_id') === (string) $studentUser->id)>
                                            {{ $studentUser->name }} ({{ $studentUser->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group m-r-sm m-b-sm">
                                <label for="student_date_from" class="m-r-xs">From</label>
                                <input type="date" id="student_date_from" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="form-group m-r-sm m-b-sm">
                                <label for="student_date_to" class="m-r-xs">To</label>
                                <input type="date" id="student_date_to" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <button type="submit" class="btn btn-primary m-r-sm m-b-sm">Filter</button>
                            <a href="{{ route('admin.home', $studentClearQuery) }}" class="btn btn-default m-b-sm">Clear</a>
                            <a href="{{ route('admin.home.activity-export', array_merge(request()->query(), ['type' => 'student'])) }}"
                                class="btn btn-success btn-sm m-b-sm" style="margin-left:auto;">
                                <i class="fa fa-file-excel-o"></i> Export to Excel
                            </a>
                        </form>
                        <p class="text-muted small m-b-sm">Leave Student on <strong>None</strong> to see all students. Use dates only when needed.</p>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 170px;">Date &amp; Time</th>
                                        <th style="width: 180px;">User</th>
                                        <th style="width: 160px;">Activity</th>
                                        <th>Item</th>
                                        @if($showSessionColumn ?? false)
                                            <th style="width: 140px;">Session</th>
                                        @endif
                                        <th style="width: 90px;">Link</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($studentActivities ?? [] as $activity)
                                        <tr>
                                            <td>{{ $activity['occurred_at']->format('M j, Y g:i A') }}</td>
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

    var $role = $('#role');
    var $user = $('#user_id');

    if ($role.length && $user.length) {
        $role.on('change', function() {
            if ($(this).val() !== '') {
                $user.val('');
            }
        });

        $user.on('change', function() {
            if ($(this).val() !== '') {
                $role.val('');
            }
        });
    }

    // Keep Activities tab in the URL so pagination stays on the same tab.
    $('#activities-panel a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr('href') || '';
        var tab = target.indexOf('student') !== -1 ? 'student' : 'staff';
        var url = new URL(window.location.href);
        url.searchParams.set('activity_tab', tab);
        window.history.replaceState({}, '', url.toString());
    });
});
</script>
@endpush
