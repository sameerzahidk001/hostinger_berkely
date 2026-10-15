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
                <thead><tr><th>Staff</th><th>Test</th><th>Status</th><th>Score</th><th>Pass</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse($skillRows as $row)
                        <tr>
                            <td>{{ $row->user->name ?? '—' }}</td>
                            <td>{{ $row->assessment->title ?? '—' }}</td>
                            <td>{{ $row->statusLabel() }}</td>
                            <td>@if($row->score_raw !== null){{ $row->score_raw }} / {{ $row->score_max }}@else — @endif</td>
                            <td>{{ $row->passFailLabel() }}</td>
                            <td>{{ optional($row->submitted_at ?: $row->updated_at)->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No skill attempts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
