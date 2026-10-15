@extends(lms_portal_layout())
@section('title', 'Attempts — ' . $assessment->title)
@section('content')
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
                <tr><th>User</th><th>Status</th><th>Score</th><th>Pass</th><th>Published</th><th>Submitted</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->user->name ?? '—' }}<br><small class="text-muted">{{ $row->user->email ?? '' }}</small></td>
                        <td>{{ $row->statusLabel() }}</td>
                        <td>
                            @if($row->score_raw !== null)
                                {{ $row->score_raw }} / {{ $row->score_max }}
                                @if($row->percent() !== null) ({{ $row->percent() }}%) @endif
                            @else — @endif
                        </td>
                        <td>{{ $row->passFailLabel() }}</td>
                        <td>{{ $row->results_published ? 'Yes' : 'No' }}</td>
                        <td>{{ optional($row->submitted_at)->format('d M Y H:i') ?? '—' }}</td>
                        <td style="white-space:nowrap;">
                            <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $row->id) }}">Mark / feedback</a>
                            @unless($row->results_published)
                                <form action="{{ route('admin.assessments.tests.publish-attempt', $row->id) }}" method="POST" style="display:inline;">@csrf<button class="btn btn-xs btn-default">Publish</button></form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No attempts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@endsection
