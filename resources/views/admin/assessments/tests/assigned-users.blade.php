@extends(lms_portal_layout())
@section('title', 'Assigned Tests')
@section('content')
<style>
    .lms-status-upcoming { color: #3b82f6; font-weight: 700; }
    .lms-status-available { color: #1ab394; font-weight: 700; }
    .lms-status-missed { color: #ed5565; font-weight: 700; }
    .lms-status-completed { color: #f59e0b; font-weight: 700; }
    .lms-status-marked { color: #1ab394; font-weight: 700; }
    .lms-status-in_progress { color: #f0ad4e; font-weight: 700; }
    .lms-assigned-wrap { overflow-x: hidden; }
    .lms-assigned-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
    }
    .lms-assigned-table > thead > tr > th,
    .lms-assigned-table > tbody > tr > td {
        vertical-align: middle;
        padding: 6px 6px;
        font-size: 12px;
        word-wrap: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
    }
    .lms-assigned-table .col-date { width: 7%; }
    .lms-assigned-table .col-id { width: 5%; }
    .lms-assigned-table .col-test { width: 16%; }
    .lms-assigned-table .col-scope { width: 12%; }
    .lms-assigned-table .col-assessor { width: 10%; }
    .lms-assigned-table .col-window { width: 14%; }
    .lms-assigned-table .col-time { width: 5%; text-align: center; }
    .lms-assigned-table .col-spend { width: 6%; text-align: center; }
    .lms-assigned-table .col-status { width: 10%; }
    .lms-assigned-table .col-action { width: 10%; white-space: nowrap; }
    .lms-assigned-table .col-action .btn { margin: 1px 0; }
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
    <div class="ibox"><div class="ibox-content lms-assigned-wrap">
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
        <table class="table table-striped table-bordered lms-assigned-table">
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-id">Test ID</th>
                    <th class="col-test">Test</th>
                    <th class="col-scope">Assigned to</th>
                    <th class="col-assessor">Assessor</th>
                    <th class="col-window">Available</th>
                    <th class="col-time">Start</th>
                    <th class="col-time">End</th>
                    <th class="col-spend">Spend</th>
                    <th class="col-status">Status</th>
                    <th class="col-action"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $attempts = ($attemptsByAssessment ?? collect())->get($row->assessment_id, collect());
                        $att = null;
                        if ($row->student_id) {
                            $att = $attempts->firstWhere('user_id', (int) $row->student_id);
                        }
                        if ($att) {
                            $winLabel = $att->statusLabel();
                            $winClass = $att->statusColorClass();
                        } elseif ($row->isNotYetOpen()) {
                            $winLabel = 'Upcoming';
                            $winClass = 'lms-status-upcoming';
                        } elseif ($row->isExpired()) {
                            $winLabel = 'Missed';
                            $winClass = 'lms-status-missed';
                        } else {
                            $winLabel = 'Available';
                            $winClass = 'lms-status-available';
                        }
                        $cannotReassign = !empty($lockReassign[(int) $row->id]);
                        $spend = $att?->spendMinutes();
                    @endphp
                    <tr>
                        <td class="col-date">{{ optional($row->created_at)->format('d/m/y H:i') ?? '—' }}</td>
                        <td class="col-id">{{ $row->assessment?->displayCode() ?? ('BST'.$row->assessment_id) }}</td>
                        <td class="col-test">{{ $row->assessment->title ?? '—' }}</td>
                        <td class="col-scope">{{ $row->scopeLabel() }}</td>
                        <td class="col-assessor">
                            @if($row->marker)
                                {{ $row->marker->name }}
                            @elseif($att)
                                {{ $att->assessorLabel($row) }}
                            @else
                                <span class="text-muted">Admin / course instructors</span>
                            @endif
                        </td>
                        <td class="col-window">
                            {{ optional($row->available_from)->format('d/m/y H:i') ?? '—' }}
                            <br><span class="text-muted">→ {{ optional($row->available_until)->format('d/m/y H:i') ?? '—' }}</span>
                        </td>
                        <td class="col-time">{{ optional($att?->started_at)->format('H:i') ?? '—' }}</td>
                        <td class="col-time">{{ optional($att?->submitted_at)->format('H:i') ?? '—' }}</td>
                        <td class="col-spend">{{ $spend !== null ? $spend . ' min' : '—' }}</td>
                        <td class="col-status"><span class="{{ $winClass }}">{{ $winLabel }}</span></td>
                        <td class="col-action">
                            @if($row->assessment_id)
                                @unless($cannotReassign)
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.assign', $row->assessment_id) }}">Re-assign</a>
                                @else
                                    <span class="text-muted" title="Cannot re-assign after Missed / Completed / Marked">—</span>
                                @endunless
                                <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.attempts', $row->assessment_id) }}">View</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center">No assignments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@endsection
