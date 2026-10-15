@extends(lms_portal_layout())
@section('title', 'Test Results')
@section('content')
<style>
    .lms-status-submitted { color: #7a4db5; font-weight: 700; }
    .lms-status-graded { color: #7a4db5; font-weight: 700; }
    .lms-status-in_progress { color: #f0ad4e; font-weight: 700; }
    .lms-status-available { color: #1ab394; font-weight: 700; }
    .lms-status-upcoming { color: #3b82f6; font-weight: 700; }
    .lms-status-missed,
    .lms-status-expired { color: #ed5565; font-weight: 700; }
    .lms-pub-yes { color: #ed5565; font-weight: 700; }
    .lms-pub-no { color: #ed5565; font-weight: 700; }
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
                <div class="col-sm-3 form-group">
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
                    <label>Course</label>
                    <select name="course_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" @selected((int)$filters['course_id'] === (int)$c->id)>{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-3 form-group" style="padding-top:24px;">
                    <button class="btn btn-primary" type="submit">Show Results</button>
                </div>
            </div>
        </form>
    </div></div>

    <div class="ibox">
        <div class="ibox-title">
            <h5 style="margin:0;">
                @if($selectedAssessment)
                    Assigned — {{ $selectedAssessment->title }}
                @else
                    All assigned tests and status
                @endif
            </h5>
        </div>
        <div class="ibox-content table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Test</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Published</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            /** @var \App\Models\LmsAssessmentAttempt|null $attempt */
                            $attempt = $row['attempt'];
                            /** @var \App\Models\LmsAssessmentAssignment $assignment */
                            $assignment = $row['assignment'];
                            if ($attempt) {
                                $statusKey = $attempt->status === 'graded' ? 'graded' : $attempt->status;
                                $statusText = $attempt->status === 'graded' ? 'Completed' : $attempt->statusLabel();
                            } elseif ($assignment->isNotYetOpen()) {
                                $statusKey = 'upcoming';
                                $statusText = 'Upcoming';
                            } elseif ($assignment->isExpired()) {
                                $statusKey = 'missed';
                                $statusText = 'Missed';
                            } else {
                                $statusKey = 'available';
                                $statusText = 'Assigned';
                            }
                        @endphp
                        <tr>
                            <td>{{ $row['assessment']->title ?? '—' }}</td>
                            <td>
                                {{ $row['student']->name ?? '—' }}
                                <br><small class="text-muted">{{ $row['student']->email ?? '' }}</small>
                            </td>
                            <td><span class="lms-status-{{ $statusKey }}">{{ $statusText }}</span></td>
                            <td>
                                @if($attempt && $attempt->score_raw !== null)
                                    {{ number_format((float) $attempt->score_raw, 2) }} / {{ number_format((float) $attempt->score_max, 2) }}
                                    @if($attempt->percent() !== null) ({{ number_format((float) $attempt->percent(), 2) }}%) @endif
                                @else — @endif
                            </td>
                            <td>
                                @if($attempt)
                                    <span class="{{ $attempt->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                        {{ $attempt->results_published ? 'Yes' : 'No' }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($attempt)
                                    {{ optional($attempt->submitted_at ?: $attempt->updated_at)->format('d/m/Y H:i') ?? '—' }}
                                @else
                                    {{ optional($assignment->created_at)->format('d/m/Y H:i') ?? '—' }}
                                @endif
                            </td>
                            <td style="white-space:nowrap;">
                                @if($attempt && in_array($attempt->status, ['submitted', 'graded', 'expired'], true))
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $attempt->id) }}">View</a>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.mark', $attempt->id) }}">Update</a>
                                    @unless($attempt->results_published)
                                        <form action="{{ route('admin.assessments.tests.publish-attempt', $attempt->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-xs btn-success" type="submit">Publish</button>
                                        </form>
                                    @endunless
                                    <form action="{{ route('admin.assessments.reports.destroy-attempt', $attempt->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this attempt?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fa fa-trash"></i></button>
                                    </form>
                                @elseif($attempt && $attempt->status === 'in_progress')
                                    <span class="text-muted">In progress</span>
                                @else
                                    <span class="text-muted">No attempt yet</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No assigned tests found for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
