@extends(lms_portal_layout())
@section('title', 'Attempts — ' . $assessment->title)
@include('admin.assessments.partials.sortable-table-assets')
@section('content')
<style>
    .lms-status-completed { color: #f59e0b; font-weight: 700; }
    .lms-status-marked { color: #1ab394; font-weight: 700; }
    .lms-status-in_progress { color: #f0ad4e; font-weight: 700; }
    .lms-status-missed { color: #ed5565; font-weight: 700; }
    .lms-pub-yes { color: #1ab394; font-weight: 700; }
    .lms-pub-no { color: inherit; font-weight: 600; }
    .lms-attempts-wrap { overflow-x: hidden; }
    .lms-attempts-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
        font-size: 12px;
    }
    .lms-attempts-table > thead > tr > th,
    .lms-attempts-table > tbody > tr > td {
        vertical-align: middle;
        padding: 6px 6px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        white-space: normal;
    }
    .lms-attempts-table .col-date { width: 10%; }
    .lms-attempts-table .col-taker { width: 16%; }
    .lms-attempts-table .col-time { width: 6%; text-align: center; }
    .lms-attempts-table .col-spend { width: 7%; text-align: center; }
    .lms-attempts-table .col-status { width: 11%; }
    .lms-attempts-table .col-score { width: 10%; }
    .lms-attempts-table .col-pub { width: 6%; }
    .lms-attempts-table .col-assessor { width: 12%; }
    .lms-attempts-table .col-action {
        width: 16%;
        overflow-wrap: normal;
        word-break: normal;
        white-space: nowrap;
    }
    .lms-attempts-table .col-action .btn {
        margin: 1px 2px 1px 0;
        white-space: nowrap;
        font-weight: 700;
    }
    .lms-attempts-table .col-action .btn-default {
        color: #fff !important;
        background: #337ab7;
        border-color: #2e6da4;
        font-weight: 700;
    }
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Attempts: {{ $assessment->title }} <small class="text-muted">({{ $assessment->displayCode() }})</small></h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">Assessments</a></li>
            <li class="active"><strong>Attempts</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="ibox"><div class="ibox-content lms-attempts-wrap">
        <table class="table table-striped table-bordered lms-attempts-table js-sortable-table">
            <thead>
                <tr>
                    <th class="col-date" data-sort-col="0">Date <span class="sort-ind"></span></th>
                    <th class="col-taker" data-sort-col="1">Test Taker <span class="sort-ind"></span></th>
                    <th class="col-time" data-sort-col="2">Start <span class="sort-ind"></span></th>
                    <th class="col-time" data-sort-col="3">End <span class="sort-ind"></span></th>
                    <th class="col-spend" data-sort-col="4">Spend <span class="sort-ind"></span></th>
                    <th class="col-status" data-sort-col="5">Status <span class="sort-ind"></span></th>
                    <th class="col-score" data-sort-col="6">Score <span class="sort-ind"></span></th>
                    <th class="col-pub" data-sort-col="7">Published <span class="sort-ind"></span></th>
                    <th class="col-assessor" data-sort-col="8">Assessor <span class="sort-ind"></span></th>
                    <th class="col-action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $spend = $row->spendMinutes();
                        $assessorUserId = $row->assessorUserId();
                    @endphp
                    <tr>
                        <td class="col-date" data-sort="{{ optional($row->submitted_at ?: $row->updated_at)->timestamp ?? 0 }}">{{ optional($row->submitted_at ?: $row->updated_at)->format('d/m/y H:i') ?? '—' }}</td>
                        <td class="col-taker" data-sort="{{ $row->user->name ?? '' }}">
                            @if(!empty($row->user->id))
                                <a href="{{ route('users.edit', $row->user->id) }}">{{ $row->user->name ?? '—' }}</a>
                            @else
                                {{ $row->user->name ?? '—' }}
                            @endif
                            <br><small class="text-muted">{{ $row->user->email ?? '' }}</small>
                        </td>
                        <td class="col-time" data-sort="{{ optional($row->started_at)->format('H:i') ?? '' }}">{{ optional($row->started_at)->format('H:i') ?? '—' }}</td>
                        <td class="col-time" data-sort="{{ optional($row->submitted_at)->format('H:i') ?? '' }}">{{ optional($row->submitted_at)->format('H:i') ?? '—' }}</td>
                        <td class="col-spend" data-sort="{{ $spend ?? -1 }}">{{ $spend !== null ? $spend . ' min' : '—' }}</td>
                        <td class="col-status" data-sort="{{ $row->statusLabel() }}"><span class="{{ $row->statusColorClass() }}">{{ $row->statusLabel() }}</span></td>
                        <td class="col-score" data-sort="{{ $row->score_raw ?? -1 }}">
                            @if($row->score_raw !== null)
                                {{ lms_format_number($row->score_raw) }} / {{ lms_format_number($row->score_max) }}
                                @if($row->percent() !== null) ({{ lms_format_number($row->percent()) }}%) @endif
                            @else — @endif
                        </td>
                        <td class="col-pub" data-sort="{{ $row->results_published ? 'Yes' : 'No' }}">
                            <span class="{{ $row->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                {{ $row->results_published ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td class="col-assessor" data-sort="{{ $row->assessorLabel() }}">
                            @if($assessorUserId)
                                <a href="{{ route('users.edit', $assessorUserId) }}">{{ $row->assessorLabel() }}</a>
                            @else
                                {{ $row->assessorLabel() }}
                            @endif
                        </td>
                        <td class="col-action">
                            <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.mark', $row->id) }}">View Details</a>
                            <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.attempt-pdf', $row->id) }}" target="_blank">Download PDF</a>
                            @unless($row->results_published)
                                <form action="{{ route('admin.assessments.tests.publish-attempt', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-primary">Publish</button></form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No attempts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@endsection
