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
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Attempts: {{ $assessment->title }}</h2>
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
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>User</th>
                    <th>Status</th>
                    <th>Score</th>
                    <th>Published</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ optional($row->submitted_at ?: $row->updated_at)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $row->user->name ?? '—' }}<br><small class="text-muted">{{ $row->user->email ?? '' }}</small></td>
                        <td><span class="{{ $row->statusColorClass() }}">{{ $row->statusLabel() }}</span></td>
                        <td>
                            @if($row->score_raw !== null)
                                {{ $row->score_raw }} / {{ $row->score_max }}
                                @if($row->percent() !== null) ({{ $row->percent() }}%) @endif
                            @else — @endif
                        </td>
                        <td>
                            <span class="{{ $row->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                {{ $row->results_published ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td style="white-space:nowrap;">
                            <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $row->id) }}">Mark / feedback</a>
                            <a class="btn btn-xs btn-success" href="{{ route('admin.assessments.tests.attempt-pdf', $row->id) }}" target="_blank"><i class="fa fa-file-pdf-o"></i> Download PDF</a>
                            @unless($row->results_published)
                                <form action="{{ route('admin.assessments.tests.publish-attempt', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-default">Publish</button></form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">No attempts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@endsection
