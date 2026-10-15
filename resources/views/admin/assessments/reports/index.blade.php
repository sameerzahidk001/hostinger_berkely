@extends(lms_portal_layout())
@section('title', 'Test Results')
@section('content')
<style>
    .lms-status-submitted { color: #7a4db5; font-weight: 700; }
    .lms-status-graded { color: #7a4db5; font-weight: 700; }
    .lms-status-in_progress { color: #f0ad4e; font-weight: 700; }
    .lms-status-expired { color: #ed5565; font-weight: 700; }
    .lms-pub-yes { color: #ed5565; font-weight: 700; }
    .lms-pub-no { color: #ed5565; font-weight: 700; }
    .lms-result-pass { color: #1ab394; font-weight: 700; }
    .lms-result-fail { color: #ed5565; font-weight: 700; }
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Test Results</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">Manage tests</a></li>
            <li class="active"><strong>viewing all results</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="ibox"><div class="ibox-content">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-sm-4 form-group">
                    <label>Assessment</label>
                    <select name="assessment_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($assessments as $a)
                            <option value="{{ $a->id }}" @selected((int)$filters['assessment_id'] === (int)$a->id)>{{ $a->title }} ({{ $a->kind }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-3 form-group">
                    <label>Batch</label>
                    <select name="batch_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->id }}" @selected((int)$filters['batch_id'] === (int)$b->id)>{{ $b->code }} — {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-3 form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="in_progress" @selected(($filters['status'] ?? '') === 'in_progress')>In progress</option>
                        <option value="submitted" @selected(($filters['status'] ?? '') === 'submitted')>Submitted</option>
                        <option value="graded" @selected(($filters['status'] ?? '') === 'graded')>Completed</option>
                        <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Expired</option>
                    </select>
                </div>
                <div class="col-sm-2 form-group" style="padding-top:24px;">
                    <button class="btn btn-primary" type="submit">Show Results</button>
                </div>
            </div>
        </form>
    </div></div>

    <div class="ibox">
        <div class="ibox-title">
            <h5 style="margin:0;">
                @if($selectedAssessment)
                    Student attempts — {{ $selectedAssessment->title }}
                @else
                    All test takers and status
                @endif
            </h5>
        </div>
        <div class="ibox-content table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        @unless($selectedAssessment)
                            <th>Test</th>
                        @endunless
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
                        @php
                            $statusText = $row->status === 'graded' ? 'Completed' : $row->statusLabel();
                            $resultText = $row->passFailLabel();
                        @endphp
                        <tr>
                            @unless($selectedAssessment)
                                <td>{{ $row->assessment->title ?? '—' }}</td>
                            @endunless
                            <td>
                                {{ $row->user->name ?? '—' }}
                                <br><small class="text-muted">{{ $row->user->email ?? '' }}</small>
                            </td>
                            <td><span class="lms-status-{{ $row->status }}">{{ $statusText }}</span></td>
                            <td>
                                @if($row->score_raw !== null)
                                    {{ number_format((float) $row->score_raw, 2) }} / {{ number_format((float) $row->score_max, 2) }}
                                    @if($row->percent() !== null) ({{ number_format((float) $row->percent(), 2) }}%) @endif
                                @else — @endif
                            </td>
                            <td>
                                @if($resultText === 'Pass')
                                    <span class="lms-result-pass">Pass</span>
                                @elseif($resultText === 'Fail')
                                    <span class="lms-result-fail">Fail</span>
                                @else
                                    {{ $resultText }}
                                @endif
                            </td>
                            <td>
                                <span class="{{ $row->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                    {{ $row->results_published ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>{{ optional($row->submitted_at ?: $row->updated_at)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td style="white-space:nowrap;">
                                @if(in_array($row->status, ['submitted', 'graded', 'expired'], true))
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $row->id) }}">View</a>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.mark', $row->id) }}">Update</a>
                                    @unless($row->results_published)
                                        <form action="{{ route('admin.assessments.tests.publish-attempt', $row->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-xs btn-success" type="submit">Publish</button>
                                        </form>
                                    @endunless
                                @elseif($row->status === 'in_progress')
                                    <span class="text-muted">In progress</span>
                                @endif
                                <form action="{{ route('admin.assessments.reports.destroy-attempt', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this attempt?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fa fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $selectedAssessment ? 7 : 8 }}" class="text-center text-muted">No test takers found for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
