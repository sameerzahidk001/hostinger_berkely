@extends(lms_portal_layout())
@section('title', 'Assessment Reports')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Assessment reports</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Pass rate · Item analysis · Skill</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-info">
        <strong>Mark &amp; release (student-wise):</strong>
        Select an assessment below and use the <em>Student attempts</em> table —
        open <strong>Mark / feedback</strong> for that student, then
        <strong>Save &amp; publish results</strong> (or <strong>Publish</strong> on the row).
        You can also go to <a href="{{ route('admin.assessments.tests.index') }}">Create / Assign → Attempts</a>.
    </div>
    <div class="ibox"><div class="ibox-content">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-sm-5 form-group">
                    <label>Assessment</label>
                    <select name="assessment_id" class="form-control">
                        <option value="0">All</option>
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
                    <button class="btn btn-primary" type="submit">Run</button>
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
                            <td>{{ $row->statusLabel() }}</td>
                            <td>
                                @if($row->score_raw !== null)
                                    {{ $row->score_raw }} / {{ $row->score_max }}
                                    @if($row->percent() !== null) ({{ $row->percent() }}%) @endif
                                @else — @endif
                            </td>
                            <td>{{ $row->passFailLabel() }}</td>
                            <td>{{ $row->results_published ? 'Yes' : 'No' }}</td>
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
    @endif

    <div class="ibox">
        <div class="ibox-title"><h5>Pass-rate summary</h5></div>
        <div class="ibox-content">
            <p>
                Attempts: <strong>{{ $passRate['attempts'] }}</strong> ·
                Passed: <strong>{{ $passRate['passed'] }}</strong> ·
                Pass rate: <strong>{{ $passRate['pass_rate'] !== null ? $passRate['pass_rate'].'%' : '—' }}</strong> ·
                Avg score: <strong>{{ $passRate['avg_percent'] !== null ? $passRate['avg_percent'].'%' : '—' }}</strong>
            </p>
        </div>
    </div>

    @if($itemAnalysis->isNotEmpty())
    <div class="ibox">
        <div class="ibox-title"><h5>MCQ item analysis</h5></div>
        <div class="ibox-content table-responsive">
            <table class="table table-striped table-bordered">
                <thead><tr><th>Question</th><th>Attempts</th><th>Correct</th><th>% correct</th></tr></thead>
                <tbody>
                    @foreach($itemAnalysis as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit(strip_tags($row['question']->stem), 120) }}</td>
                            <td>{{ $row['attempts'] }}</td>
                            <td>{{ $row['correct'] }}</td>
                            <td>{{ $row['percent'] !== null ? $row['percent'].'%' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if(!empty($canSkill))
    <div class="ibox">
        <div class="ibox-title"><h5>Staff skill test results</h5></div>
        <div class="ibox-content table-responsive">
            <table class="table table-striped table-bordered">
                <thead><tr><th>Staff</th><th>Test</th><th>Status</th><th>Score</th><th>Pass</th><th>Date</th><th></th></tr></thead>
                <tbody>
                    @forelse($skillRows as $row)
                        <tr>
                            <td>{{ $row->user->name ?? '—' }}</td>
                            <td>{{ $row->assessment->title ?? '—' }}</td>
                            <td>{{ $row->statusLabel() }}</td>
                            <td>@if($row->score_raw !== null){{ $row->score_raw }} / {{ $row->score_max }}@else — @endif</td>
                            <td>{{ $row->passFailLabel() }}</td>
                            <td>{{ optional($row->submitted_at ?: $row->updated_at)->format('d M Y') }}</td>
                            <td>
                                @if(in_array($row->status, ['submitted', 'graded', 'expired'], true))
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $row->id) }}">Mark</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No skill attempts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
