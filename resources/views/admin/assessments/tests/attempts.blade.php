@extends(lms_portal_layout())
@section('title', 'Attempts — ' . $assessment->title)
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
        overflow-wrap: anywhere;
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
    .lms-attempts-table .col-action { width: 16%; white-space: nowrap; }
    .lms-attempts-table .col-action .btn { margin: 1px 0; }
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
        <table class="table table-striped table-bordered lms-attempts-table">
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-taker">Test Taker</th>
                    <th class="col-time">Start</th>
                    <th class="col-time">End</th>
                    <th class="col-spend">Spend</th>
                    <th class="col-status">Status</th>
                    <th class="col-score">Score</th>
                    <th class="col-pub">Published</th>
                    <th class="col-assessor">Assessor</th>
                    <th class="col-action"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php $spend = $row->spendMinutes(); @endphp
                    <tr>
                        <td class="col-date">{{ optional($row->submitted_at ?: $row->updated_at)->format('d/m/y H:i') ?? '—' }}</td>
                        <td class="col-taker">{{ $row->user->name ?? '—' }}<br><small class="text-muted">{{ $row->user->email ?? '' }}</small></td>
                        <td class="col-time">{{ optional($row->started_at)->format('H:i') ?? '—' }}</td>
                        <td class="col-time">{{ optional($row->submitted_at)->format('H:i') ?? '—' }}</td>
                        <td class="col-spend">{{ $spend !== null ? $spend . ' min' : '—' }}</td>
                        <td class="col-status"><span class="{{ $row->statusColorClass() }}">{{ $row->statusLabel() }}</span></td>
                        <td class="col-score">
                            @if($row->score_raw !== null)
                                {{ lms_format_number($row->score_raw) }} / {{ lms_format_number($row->score_max) }}
                                @if($row->percent() !== null) ({{ lms_format_number($row->percent()) }}%) @endif
                            @else — @endif
                        </td>
                        <td class="col-pub">
                            <span class="{{ $row->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                {{ $row->results_published ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td class="col-assessor">{{ $row->assessorLabel() }}</td>
                        <td class="col-action">
                            <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $row->id) }}">Mark</a>
                            <a class="btn btn-xs btn-success" href="{{ route('admin.assessments.tests.attempt-pdf', $row->id) }}" target="_blank" title="Download PDF"><i class="fa fa-file-pdf-o"></i> PDF</a>
                            @unless($row->results_published)
                                <form action="{{ route('admin.assessments.tests.publish-attempt', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-default">Publish</button></form>
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
