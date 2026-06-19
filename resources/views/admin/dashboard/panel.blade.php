@extends('admin.layout.app')
@section('title', 'Dashboard')

@section('content')
@if(!($activityLogsEnabled ?? true))
<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="alert alert-warning">
            <strong>Login/logout tracking is not active.</strong>
            Run <code>database/sql/create-user-activity-logs.sql</code> on the live database (or
            <code>php artisan migrate --path=database/migrations/2026_06_19_000001_create_user_activity_logs_table.php --force</code>),
            then sign in/out again to record session activity.
        </div>
    </div>
</div>
@endif
<div class="wrapper wrapper-content animated fadeInRight" style="padding: 20px 10px 0px;">
    <div class="row">
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
                        <span>Total Courses</span>
                        <h2 class="font-bold">{{ $summary['total_courses_site'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="widget style1 blue-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-sitemap fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Total Pages</span>
                        <h2 class="font-bold">{{ $summary['total_pages_site'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        @if(($includePayments ?? false) && !($showInvoiceStats ?? false))
        <div class="col-lg-3 col-md-6">
            <div class="widget style1 red-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-money fa-3x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>Total Invoices</span>
                        <h2 class="font-bold">{{ $summary['total_payments_site'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
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

<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Filter activity</h5>
            </div>
            <div class="ibox-content">
                <form method="GET" action="{{ route('admin.home') }}" class="form-inline" style="flex-wrap: wrap;">
                    @if($showUserFilter ?? false)
                    <div class="form-group m-r-sm m-b-sm">
                        <label for="role" class="m-r-xs">Role</label>
                        <select id="role" name="role" class="form-control">
                            <option value="">All roles</option>
                            <option value="content_writer" @selected(request('role') === 'content_writer')>Content Writers</option>
                            <option value="accountant" @selected(request('role') === 'accountant')>Accountants</option>
                            <option value="instructor" @selected(request('role') === 'instructor')>Instructors</option>
                        </select>
                    </div>
                    <div class="form-group m-r-sm m-b-sm">
                        <label for="user_id" class="m-r-xs">User</label>
                        <select id="user_id" name="user_id" class="form-control" style="min-width: 220px;">
                            <option value="">All users</option>
                            @foreach($filterUsers ?? [] as $filterUser)
                                @php $roleName = $filterUser->roles->first()?->name; @endphp
                                <option value="{{ $filterUser->id }}" @selected((string) request('user_id') === (string) $filterUser->id)>
                                    {{ $filterUser->name }} ({{ role_display_name($roleName) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if($showStudentTable ?? false)
                    <div class="form-group m-r-sm m-b-sm">
                        <label for="student_user_id" class="m-r-xs">Student</label>
                        <select id="student_user_id" name="student_user_id" class="form-control" style="min-width: 220px;">
                            <option value="">All students</option>
                            @foreach($studentFilterUsers ?? [] as $studentUser)
                                <option value="{{ $studentUser->id }}" @selected((string) request('student_user_id') === (string) $studentUser->id)>
                                    {{ $studentUser->name }} ({{ $studentUser->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="form-group m-r-sm m-b-sm">
                        <label for="date_from" class="m-r-xs">From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="form-group m-r-sm m-b-sm">
                        <label for="date_to" class="m-r-xs">To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control"
                            value="{{ request('date_to') }}">
                    </div>
                    <button type="submit" class="btn btn-primary m-r-sm m-b-sm">Filter</button>
                    <a href="{{ route('admin.home') }}" class="btn btn-default m-b-sm">Clear</a>
                </form>

                <div class="m-t-md">
                    <span class="label label-primary m-r-xs">Courses created: {{ $summary['courses_created'] }}</span>
                    <span class="label label-info m-r-xs">Pages created: {{ $summary['pages_created'] }}</span>
                    @if($includePayments)
                        <span class="label label-success m-r-xs">Invoices recorded: {{ $summary['invoices_recorded'] ?? $summary['payments_recorded'] }}</span>
                    @endif
                    @if(request()->hasAny(['date_from', 'date_to', 'user_id', 'role', 'student_user_id']))
                        <small class="text-muted m-l-sm">Counts above match the selected filters.</small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="ibox float-e-margins" style="margin-bottom: 16px;">
            <div class="ibox-title">
                <h5>{{ $activityTitle }}</h5>
            </div>
            <div class="ibox-content">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 170px;">Date &amp; Time</th>
                                @if($showUserColumn)
                                    <th style="width: 180px;">Name</th>
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
                                    @if($showUserColumn)
                                        <td>{{ $activity['user_name'] ?? '—' }}</td>
                                    @endif
                                    <td>{{ $activity['action'] }}</td>
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
                                    @php
                                        $staffColspan = 4
                                            + ($showUserColumn ? 1 : 0)
                                            + (($showSessionColumn ?? false) ? 1 : 0);
                                    @endphp
                                    <td colspan="{{ $staffColspan }}" class="text-center text-muted">
                                        No activity found for the selected filters.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {!! $activities->links() !!}
                </div>
            </div>
        </div>
    </div>
</div>

@if($showStudentTable ?? false)
<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="ibox float-e-margins" style="margin-bottom: 16px;">
            <div class="ibox-title">
                <h5>{{ $studentActivityTitle ?? 'Student activity history' }}</h5>
            </div>
            <div class="ibox-content">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 170px;">Date &amp; Time</th>
                                <th style="width: 180px;">Name</th>
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
                                    <td>{{ $activity['action'] }}</td>
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
                        {!! $studentActivities->links() !!}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
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
});
</script>
@endpush
