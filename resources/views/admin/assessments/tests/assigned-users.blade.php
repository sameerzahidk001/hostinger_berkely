@extends(lms_portal_layout())
@section('title', 'Assigned Tests')
@include('admin.assessments.partials.searchable-select-assets')
@include('admin.assessments.partials.sortable-table-assets')
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
    .wrapper.wrapper-content { padding-left: 10px; padding-right: 10px; }
    .lms-assigned-wrap { overflow-x: auto; width: 100%; }
    .lms-assigned-table {
        width: 100% !important;
        min-width: 100%;
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
    .lms-assigned-table .col-appt { width: 9%; white-space: nowrap !important; }
    .lms-assigned-table .col-test { width: 16%; }
    .lms-assigned-table .col-taker { width: 13%; }
    .lms-assigned-table .col-timing { width: 28%; }
    .lms-assigned-table .col-created { width: 8%; }
    .lms-assigned-table .col-assessor { width: 9%; }
    .lms-assigned-table .col-status { width: 12%; }
    .lms-assigned-table .col-pub { width: 6%; }
    .lms-assigned-table .col-actions {
        width: 11%;
        overflow-wrap: normal;
        word-break: normal;
        white-space: normal;
    }
    .lms-assigned-table .col-actions .btn,
    .lms-assigned-table .col-actions form {
        display: inline-block;
        width: auto;
        max-width: 100%;
        margin: 0 0 4px 0;
        box-sizing: border-box;
    }
    .lms-assigned-table .col-actions .btn {
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.3;
        text-align: center;
        white-space: nowrap;
    }
    .lms-assigned-table .col-actions .btn-warning { color: #fff !important; background: #f0ad4e; border-color: #eea236; }
    .lms-assigned-table .col-actions .btn-info { color: #fff !important; background: #5bc0de; border-color: #46b8da; }
    .lms-assigned-table .col-actions .btn-primary { color: #fff !important; background: #337ab7; border-color: #2e6da4; }
    .lms-assigned-table .col-actions .btn-danger { color: #fff !important; background: #d9534f; border-color: #d43f3a; }
    .lms-assigned-table .col-status .lms-score-line { display: block; margin-top: 4px; font-weight: 600; color: #333; }
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
            <li><a href="{{ route('admin.assessments.tests.index') }}">ALL TESTS</a></li>
            <li class="active"><strong>Assigned Tests</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.assessments.tests.assigned-users', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Download Excel</a>
        <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default btn-sm">ALL TESTS</a>
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
                    <select name="assessment_id" class="form-control lms-searchable" data-placeholder="Type to find assessment">
                        <option value="0">All</option>
                        @foreach($assessments as $a)
                            <option value="{{ $a->id }}" @selected((int)($filters['assessment_id'] ?? 0) === (int)$a->id)>{{ $a->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Batch</label>
                    <select name="batch_id" class="form-control lms-searchable" data-placeholder="Type to find batch">
                        <option value="0">All</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->id }}" @selected((int)($filters['batch_id'] ?? 0) === (int)$b->id)>{{ $b->code }} — {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control lms-searchable" data-placeholder="Type to find course">
                        <option value="0">All</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" @selected((int)($filters['course_id'] ?? 0) === (int)$c->id)>{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Type</label>
                    <select name="kind" class="form-control lms-searchable" data-placeholder="Type to find type">
                        <option value="">All</option>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['kind'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Status</label>
                    <select name="status" class="form-control lms-searchable" data-placeholder="Type to find status">
                        <option value="">All</option>
                        @foreach(($statusOptions ?? []) as $sk => $sl)
                            <option value="{{ $sk }}" @selected(($filters['status'] ?? '') === $sk)>{{ $sl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-2 form-group">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, test, course…">
                </div>
                <div class="col-sm-12 form-group" style="padding-top:8px;">
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
            <table class="table table-striped table-bordered lms-assigned-table js-sortable-table">
                <thead>
                    <tr>
                        <th class="col-appt" data-sort-col="0" style="white-space:nowrap;">Appointment ID <span class="sort-ind"></span></th>
                        <th class="col-test" data-sort-col="1">Test Details <span class="sort-ind"></span></th>
                        <th class="col-taker" data-sort-col="2">Test Taker <span class="sort-ind"></span></th>
                        <th class="col-timing" data-sort-col="3">Test Time Details <span class="sort-ind"></span></th>
                        <th class="col-created" data-sort-col="4">Created by <span class="sort-ind"></span></th>
                        <th class="col-assessor" data-sort-col="5">Assessor <span class="sort-ind"></span></th>
                        <th class="col-status" data-sort-col="6">Status <span class="sort-ind"></span></th>
                        <th class="col-pub" data-sort-col="7">Published <span class="sort-ind"></span></th>
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
                            $spend = $attempt?->spendMinutes();
                            $appointment = $assignment->appointmentWindowLabel();
                            $assessorName = $attempt
                                ? $attempt->assessorLabel($assignment)
                                : ($assignment->marker->name ?? 'Admin / course instructors');
                            $assessorUserId = $attempt
                                ? $attempt->assessorUserId($assignment)
                                : ($assignment->marker_user_id ? (int) $assignment->marker_user_id : null);
                            $course = $assessment->course ?? null;
                            $apptId = $assignment->displayCode();
                            $attemptNo = (int) ($row['attempt_number'] ?? 1);
                            $attemptTotal = (int) ($row['attempt_total'] ?? 1);
                        @endphp
                        <tr>
                            <td class="col-appt" data-sort="{{ $assignment->id }}"><strong>{{ $apptId }}</strong></td>
                            <td class="col-test" data-sort="{{ $assessment->title ?? ('Test #'.($assignment->assessment_id ?? '')) }}">
                                <strong>{{ $assessment->title ?: ('Test #'.$assessment->id) }}</strong>
                                <br><small class="text-muted">{{ $assessment->displayCode() }} · {{ $assessment->kindLabel() }} · {{ $assignment->sittingKindLabel() }}</small>
                                <br><small><strong>Attempt {{ $attemptNo }} of {{ $attemptTotal }}</strong></small>
                            </td>
                            <td class="col-taker" data-sort="{{ $row['student']->name ?? '' }}">
                                @if(!empty($row['student']->id))
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $row['student']->id }}">{{ $row['student']->name ?? '—' }}</a>
                                @else
                                    {{ $row['student']->name ?? '—' }}
                                @endif
                                <br><small class="text-muted">{{ $row['student']->email ?? '' }}</small>
                                @if($assignment->batch)
                                    <br><small class="text-muted">Batch: {{ $assignment->batch->code ?? $assignment->batch->name }}</small>
                                @endif
                                @if($course)
                                    <br><small>
                                        @if(!empty($course->slug))
                                            <a href="{{ route('course.details', ['course' => $course->slug]) }}" target="_blank" rel="noopener noreferrer">{{ $course->title }}</a>
                                        @else
                                            <a href="{{ route('course.show', $course->id) }}" target="_blank" rel="noopener noreferrer">{{ $course->title }}</a>
                                        @endif
                                    </small>
                                @endif
                            </td>
                            <td class="col-timing" data-sort="{{ optional($assignment->available_from)->timestamp ?? 0 }}">
                                <span class="lms-timing-line"><strong>Test Appointment:</strong> {{ $assignment->isSelfSitting() ? 'Start anytime' : ($assignment->windowStart()?->format('d/m/Y, H:i') ?? '—') }}</span>
                                <span class="lms-timing-line text-muted"><strong>Timezone:</strong> {{ $assignment->timezoneLabel() }}</span>
                                <span class="lms-timing-line"><strong>Test Duration:</strong> {{ $fmtDuration($assessment->durationMinutes()) }}</span>
                                @if($attempt)
                                    <span class="lms-timing-line"><strong>Started:</strong> {{ $assignment->formatInTimezone($attempt->startedAtForDisplay()) }}</span>
                                    <span class="lms-timing-line"><strong>Ended:</strong> {{ $assignment->formatInTimezone($attempt->endedAtForDisplay()) }}</span>
                                    <span class="lms-timing-line"><strong>Time spent:</strong> {{ $spend !== null ? $fmtDuration($spend) : '—' }}</span>
                                    <span class="lms-timing-line"><strong>Pause duration:</strong> {{ $attempt->pauseDurationLabel() }}</span>
                                @else
                                    <span class="lms-timing-line"><strong>Started:</strong> —</span>
                                    <span class="lms-timing-line"><strong>Ended:</strong> —</span>
                                    <span class="lms-timing-line"><strong>Time spent:</strong> —</span>
                                    <span class="lms-timing-line"><strong>Pause duration:</strong> —</span>
                                @endif
                            </td>
                            <td class="col-created" data-sort="{{ $assignment->createdByLabel() }}">
                                @if($assignment->assigned_by_user_id)
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $assignment->assigned_by_user_id }}">{{ $assignment->createdByLabel() }}</a>
                                @else
                                    {{ $assignment->createdByLabel() }}
                                @endif
                            </td>
                            <td class="col-assessor" data-sort="{{ $assessorName }}">
                                @if($assessorUserId)
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $assessorUserId }}">{{ $assessorName }}</a>
                                @else
                                    {{ $assessorName }}
                                @endif
                            </td>
                            <td class="col-status" data-sort="{{ $statusText }}">
                                <span class="{{ $statusClass }}">{{ $statusText }}</span>
                                @if($attempt && $attempt->results_published && $attempt->score_raw !== null && ! $assessment->hidesOverallMarks())
                                    <span class="lms-score-line">
                                        {{ lms_format_number($attempt->score_raw) }} / {{ lms_format_number($attempt->score_max) }}
                                        @if($attempt->percent() !== null) ({{ lms_format_number($attempt->percent()) }}%) @endif
                                    </span>
                                @endif
                            </td>
                            <td class="col-pub" data-sort="{{ ($attempt && $attempt->results_published) ? 'Yes' : 'No' }}">
                                @if($attempt)
                                    <span class="{{ $attempt->results_published ? 'lms-pub-yes' : 'lms-pub-no' }}">
                                        {{ $attempt->results_published ? 'Yes' : 'No' }}
                                    </span>
                                @else
                                    <span class="lms-pub-no">No</span>
                                @endif
                            </td>
                            <td class="col-actions">
                                @php
                                    $canManageSitting = $assessment
                                        && app(\App\Services\LmsAssessmentService::class)->canManageSittingsFor($assessment);
                                @endphp
                                @if($attempt && in_array($attempt->status, ['submitted', 'graded', 'expired'], true))
                                    <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.mark', $attempt->id) }}">View Details</a>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.attempt-pdf', $attempt->id) }}" target="_blank">Download PDF</a>
                                    @if(! $attempt->results_published && $attempt->status === 'graded')
                                        <form action="{{ route('admin.assessments.tests.publish-attempt', $attempt->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-xs btn-primary" type="submit">Publish</button>
                                        </form>
                                    @endif
                                @endif
                                @if($canManageSitting && $assignment)
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.edit-assignment', $assignment->id) }}">Edit</a>
                                    <form action="{{ route('admin.assessments.tests.destroy-assignment', $assignment->id) }}" method="POST" onsubmit="return confirm('Delete this sitting ({{ $assignment->displayCode() }})?');">
                                        @csrf
                                        @method('DELETE')
                                        @if($attempt)
                                            <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">
                                        @endif
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.assign', ['id' => $assessment->id, 'mode' => $assignment->isSelfSitting() ? 'self' : 'exam']) }}">Assign again</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">No assigned test takers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('admin.assessments.partials.user-preview-modal')
@endsection
