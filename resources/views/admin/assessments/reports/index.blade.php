@extends(lms_portal_layout())
@section('title', 'Test Results')
@section('content')
<style>
    .lms-status-completed { color: #f59e0b; font-weight: 700; }
    .lms-status-marked { color: #1ab394; font-weight: 700; }
    .lms-status-in_progress { color: #f0ad4e; font-weight: 700; }
    .lms-status-available { color: #1ab394; font-weight: 700; }
    .lms-status-upcoming { color: #3b82f6; font-weight: 700; }
    .lms-status-missed { color: #ed5565; font-weight: 700; }
    .lms-pub-yes { color: #1ab394; font-weight: 700; }
    .lms-pub-no { color: inherit; font-weight: 600; }
    .lms-results-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .lms-results-table { min-width: 1680px; table-layout: auto; width: 100%; margin-bottom: 0; }
    .lms-results-table > thead > tr > th,
    .lms-results-table > tbody > tr > td {
        vertical-align: middle;
        padding: 10px 12px;
        white-space: nowrap;
    }
    .lms-results-table .col-test { min-width: 200px; max-width: 260px; white-space: normal; }
    .lms-results-table .col-taker { min-width: 180px; max-width: 240px; white-space: normal; }
    .lms-results-table .col-course { min-width: 160px; max-width: 220px; white-space: normal; }
    .lms-results-table .col-assessor { min-width: 130px; max-width: 180px; white-space: normal; }
    .lms-results-table .col-status { min-width: 140px; white-space: normal; }
    .lms-results-table .col-score { min-width: 150px; }
    .lms-results-table .col-actions { min-width: 210px; white-space: nowrap; }
    .lms-results-table .col-actions .btn { margin: 0 2px 2px 0; }
</style>
@php
    $fmtDuration = function ($minutes) {
        if ($minutes === null || $minutes === '') {
            return '—';
        }
        $m = (int) round((float) $minutes);
        if ($m < 60) {
            return $m . ' min';
        }
        $h = intdiv($m, 60);
        $r = $m % 60;

        return $r ? ($h . 'h ' . $r . 'm') : ($h . 'h');
    };
@endphp
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
                <div class="col-sm-2 form-group">
                    <label>Assessment</label>
                    <select name="assessment_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($assessments as $a)
                            <option value="{{ $a->id }}" @selected((int)$filters['assessment_id'] === (int)$a->id)>{{ $a->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Batch</label>
                    <select name="batch_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->id }}" @selected((int)$filters['batch_id'] === (int)$b->id)>{{ $b->code }} — {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" @selected((int)$filters['course_id'] === (int)$c->id)>{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Type</label>
                    <select name="kind" class="form-control">
                        <option value="">All</option>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['kind'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Course, test, taker…">
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
                    Assigned — {{ $selectedAssessment->title }}
                @else
                    All assigned tests and status
                @endif
            </h5>
        </div>
        <div class="ibox-content lms-results-wrap">
            <table class="table table-striped table-bordered lms-results-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="col-test">Test</th>
                        <th class="col-taker">Test Taker</th>
                        <th class="col-course">Course</th>
                        <th>Test date</th>
                        <th>Start time</th>
                        <th>End time</th>
                        <th>Duration given</th>
                        <th>Spend duration</th>
                        <th class="col-assessor">Assessor</th>
                        <th class="col-status">Status</th>
                        <th class="col-score">Score</th>
                        <th>Published</th>
                        <th class="col-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $attempt = $row['attempt'];
                            $assignment = $row['assignment'];
                            $assessment = $row['assessment'];
                            if ($attempt) {
                                $statusClass = $attempt->statusColorClass();
                                $statusText = $attempt->statusLabel();
                            } elseif ($assignment->isNotYetOpen()) {
                                $statusClass = 'lms-status-upcoming';
                                $statusText = 'Upcoming';
                            } elseif ($assignment->isExpired()) {
                                $statusClass = 'lms-status-missed';
                                $statusText = 'Missed';
                            } else {
                                $statusClass = 'lms-status-available';
                                $statusText = 'Assigned';
                            }
                            $dateText = $attempt
                                ? (optional($attempt->submitted_at ?: $attempt->updated_at)->format('d/m/Y H:i') ?? '—')
                                : (optional($assignment->created_at)->format('d/m/Y H:i') ?? '—');
                            $testDate = optional($assignment->available_from ?: $attempt?->started_at)->format('d/m/Y') ?? '—';
                            $startTime = optional($attempt?->started_at ?: $assignment->available_from)->format('H:i') ?? '—';
                            $endTime = optional($attempt?->submitted_at ?: $assignment->available_until)->format('H:i') ?? '—';
                            $durationGiven = $fmtDuration($assessment->duration_minutes ?? null);
                            $spend = $attempt?->spendMinutes();
                            $durationTaken = $spend !== null ? $fmtDuration($spend) : '—';
                        @endphp
                        <tr>
                            <td>{{ $dateText }}</td>
                            <td class="col-test">
                                {{ $assessment->title ?? '—' }}
                                <br><small class="text-muted">{{ $assessment->displayCode() }} · {{ $assessment->kindLabel() }}</small>
                            </td>
                            <td class="col-taker">
                                {{ $row['student']->name ?? '—' }}
                                <br><small class="text-muted">{{ $row['student']->email ?? '' }}</small>
                            </td>
                            <td class="col-course">{{ $assessment->course->title ?? '—' }}</td>
                            <td>{{ $testDate }}</td>
                            <td>{{ $startTime }}</td>
                            <td>{{ $endTime }}</td>
                            <td>{{ $durationGiven }}</td>
                            <td>{{ $durationTaken }}</td>
                            <td class="col-assessor">{{ $attempt ? $attempt->assessorLabel($assignment) : ($assignment->marker->name ?? '—') }}</td>
                            <td class="col-status"><span class="{{ $statusClass }}">{{ $statusText }}</span></td>
                            <td class="col-score">
                                @if($attempt && $attempt->score_raw !== null)
                                    {{ lms_format_number($attempt->score_raw) }} / {{ lms_format_number($attempt->score_max) }}
                                    @if($attempt->percent() !== null) ({{ lms_format_number($attempt->percent()) }}%) @endif
                                @else — @endif
                            </td>
                            <td>
                                @if($attempt)
                                    <span class="{{ $attempt->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                        {{ $attempt->results_published ? 'Yes' : 'No' }}
                                    </span>
                                @else
                                    <span class="lms-pub-no">No</span>
                                @endif
                            </td>
                            <td class="col-actions">
                                @if($attempt && in_array($attempt->status, ['submitted', 'graded', 'expired'], true))
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.mark', $attempt->id) }}">View</a>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.mark', $attempt->id) }}">Update</a>
                                    <a class="btn btn-xs btn-success" href="{{ route('admin.assessments.tests.attempt-pdf', $attempt->id) }}" target="_blank" title="Download PDF"><i class="fa fa-file-pdf-o"></i> PDF</a>
                                    @unless($attempt->results_published)
                                        <form action="{{ route('admin.assessments.tests.publish-attempt', $attempt->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-xs btn-default" type="submit">Publish</button>
                                        </form>
                                    @endunless
                                    @if(!empty($canDeleteAttempts))
                                        <form action="{{ route('admin.assessments.reports.destroy-attempt', $attempt->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this attempt?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fa fa-trash"></i></button>
                                        </form>
                                    @endif
                                @elseif($attempt && $attempt->status === 'in_progress')
                                    <span class="text-muted">In progress</span>
                                @else
                                    <span class="text-muted">No attempt yet</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center text-muted">No assigned tests found for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
