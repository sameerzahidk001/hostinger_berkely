@extends(lms_portal_layout())
@section('title', 'Assigned Tests')
@section('content')
<style>
    .lms-status-upcoming { color: #3b82f6; font-weight: 700; }
    .lms-status-available { color: #1ab394; font-weight: 700; }
    .lms-status-missed { color: #ed5565; font-weight: 700; }
    .lms-status-completed { color: #f59e0b; font-weight: 700; }
    .lms-status-marked { color: #1ab394; font-weight: 700; }
    .lms-status-in_progress { color: #f0ad4e; font-weight: 700; }
    .lms-pub-yes { color: #1ab394; font-weight: 700; }
    .lms-pub-no { color: inherit; font-weight: 600; }
    .lms-assigned-wrap { overflow-x: auto; }
    .lms-assigned-table {
        width: 100%;
        max-width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
        font-size: 12px;
    }
    .lms-assigned-table > thead > tr > th,
    .lms-assigned-table > tbody > tr > td {
        vertical-align: middle;
        padding: 6px 5px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        white-space: normal;
    }
    .lms-assigned-table .col-date { width: 7%; }
    .lms-assigned-table .col-test { width: 13%; }
    .lms-assigned-table .col-taker { width: 14%; }
    .lms-assigned-table .col-timing { width: 17%; }
    .lms-assigned-table .col-created { width: 8%; }
    .lms-assigned-table .col-assessor { width: 9%; }
    .lms-assigned-table .col-status { width: 8%; }
    .lms-assigned-table .col-score { width: 7%; }
    .lms-assigned-table .col-pub { width: 5%; }
    .lms-assigned-table .col-actions {
        width: 12%;
        overflow-wrap: normal;
        word-break: normal;
        white-space: normal;
    }
    .lms-assigned-table .col-actions .btn,
    .lms-assigned-table .col-actions form {
        display: block;
        width: 100%;
        margin: 0 0 4px 0;
        box-sizing: border-box;
    }
    .lms-assigned-table .col-actions .btn {
        padding: 4px 8px;
        font-size: 11px;
        line-height: 1.3;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .lms-assigned-table .col-timing .lms-timing-line { display: block; line-height: 1.35; }
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
    <div class="col-lg-8">
        <h2>Assigned Tests</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">Assessments</a></li>
            <li class="active"><strong>Assigned Tests</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default btn-sm">All tests</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <p class="text-muted m-b-sm">
        All assigned test takers (started or not). Status shows <strong>Marking Required</strong> until the assessor marks a submitted sitting.
    </p>
    <div class="ibox"><div class="ibox-content">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-sm-2 form-group">
                    <label>Assessment</label>
                    <select name="assessment_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($assessments as $a)
                            <option value="{{ $a->id }}" @selected((int)($filters['assessment_id'] ?? 0) === (int)$a->id)>{{ $a->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Batch</label>
                    <select name="batch_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->id }}" @selected((int)($filters['batch_id'] ?? 0) === (int)$b->id)>{{ $b->code }} — {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control">
                        <option value="0">All</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" @selected((int)($filters['course_id'] ?? 0) === (int)$c->id)>{{ $c->title }}</option>
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
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, test, course…">
                </div>
                <div class="col-sm-2 form-group" style="padding-top:24px;">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.assessments.tests.assigned-users') }}" class="btn btn-default">Clear</a>
                </div>
            </div>
        </form>
    </div></div>

    <div class="ibox">
        <div class="ibox-title">
            <h5 style="margin:0;">
                @if(!empty($selectedAssessment))
                    {{ $selectedAssessment->title }}
                @else
                    All assigned test takers
                @endif
                <small class="text-muted">({{ $rows->count() }})</small>
            </h5>
        </div>
        <div class="ibox-content lms-assigned-wrap">
            <table class="table table-striped table-bordered lms-assigned-table">
                <thead>
                    <tr>
                        <th class="col-date">Date</th>
                        <th class="col-test">Test</th>
                        <th class="col-taker">Test Taker</th>
                        <th class="col-timing">Timing</th>
                        <th class="col-created">Created by</th>
                        <th class="col-assessor">Assessor</th>
                        <th class="col-status">Status</th>
                        <th class="col-score">Score</th>
                        <th class="col-pub">Published</th>
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
                                ? (optional($attempt->submitted_at ?: $attempt->updated_at)->format('d/m/y H:i') ?? '—')
                                : (optional($assignment->created_at)->format('d/m/y H:i') ?? '—');
                            $spend = $attempt?->spendMinutes();
                            $appointment = $assignment->appointmentWindowLabel();
                            $assessorName = $attempt
                                ? $attempt->assessorLabel($assignment)
                                : ($assignment->marker->name ?? 'Admin / course instructors');
                            $assessorUserId = $attempt
                                ? $attempt->assessorUserId($assignment)
                                : ($assignment->marker_user_id ? (int) $assignment->marker_user_id : null);
                            $course = $assessment->course ?? null;
                        @endphp
                        <tr>
                            <td class="col-date">{{ $dateText }}</td>
                            <td class="col-test">
                                {{ $assessment->title ?? '—' }}
                                <br><small class="text-muted">{{ $assessment->displayCode() }} · {{ $assessment->kindLabel() }}</small>
                            </td>
                            <td class="col-taker">
                                {{ $row['student']->name ?? '—' }}
                                <br><small class="text-muted">{{ $row['student']->email ?? '' }}</small>
                                @if($course)
                                    <br><small>
                                        <a href="{{ route('course.edit', $course->id) }}">{{ $course->title }}</a>
                                    </small>
                                @endif
                            </td>
                            <td class="col-timing">
                                <span class="lms-timing-line"><strong>Exam Appointment Timing:</strong> {{ $appointment }}</span>
                                <span class="lms-timing-line"><strong>Exam Duration:</strong> {{ $fmtDuration($assessment->duration_minutes ?? null) }}</span>
                                <span class="lms-timing-line"><strong>Started:</strong> {{ optional($attempt?->started_at)->format('d/m/y H:i') ?? '—' }}</span>
                                <span class="lms-timing-line"><strong>Ended:</strong> {{ optional($attempt?->submitted_at)->format('d/m/y H:i') ?? '—' }}</span>
                                <span class="lms-timing-line"><strong>Duration spent:</strong> {{ $spend !== null ? $fmtDuration($spend) : '—' }}</span>
                            </td>
                            <td class="col-created">
                                @if($assignment->assigned_by_user_id)
                                    <a href="{{ route('users.edit', $assignment->assigned_by_user_id) }}">{{ $assignment->createdByLabel() }}</a>
                                @else
                                    {{ $assignment->createdByLabel() }}
                                @endif
                            </td>
                            <td class="col-assessor">
                                @if($assessorUserId)
                                    <a href="{{ route('users.edit', $assessorUserId) }}">{{ $assessorName }}</a>
                                @else
                                    {{ $assessorName }}
                                @endif
                            </td>
                            <td class="col-status"><span class="{{ $statusClass }}">{{ $statusText }}</span></td>
                            <td class="col-score">
                                @if($attempt && $attempt->score_raw !== null)
                                    {{ lms_format_number($attempt->score_raw) }} / {{ lms_format_number($attempt->score_max) }}
                                    @if($attempt->percent() !== null) ({{ lms_format_number($attempt->percent()) }}%) @endif
                                @else — @endif
                            </td>
                            <td class="col-pub">
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
                                    <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.mark', $attempt->id) }}">View</a>
                                    <a class="btn btn-xs btn-success" href="{{ route('admin.assessments.tests.attempt-pdf', $attempt->id) }}" target="_blank">PDF</a>
                                    @unless($attempt->results_published)
                                        <form action="{{ route('admin.assessments.tests.publish-attempt', $attempt->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-xs btn-default" type="submit">Publish</button>
                                        </form>
                                    @endunless
                                    @if(!empty($canDeleteAttempts))
                                        <form action="{{ route('admin.assessments.reports.destroy-attempt', $attempt->id) }}" method="POST" onsubmit="return confirm('Delete this sitting?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                        </form>
                                    @endif
                                @elseif($attempt && $attempt->status === 'in_progress')
                                    <span class="text-muted">In progress</span>
                                @else
                                    <span class="text-muted">Not started</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">No assigned test takers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
