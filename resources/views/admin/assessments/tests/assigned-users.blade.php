@extends(lms_portal_layout())
@section('title', 'Assigned Tests')
@section('content')
<style>
    .lms-status-upcoming { color: #3b82f6; font-weight: 700; }
    .lms-status-available { color: #1ab394; font-weight: 700; }
    .lms-status-missed { color: #ed5565; font-weight: 700; }
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Assigned Tests</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">Assessments</a></li>
            <li class="active"><strong>Assigned Tests</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default btn-sm">All tests</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="ibox"><div class="ibox-content table-responsive">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-sm-6">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search test, user, batch, or test ID">
                </div>
                <div class="col-sm-4">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.assessments.tests.assigned-users') }}" class="btn btn-default">Clear</a>
                </div>
            </div>
        </form>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Test ID</th>
                    <th>Test</th>
                    <th>Assigned to</th>
                    <th>Marking instructor</th>
                    <th>Available from</th>
                    <th>Available until</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        if ($row->isNotYetOpen()) {
                            $winLabel = 'Upcoming';
                            $winClass = 'lms-status-upcoming';
                        } elseif ($row->isExpired()) {
                            $winLabel = 'Missed';
                            $winClass = 'lms-status-missed';
                        } else {
                            $winLabel = 'Available';
                            $winClass = 'lms-status-available';
                        }
                    @endphp
                    <tr>
                        <td>{{ optional($row->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $row->assessment_id }}</td>
                        <td>{{ $row->assessment->title ?? '—' }}</td>
                        <td>{{ $row->scopeLabel() }}</td>
                        <td>
                            @if($row->marker)
                                {{ $row->marker->name }}
                            @else
                                <span class="text-muted">Admin / course instructors</span>
                            @endif
                        </td>
                        <td>{{ optional($row->available_from)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ optional($row->available_until)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td><span class="{{ $winClass }}">{{ $winLabel }}</span></td>
                        <td style="white-space:nowrap;">
                            @if($row->assessment_id)
                                @unless($row->isExpired())
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.assign', $row->assessment_id) }}">Re-assign</a>
                                @else
                                    <span class="text-muted" title="Window ended — cannot re-assign">—</span>
                                @endunless
                                <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.attempts', $row->assessment_id) }}">Attempts</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center">No assignments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@endsection
