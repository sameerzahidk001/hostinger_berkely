@extends(lms_portal_layout())
@section('title', 'Assigned Tests')
@include('admin.assessments.partials.searchable-select-assets')
@include('admin.assessments.partials.sortable-table-assets')
@section('content')
<!-- lms-assigned-tests-20261104k same columns admin+instructor -->
<style>
    .lms-status-upcoming { color: #3b82f6; font-weight: 700; }
    .lms-status-available { color: #1ab394; font-weight: 700; }
    .lms-status-missed { color: #ed5565; font-weight: 700; }
    .lms-status-completed { color: #f59e0b; font-weight: 700; }
    .lms-status-marked { color: #1ab394; font-weight: 700; }
    .lms-status-in_progress { color: #f0ad4e; font-weight: 700; }
    .lms-pub-yes { color: #1ab394; font-weight: 700; }
    .lms-pub-no { color: inherit; font-weight: 600; }
    .wrapper.wrapper-content {
        width: 100%;
        max-width: 100%;
        padding-left: 10px;
        padding-right: 10px;
        box-sizing: border-box;
    }
    .wrapper.wrapper-content > .ibox {
        width: 100%;
        max-width: 100%;
        margin-left: 0;
        margin-right: 0;
    }
    .lms-assigned-wrap {
        overflow-x: hidden;
        width: 100% !important;
        max-width: 100%;
        box-sizing: border-box;
    }
    .lms-assigned-table {
        width: 100% !important;
        min-width: 0 !important;
        max-width: 100% !important;
        table-layout: fixed;
        margin-bottom: 0;
        font-size: 11px;
    }
    .lms-assigned-table > thead > tr > th,
    .lms-assigned-table > tbody > tr > td {
        vertical-align: top;
        padding: 6px 4px;
        word-wrap: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
        overflow: visible;
    }
    .lms-assigned-table .col-appt { width: 9%; }
    .lms-assigned-table .col-when { width: 12%; font-weight: 700; }
    .lms-assigned-table .col-when .lms-timing-line { display: block; line-height: 1.35; }
    .lms-assigned-table .col-taker { width: 14%; }
    .lms-assigned-table .col-test { width: 14%; }
    .lms-assigned-table .col-timing { width: 7%; }
    .lms-assigned-table .col-assessor { width: 11%; font-weight: 700; }
    .lms-assigned-table .col-created { width: 7%; }
    .lms-assigned-table .col-status { width: 8%; }
    .lms-assigned-table .col-pub { width: 5%; }
    .lms-assigned-table .col-actions {
        width: 13%;
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
    .page-heading .btn,
    .lms-filter-actions .btn,
    .lms-assigned-table .col-actions .btn {
        font-weight: 700 !important;
    }
    .page-heading .btn-default,
    .lms-filter-actions .btn-default {
        background: #5bc0de;
        border-color: #46b8da;
        color: #fff !important;
    }
    .page-heading .btn-default:hover,
    .page-heading .btn-default:focus,
    .lms-filter-actions .btn-default:hover,
    .lms-filter-actions .btn-default:focus {
        background: #31b0d5;
        border-color: #269abc;
        color: #fff !important;
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
    .lms-assigned-pager {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 12px 0 4px;
        flex-wrap: wrap;
    }
    .lms-assigned-pager .lms-page-label { font-weight: 600; color: #333; }
    .lms-filter-bar {
        display: flex;
        flex-wrap: nowrap;
        align-items: flex-end;
        gap: 8px;
        width: 100%;
    }
    .lms-filter-bar .lms-filter-field {
        flex: 1 1 0;
        min-width: 0;
        margin-bottom: 0;
    }
    .lms-filter-bar .lms-filter-field label {
        display: block;
        margin-bottom: 4px;
        font-weight: 600;
    }
    .lms-filter-bar .lms-filter-search {
        flex: 1.4 1 0;
    }
    .lms-filter-bar .lms-filter-actions {
        flex: 0 0 auto;
        display: flex;
        gap: 6px;
        align-items: center;
        padding-bottom: 1px;
    }
    .lms-filter-bar .form-control {
        width: 100%;
        min-width: 0;
    }
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
        <h2>@if(!empty($resultsView) && !empty($selectedAssessment)) Results: {{ $selectedAssessment->title }} @else Assigned Tests @endif</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">ALL TESTS</a></li>
            <li class="active"><strong>@if(!empty($resultsView)) Results @else Assigned Tests @endif</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.assessments.tests.assigned-users', array_merge(request()->query(), ['export' => 'excel'])) }}" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Download Excel</a>
        <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default btn-sm">ALL TESTS</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="ibox"><div class="ibox-content">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            @if(!empty($resultsView))
                <input type="hidden" name="view" value="results">
            @endif
            <div class="lms-filter-bar">
                <div class="lms-filter-field">
                    <label>Assessment</label>
                    <select name="assessment_id" class="form-control lms-searchable" data-placeholder="Type to find assessment">
                        <option value="0">All</option>
                        @foreach($assessments as $a)
                            <option value="{{ $a->id }}" @selected((int)($filters['assessment_id'] ?? 0) === (int)$a->id)>{{ $a->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-field">
                    <label>Batch</label>
                    <select name="batch_id" class="form-control lms-searchable" data-placeholder="Type to find batch">
                        <option value="0">All</option>
                        @foreach($batches as $b)
                            <option value="{{ $b->id }}" @selected((int)($filters['batch_id'] ?? 0) === (int)$b->id)>{{ $b->code }} — {{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-field">
                    <label>Course</label>
                    <select name="course_id" class="form-control lms-searchable" data-placeholder="Type to find course">
                        <option value="0">All</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" @selected((int)($filters['course_id'] ?? 0) === (int)$c->id)>{{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-field">
                    <label>Type</label>
                    <select name="kind" class="form-control lms-searchable" data-placeholder="Type to find type">
                        <option value="">All</option>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected(($filters['kind'] ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-field">
                    <label>Status</label>
                    <select name="status" class="form-control lms-searchable" data-placeholder="Type to find status">
                        <option value="">All</option>
                        @foreach(($statusOptions ?? []) as $sk => $sl)
                            <option value="{{ $sk }}" @selected(($filters['status'] ?? '') === $sk)>{{ $sl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lms-filter-field lms-filter-search">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email, test, course…">
                </div>
                <div class="lms-filter-actions">
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
                <small class="text-muted">
                    ({{ $rowsTotal ?? $rows->count() }}
                    @if(!empty($statusCounts['Missed']))
                        · <span class="lms-status-missed">Missed {{ (int) $statusCounts['Missed'] }}</span>
                    @endif)
                </small>
            </h5>
        </div>
        <div class="ibox-content lms-assigned-wrap">
            <table class="table table-striped table-bordered lms-assigned-table js-sortable-table">
                <thead>
                    <tr>
                        <th class="col-appt" data-sort-col="0">Appointment ID <span class="sort-ind"></span></th>
                        <th class="col-when" data-sort-col="1">Appointment Date &amp; Time <span class="sort-ind"></span></th>
                        <th class="col-taker" data-sort-col="2">Test Taker <span class="sort-ind"></span></th>
                        <th class="col-test" data-sort-col="3">Test Name <span class="sort-ind"></span></th>
                        <th class="col-timing" data-sort-col="4">Test Duration <span class="sort-ind"></span></th>
                        <th class="col-assessor" data-sort-col="5">Assessor <span class="sort-ind"></span></th>
                        <th class="col-created" data-sort-col="6">Created by <span class="sort-ind"></span></th>
                        <th class="col-status" data-sort-col="7">Status <span class="sort-ind"></span></th>
                        <th class="col-pub" data-sort-col="8">Published <span class="sort-ind"></span></th>
                        <th class="col-actions">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php
                            $attempt = $row['attempt'] ?? null;
                            $assignment = $row['assignment'] ?? null;
                            $assessment = $row['assessment'] ?? $attempt?->assessment;
                            if (! $assessment) {
                                continue;
                            }
                            $disp = $row['sitting_display'] ?? [];
                            if ($attempt) {
                                $statusClass = $attempt->statusColorClass();
                                $statusText = $attempt->statusLabel();
                            } elseif (! empty($disp['status'])) {
                                $statusText = $disp['status'];
                                $statusClass = match ($statusText) {
                                    'Missed' => 'lms-status-missed',
                                    'Upcoming' => 'lms-status-upcoming',
                                    'In progress' => 'lms-status-in_progress',
                                    'Marking Required' => 'lms-status-completed',
                                    'Completed and Marked', 'Completed' => 'lms-status-marked',
                                    default => 'lms-status-available',
                                };
                            } elseif ($assignment && $assignment->isNotYetOpen()) {
                                $statusClass = 'lms-status-upcoming';
                                $statusText = 'Upcoming';
                            } elseif ($assignment && method_exists($assignment, 'isMissedByStartGrace') && $assignment->isMissedByStartGrace()) {
                                $statusClass = 'lms-status-missed';
                                $statusText = 'Missed';
                            } elseif ($assignment && ! $assignment->isSelfSitting() && $assignment->isExpired()) {
                                $statusClass = 'lms-status-missed';
                                $statusText = 'Missed';
                            } else {
                                $statusClass = 'lms-status-available';
                                $statusText = 'Assigned';
                            }
                            $spend = $attempt?->spendMinutes();
                            $appointment = $disp['appointment'] ?? null;
                            $isSelfRow = $assignment && method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting();
                            if ($isSelfRow) {
                                $appointment = $assignment && method_exists($assignment, 'appointmentStartLabel')
                                    ? $assignment->appointmentStartLabel()
                                    : 'Open to start the test';
                            } elseif ($appointment === null || $appointment === '' || $appointment === '—'
                                || $appointment === 'Open (no appointment set)' || $appointment === 'Open'
                                || $appointment === 'Start anytime' || $appointment === 'Self test — start anytime') {
                                $appointment = $assignment && method_exists($assignment, 'appointmentStartLabel')
                                    ? $assignment->appointmentStartLabel()
                                    : ($assignment?->appointmentWindowLabel() ?: null);
                                $looksBlank = $appointment === null || $appointment === '' || $appointment === '—'
                                    || $appointment === 'Open (no appointment set)' || $appointment === 'Open'
                                    || $appointment === 'Start anytime' || $appointment === 'Self test — start anytime';
                                if ($looksBlank && $assignment && method_exists($assignment, 'windowStart') && $assignment->windowStart()) {
                                    $appointment = $assignment->windowStart()->format('d/m/Y, H:i');
                                    $looksBlank = false;
                                }
                                if ($looksBlank) {
                                    $appointment = '—';
                                }
                            }
                            $assessorName = $disp['assessor'] ?? '';
                            $assessorBlank = $assessorName === ''
                                || $assessorName === '—'
                                || strcasecmp((string) $assessorName, 'Admin / course instructors') === 0
                                || strcasecmp((string) $assessorName, 'Admin') === 0;
                            if ($assessorBlank) {
                                $assessorName = $attempt
                                    ? $attempt->assessorLabel($assignment)
                                    : ($assignment && method_exists($assignment, 'assessorDisplayName')
                                        ? $assignment->assessorDisplayName()
                                        : ($assignment?->marker?->name ?? ''));
                            }
                            if ($assessorName === '' || $assessorName === '—' || strcasecmp((string) $assessorName, 'Admin / course instructors') === 0) {
                                $assessorName = $disp['assessor'] ?? 'Admin / course instructors';
                            }
                            $assessorUserId = $disp['assessor_id'] ?? ($attempt
                                ? $attempt->assessorUserId($assignment)
                                : ($assignment && $assignment->marker_user_id ? (int) $assignment->marker_user_id : null));
                            $course = $assessment->course ?? null;
                            $courseTitle = $disp['course'] ?? ($course->title ?? null);
                            $durationMins = $assessment->durationMinutes();
                            if ((int) $durationMins <= 0) {
                                $durationMins = 180;
                            }
                            $studentId = (int) ($row['student']->id ?? 0);
                            $apptId = $disp['appt_id'] ?? null;
                            if ($assignment && $studentId > 0 && method_exists($assignment, 'displayCodeForStudent')) {
                                $apptId = $assignment->displayCodeForStudent($studentId);
                            } elseif ($apptId === null || $apptId === '' || $apptId === 'BEXT0') {
                                $apptId = ! empty($attempt?->assignment_id)
                                    ? ('BEXT' . (int) $attempt->assignment_id . ($studentId > 0 ? ('-' . $studentId) : ''))
                                    : (! empty($attempt?->id) ? ('Sit' . (int) $attempt->id) : '—');
                            }
                            $timezoneLabel = $disp['timezone'] ?? $assignment?->timezoneLabel();
                            if (! empty($isSelfRow)) {
                                $timezoneLabel = '';
                            } elseif ($timezoneLabel === null || $timezoneLabel === '' || ! empty($isMock1Row)) {
                                $timezoneLabel = ! empty($isMock1Row)
                                    ? 'Asia/Dubai · GST'
                                    : (string) ($timezoneLabel ?: config('app.timezone', 'Asia/Dubai'));
                            }
                            $createdBy = $disp['created_by'] ?? $assignment?->createdByLabel();
                            if ($createdBy === null || $createdBy === '') {
                                $createdBy = method_exists($assessment, 'createdByLabel')
                                    ? $assessment->createdByLabel()
                                    : 'Admin';
                            }
                            if ($createdBy === null || $createdBy === '') {
                                $createdBy = 'Admin';
                            }
                            $batchCode = $disp['batch'] ?? ($assignment?->batch?->code ?? $assignment?->batch?->name);
                            if (($batchCode === null || $batchCode === '') && ! empty($isMock1Row)) {
                                $batchCode = 'BAT-0004';
                            }
                            $attemptNo = (int) ($row['attempt_number'] ?? 1);
                            $attemptTotal = max((int) ($row['attempt_total'] ?? 1), max(1, (int) ($assessment->max_attempts ?? 1)));
                            $attemptNo = min($attemptTotal, max(1, $attemptNo));
                            if (! $attempt && $statusText === 'Assigned' && ! ($assignment && $assignment->isSelfSitting())) {
                                $startCheck = $assignment && method_exists($assignment, 'windowStart') ? $assignment->windowStart() : null;
                                if (! $startCheck && ! empty($appointment) && $appointment !== '—' && stripos((string) $appointment, 'anytime') === false) {
                                    try {
                                        $startCheck = \Carbon\Carbon::createFromFormat('d/m/Y, H:i', $appointment);
                                    } catch (\Throwable $e) {
                                        $startCheck = null;
                                    }
                                }
                                if ($startCheck) {
                                    $endCheck = $assignment && method_exists($assignment, 'appointmentEnd') ? $assignment->appointmentEnd() : null;
                                    if (! $endCheck) {
                                        $endCheck = $startCheck->copy()->addMinutes(max(1, (int) $durationMins));
                                    }
                                    if (now()->gt($endCheck)) {
                                        $statusClass = 'lms-status-missed';
                                        $statusText = 'Missed';
                                    }
                                }
                            }
                        @endphp
                        @if($assessment)
                        <tr>
                            <td class="col-appt" data-sort="{{ $assignment->id ?? 0 }}"><strong>{{ $apptId }}</strong></td>
                            <td class="col-when" data-sort="{{ $appointment }}">
                                <span class="lms-timing-line">{{ $appointment }}</span>
                                @if($timezoneLabel !== null && $timezoneLabel !== '')
                                    <br>
                                    <span class="lms-timing-line text-muted">{{ $timezoneLabel }}</span>
                                @endif
                            </td>
                            <td class="col-taker" data-sort="{{ $row['student']->name ?? '' }}">
                                @if(!empty($row['student']->id))
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $row['student']->id }}">{{ $row['student']->name ?? '—' }}</a>
                                @else
                                    {{ $row['student']->name ?? '—' }}
                                @endif
                                <br><small class="text-muted">{{ $row['student']->email ?? '' }}</small>
                                @if(!empty($batchCode))
                                    <br><small class="text-muted">Batch: {{ $batchCode }}</small>
                                @endif
                                @if($course)
                                    <br><small>
                                        @if(!empty($course->slug))
                                            <a href="{{ route('course.details', ['course' => $course->slug]) }}" target="_blank" rel="noopener noreferrer">{{ $course->title }}</a>
                                        @else
                                            <a href="{{ route('course.show', $course->id) }}" target="_blank" rel="noopener noreferrer">{{ $course->title }}</a>
                                        @endif
                                    </small>
                                @elseif(!empty($courseTitle))
                                    <br><small>{{ $courseTitle }}</small>
                                @endif
                            </td>
                            <td class="col-test" data-sort="{{ $assessment->title ?? ('Test #'.($assignment->assessment_id ?? '')) }}">
                                <strong>{{ $assessment->title ?: ('Test #'.$assessment->id) }}</strong>
                                <br><small class="text-muted"><strong>{{ $assessment->displayCode() }} · {{ $assessment->kindLabel() }} · {{ ($assignment && method_exists($assignment, 'sittingKindLabel')) ? $assignment->sittingKindLabel() : 'Test' }}</strong></small>
                                <br><small><strong>Attempt {{ $attemptNo }} of {{ $attemptTotal }}</strong></small>
                            </td>
                            <td class="col-timing" data-sort="{{ (int) $durationMins }}">
                                {{ $fmtDuration($durationMins) }}
                            </td>
                            <td class="col-assessor" data-sort="{{ $assessorName }}">
                                @if($assessorUserId)
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $assessorUserId }}">{{ $assessorName }}</a>
                                @else
                                    {{ $assessorName }}
                                @endif
                            </td>
                            <td class="col-created" data-sort="{{ $createdBy }}">
                                @if($assignment && $assignment->assigned_by_user_id)
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $assignment->assigned_by_user_id }}">{{ $createdBy }}</a>
                                @else
                                    {{ $createdBy }}
                                @endif
                            </td>
                            <td class="col-status" data-sort="{{ $statusText }}">
                                <span class="{{ $statusClass }}">{{ $statusText }}</span>
                                @if($statusText === 'Missed' && (int) ($assessment->max_attempts ?? 1) > 1)
                                    <br><small class="text-muted">Student can Retake</small>
                                @endif
                                @if($attempt && $attempt->score_raw !== null && ! $assessment->hidesOverallMarks() && ($attempt->results_published || $attempt->status === 'graded' || !empty($resultsView)))
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
                                    $canAdminSitting = \Illuminate\Support\Facades\Auth::guard('admin')->check();
                                @endphp
                                @if($attempt && in_array($attempt->status, ['submitted', 'graded', 'expired'], true))
                                    <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.mark', $attempt->id) }}">Mark</a>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.attempt-pdf', $attempt->id) }}" target="_blank">Download PDF</a>
                                    @if(! $attempt->results_published && $attempt->status === 'graded')
                                        <form action="{{ route('admin.assessments.tests.publish-attempt', $attempt->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-xs btn-primary" type="submit">Publish</button>
                                        </form>
                                    @endif
                                @endif
                                @if($canAdminSitting && $assignment)
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.edit-assignment', $assignment->id) }}">Edit</a>
                                    <form action="{{ route('admin.assessments.tests.destroy-assignment', $assignment->id) }}" method="POST" onsubmit="return confirm('Remove this student from the assigned test?');">
                                        @csrf
                                        <input type="hidden" name="student_id" value="{{ (int) $studentId }}">
                                        @if($attempt)
                                            <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">
                                        @endif
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.assign', ['id' => $assessment->id, 'mode' => $assignment->isSelfSitting() ? 'self' : 'exam']) }}">Assign again</a>
                                @elseif($canAdminSitting && $attempt)
                                    <form action="{{ route('admin.assessments.tests.destroy-attempt', $attempt->id) }}" method="POST" onsubmit="return confirm('Delete this test taker sitting?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endif
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">@if(!empty($resultsView)) No attempts yet for this test. @else No assigned test takers found. @endif</td></tr>
                    @endforelse
                </tbody>
            </table>
            @include('admin.assessments.partials.table-pager', ['pager' => $rows])
        </div>
    </div>
</div>
@include('admin.assessments.partials.user-preview-modal')
@endsection
