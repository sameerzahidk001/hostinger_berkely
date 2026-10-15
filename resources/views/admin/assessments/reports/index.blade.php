@extends(lms_portal_layout())
@section('title', 'Test Results')
@section('content')
<style>
    .lms-status-submitted { color: #7a4db5; font-weight: 700; }
    .lms-status-graded { color: #1ab394; font-weight: 700; }
    .lms-status-in_progress { color: #f0ad4e; font-weight: 700; }
    .lms-status-expired { color: #ed5565; font-weight: 700; }
    .lms-pub-yes { color: #1ab394; font-weight: 700; }
    .lms-pub-no { color: #ed5565; font-weight: 700; }
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Test Results</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Person-wise marking &amp; release</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-info">
        Select a test, then mark each student and publish results. Access is for Admin or instructors with Create/Assign permission on their courses.
    </div>
    <div class="ibox"><div class="ibox-content">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-sm-5 form-group">
                    <label>Assessment</label>
                    <select name="assessment_id" class="form-control">
                        <option value="0">Select a test…</option>
                        @foreach($assessments as $a)
                            <option value="{{ $a->id }}" @selected((int)$filters['assessment_id'] === (int)$a->id)>{{ $a->title }} ({{ $a->kind }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4 form-group">
                    <label>Batch</label>
                    <select name="batch_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->id }}" @selected((int)$filters['batch_id'] === (int)$b->id)>{{ $b->code }} — {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-3 form-group" style="padding-top:24px;">
                    <button class="btn btn-primary" type="submit">Show attempts</button>
                </div>
            </div>
        </form>
    </div></div>

    @if($selectedAssessment)
    <div class="ibox">
        <div class="ibox-title" style="display:flex;justify-content:space-between;align-items:center;">
            <h5 style="margin:0;">Student attempts — {{ $selectedAssessment->title }}</h5>
            <a class="btn btn-xs btn-default" href="{{ route('admin.assessments.tests.attempts', $selectedAssessment->id) }}">Full attempts list</a>
        </div>
        <div class="ibox-content table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Result</th>
                        <th>Published</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($studentAttempts as $row)
                        <tr>
                            <td>
                                {{ $row->user->name ?? '—' }}
                                <br><small class="text-muted">{{ $row->user->email ?? '' }}</small>
                            </td>
                            <td><span class="lms-status-{{ $row->status }}">{{ $row->statusLabel() }}</span></td>
                            <td>
                                @if($row->score_raw !== null)
                                    {{ $row->score_raw }} / {{ $row->score_max }}
                                    @if($row->percent() !== null) ({{ $row->percent() }}%) @endif
                                @else — @endif
                            </td>
                            <td>{{ $row->passFailLabel() }}</td>
                            <td>
                                <span class="{{ $row->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                    {{ $row->results_published ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>{{ optional($row->submitted_at ?: $row->updated_at)->format('d M Y H:i') ?? '—' }}</td>
                            <td style="white-space:nowrap;">
                                @if(in_array($row->status, ['submitted', 'graded', 'expired'], true))
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $row->id) }}">Mark / feedback</a>
                                    @unless($row->results_published)
                                        <form action="{{ route('admin.assessments.tests.publish-attempt', $row->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-xs btn-success" type="submit">Publish</button>
                                        </form>
                                    @endunless
                                @elseif($row->status === 'in_progress')
                                    <span class="text-muted">Still in progress</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No student attempts for this assessment yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="ibox"><div class="ibox-content text-muted">Select a test above to mark and release scores person-wise.</div></div>
    @endif
</div>
@endsection
