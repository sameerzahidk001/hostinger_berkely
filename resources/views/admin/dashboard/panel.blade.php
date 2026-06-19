@extends('admin.layout.app')
@section('title', 'Dashboard')

@section('content')
<div class="wrapper wrapper-content animated fadeInRight" style="padding: 20px 10px 0px;">
    <div class="row">
        @if($showTotals ?? true)
        <div class="col-lg-3">
            <div class="widget style1 navy-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-files-o fa-5x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>{{ $scopeLabel }} Courses</span>
                        <h2 class="font-bold">{{ $summary['total_courses'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="widget style1 lazur-bg">
                <div class="row">
                    <div class="col-xs-4">
                        <i class="fa fa-sitemap fa-5x"></i>
                    </div>
                    <div class="col-xs-8 text-right">
                        <span>{{ $scopeLabel }} Pages</span>
                        <h2 class="font-bold">{{ $summary['total_pages'] }}</h2>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<div class="row" style="padding: 0 10px 12px;">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5>Filter activity by date</h5>
            </div>
            <div class="ibox-content">
                <form method="GET" action="{{ route('admin.home') }}" class="form-inline">
                    <div class="form-group m-r-sm">
                        <label for="date_from" class="m-r-xs">From</label>
                        <input type="date" id="date_from" name="date_from" class="form-control"
                            value="{{ request('date_from') }}">
                    </div>
                    <div class="form-group m-r-sm">
                        <label for="date_to" class="m-r-xs">To</label>
                        <input type="date" id="date_to" name="date_to" class="form-control"
                            value="{{ request('date_to') }}">
                    </div>
                    <button type="submit" class="btn btn-primary m-r-sm">Filter</button>
                    <a href="{{ route('admin.home') }}" class="btn btn-default">Clear</a>
                </form>

                <div class="m-t-md">
                    <span class="label label-primary m-r-xs">Courses created: {{ $summary['courses_created'] }}</span>
                    <span class="label label-info m-r-xs">Pages created: {{ $summary['pages_created'] }}</span>
                    @if($includePayments)
                        <span class="label label-success">Payments recorded: {{ $summary['payments_recorded'] }}</span>
                    @endif
                    @if(request('date_from') || request('date_to'))
                        <small class="text-muted m-l-sm">Counts above are for the selected date range.</small>
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
                                <th style="width: 160px;">Activity</th>
                                <th>Item</th>
                                @if($showUserColumn)
                                    <th style="width: 180px;">User</th>
                                @endif
                                <th style="width: 90px;">Link</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activities as $activity)
                                <tr>
                                    <td>{{ $activity['occurred_at']->format('M j, Y g:i A') }}</td>
                                    <td>{{ $activity['action'] }}</td>
                                    <td>{{ $activity['item'] }}</td>
                                    @if($showUserColumn)
                                        <td>{{ $activity['user_name'] }}</td>
                                    @endif
                                    <td>
                                        <a href="{{ $activity['url'] }}" target="_blank" rel="noopener">Open</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $showUserColumn ? 5 : 4 }}" class="text-center text-muted">
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
