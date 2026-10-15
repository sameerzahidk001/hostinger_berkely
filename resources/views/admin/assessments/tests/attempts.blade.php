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
    .lms-attempts-table { min-width: 1100px; width: 100%; margin-bottom: 0; }
    .lms-attempts-table > thead > tr > th,
    .lms-attempts-table > tbody > tr > td {
        vertical-align: middle;
        padding: 10px 12px;
        white-space: nowrap;
    }
    .lms-attempts-table .col-taker { min-width: 180px; max-width: 240px; white-space: normal; }
    .lms-attempts-table .col-status { min-width: 140px; white-space: normal; }
    .lms-attempts-table .col-assessor { min-width: 130px; max-width: 180px; white-space: normal; }
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
    <div class="ibox"><div class="ibox-content table-responsive">
        <table class="table table-striped table-bordered lms-attempts-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="col-taker">Test Taker</th>
                    <th>Start time</th>
                    <th>End time</th>
                    <th>Spend duration</th>
                    <th class="col-status">Status</th>
                    <th>Score</th>
                    <th>Published</th>
                    <th class="col-assessor">Assessor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php $spend = $row->spendMinutes(); @endphp
                    <tr>
                        <td>{{ optional($row->submitted_at ?: $row->updated_at)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="col-taker">{{ $row->user->name ?? '—' }}<br><small class="text-muted">{{ $row->user->email ?? '' }}</small></td>
                        <td>{{ optional($row->started_at)->format('H:i') ?? '—' }}</td>
                        <td>{{ optional($row->submitted_at)->format('H:i') ?? '—' }}</td>
                        <td>{{ $spend !== null ? $spend . ' min' : '—' }}</td>
                        <td class="col-status"><span class="{{ $row->statusColorClass() }}">{{ $row->statusLabel() }}</span></td>
                        <td>
                            @if($row->score_raw !== null)
                                {{ lms_format_number($row->score_raw) }} / {{ lms_format_number($row->score_max) }}
                                @if($row->percent() !== null) ({{ lms_format_number($row->percent()) }}%) @endif
                            @else — @endif
                        </td>
                        <td>
                            <span class="{{ $row->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                {{ $row->results_published ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td class="col-assessor">{{ $row->assessorLabel() }}</td>
                        <td style="white-space:nowrap;">
                            <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $row->id) }}">Mark / feedback</a>
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
