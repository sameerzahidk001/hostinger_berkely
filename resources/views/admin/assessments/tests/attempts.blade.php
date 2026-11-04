@extends(lms_portal_layout())
@section('title', 'Results — ' . $assessment->title)
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
    .lms-attempts-wrap { overflow-x: auto; width: 100%; }
    .lms-attempts-table {
        width: 100% !important;
        min-width: 1200px;
        max-width: none;
        table-layout: fixed;
        margin-bottom: 0;
        font-size: 12px;
    }
    .lms-attempts-table > thead > tr > th,
    .lms-attempts-table > tbody > tr > td {
        vertical-align: middle;
        padding: 6px 5px;
        word-wrap: break-word;
        overflow-wrap: break-word;
        white-space: normal;
    }
    .lms-attempts-table .col-appt { width: 8%; }
    .lms-attempts-table .col-when { width: 13%; font-weight: 700; }
    .lms-attempts-table .col-when .lms-timing-line { display: block; line-height: 1.35; }
    .lms-attempts-table .col-taker { width: 12%; }
    .lms-attempts-table .col-test { width: 12%; }
    .lms-attempts-table .col-timing { width: 14%; }
    .lms-attempts-table .col-assessor { width: 10%; min-width: 120px; font-weight: 700; }
    .lms-attempts-table .col-created { width: 6%; }
    .lms-attempts-table .col-status { width: 8%; }
    .lms-attempts-table .col-pub { width: 4%; }
    .lms-attempts-table .col-actions {
        width: 11%;
        overflow-wrap: normal;
        word-break: normal;
        white-space: normal;
    }
    .lms-attempts-table .col-actions .btn,
    .lms-attempts-table .col-actions form {
        display: inline-block;
        width: auto;
        max-width: 100%;
        margin: 0 0 4px 0;
        box-sizing: border-box;
    }
    .lms-attempts-table .col-actions .btn {
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.3;
        text-align: center;
        white-space: nowrap;
    }
    .lms-attempts-table .col-actions .btn-warning { color: #fff !important; background: #f0ad4e; border-color: #eea236; }
    .lms-attempts-table .col-actions .btn-info { color: #fff !important; background: #5bc0de; border-color: #46b8da; }
    .lms-attempts-table .col-actions .btn-primary { color: #fff !important; background: #337ab7; border-color: #2e6da4; }
    .lms-attempts-table .col-actions .btn-danger { color: #fff !important; background: #d9534f; border-color: #d43f3a; }
    .lms-attempts-table .col-status .lms-score-line { display: block; margin-top: 4px; font-weight: 600; color: #333; }
    .lms-attempts-table .col-timing .lms-timing-line { display: block; line-height: 1.35; }
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
        <h2>Results: {{ $assessment->title }} <small class="text-muted">({{ $assessment->displayCode() }})</small></h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">ALL TESTS</a></li>
            <li class="active"><strong>Results</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.assessments.tests.attempts', ['id' => $assessment->id, 'export' => 'excel']) }}" class="btn btn-success btn-sm"><i class="fa fa-file-excel-o"></i> Download Excel</a>
        <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default btn-sm">Back to ALL TESTS</a>
        <a href="{{ route('admin.assessments.tests.assigned-users', ['assessment_id' => $assessment->id]) }}" class="btn btn-default btn-sm">Assigned Tests</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <p class="text-muted m-b-sm">
        All assigned sittings for this test (completed or not). Attempt number is shown under the test title (e.g. Attempt 2 of 3).
    </p>
    <div class="ibox">
        <div class="ibox-content lms-attempts-wrap">
            <table class="table table-striped table-bordered lms-attempts-table js-sortable-table">
                <thead>
                    <tr>
                        <th class="col-appt" data-sort-col="0">Appointment ID <span class="sort-ind"></span></th>
                        <th class="col-when" data-sort-col="1">Appointment Date &amp; Time <span class="sort-ind"></span></th>
                        <th class="col-taker" data-sort-col="2">Test Taker <span class="sort-ind"></span></th>
                        <th class="col-test" data-sort-col="3">Test Name <span class="sort-ind"></span></th>
                        <th class="col-timing" data-sort-col="4">Test Time Details <span class="sort-ind"></span></th>
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
                            $assignment = $row['assignment'];
                            $assessmentRow = $row['assessment'];
                            $student = $row['student'];
                            $attemptNo = (int) ($row['attempt_number'] ?? 1);
                            $attemptTotal = max((int) ($row['attempt_total'] ?? 1), max(1, (int) ($assessmentRow->max_attempts ?? 1)));
                            $attemptNo = min($attemptTotal, max(1, $attemptNo));
                            if ($attempt) {
                                $statusClass = $attempt->statusColorClass();
                                $statusText = $attempt->statusLabel();
                            } elseif ($assignment && $assignment->isNotYetOpen()) {
                                $statusClass = 'lms-status-upcoming';
                                $statusText = 'Upcoming';
                            } elseif ($assignment && method_exists($assignment, 'isMissedByStartGrace') && $assignment->isMissedByStartGrace()) {
                                $statusClass = 'lms-status-missed';
                                $statusText = 'Missed';
                            } else {
                                $statusClass = 'lms-status-available';
                                $statusText = 'Assigned';
                            }
                            $durationMins = $assessmentRow->durationMinutes();
                            if ((int) $durationMins <= 0) {
                                $durationMins = 180;
                            }
                            $spend = $attempt?->spendMinutes();
                            $isSelfRow = $assignment && method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting();
                            $appointment = $isSelfRow
                                ? ($assignment && method_exists($assignment, 'appointmentStartLabel')
                                    ? $assignment->appointmentStartLabel()
                                    : 'Open to start the test')
                                : ($assignment?->windowStart()?->format('d/m/Y, H:i') ?? '—');
                            $timezoneLabel = $isSelfRow ? '' : ($assignment?->timezoneLabel() ?: (string) config('app.timezone', 'Asia/Dubai'));
                            $assessorName = $attempt
                                ? $attempt->assessorLabel($assignment)
                                : ($assignment?->marker->name ?? 'Admin / course instructors');
                            $assessorUserId = $attempt
                                ? $attempt->assessorUserId($assignment)
                                : ($assignment && $assignment->marker_user_id ? (int) $assignment->marker_user_id : null);
                            $course = $assessmentRow->course ?? null;
                            $createdBy = $assignment ? $assignment->createdByLabel() : '—';
                            $apptId = ($assignment && ! empty($student->id) && method_exists($assignment, 'displayCodeForStudent'))
                                ? $assignment->displayCodeForStudent((int) $student->id)
                                : ($assignment ? $assignment->displayCode() : '—');
                        @endphp
                        <tr>
                            <td class="col-appt" data-sort="{{ $assignment->id ?? 0 }}"><strong>{{ $apptId }}</strong></td>
                            <td class="col-when" data-sort="{{ $appointment }}">
                                <span class="lms-timing-line">{{ $appointment }}</span>
                                @if($timezoneLabel !== '')
                                    <br>
                                    <span class="lms-timing-line text-muted">{{ $timezoneLabel }}</span>
                                @endif
                            </td>
                            <td class="col-taker" data-sort="{{ $student->name ?? '' }}">
                                @if(!empty($student->id))
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $student->id }}">{{ $student->name ?? '—' }}</a>
                                @else
                                    {{ $student->name ?? '—' }}
                                @endif
                                <br><small class="text-muted">{{ $student->email ?? '' }}</small>
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
                            <td class="col-test" data-sort="{{ $assessmentRow->title ?? '' }}">
                                <strong>{{ $assessmentRow->title ?: ('Test #'.$assessmentRow->id) }}</strong>
                                <br><small class="text-muted"><strong>{{ $assessmentRow->displayCode() }} · {{ $assessmentRow->kindLabel() }} · {{ ($assignment && method_exists($assignment, 'sittingKindLabel')) ? $assignment->sittingKindLabel() : 'Test' }}</strong></small>
                                <br><small><strong>Attempt {{ $attemptNo }} of {{ $attemptTotal }}</strong></small>
                            </td>
                            <td class="col-timing" data-sort="{{ (int) $durationMins }}">
                                <span class="lms-timing-line"><strong>Test Duration:</strong> {{ $fmtDuration($durationMins) }}</span>
                                @if($attempt)
                                    @php
                                        $startedDisp = $attempt->startedAtForDisplay();
                                        $endedDisp = $attempt->endedAtForDisplay();
                                        $startedTxt = ($assignment && $startedDisp && method_exists($assignment, 'formatInTimezone'))
                                            ? $assignment->formatInTimezone($startedDisp)
                                            : (optional($startedDisp)->format('d/m/Y, H:i') ?? '—');
                                        $endedTxt = ($assignment && $endedDisp && method_exists($assignment, 'formatInTimezone'))
                                            ? $assignment->formatInTimezone($endedDisp)
                                            : (optional($endedDisp)->format('d/m/Y, H:i') ?? '—');
                                    @endphp
                                    <span class="lms-timing-line"><strong>Started:</strong> {{ $startedTxt }}</span>
                                    <span class="lms-timing-line"><strong>Ended:</strong> {{ $endedTxt }}</span>
                                    <span class="lms-timing-line"><strong>Time spent:</strong> {{ $spend !== null ? $fmtDuration($spend) : '—' }}</span>
                                    <span class="lms-timing-line"><strong>Pause duration:</strong> {{ $attempt->pauseDurationLabel() }}</span>
                                @else
                                    <span class="lms-timing-line"><strong>Started:</strong> —</span>
                                    <span class="lms-timing-line"><strong>Ended:</strong> —</span>
                                    <span class="lms-timing-line"><strong>Time spent:</strong> —</span>
                                    <span class="lms-timing-line"><strong>Pause duration:</strong> —</span>
                                @endif
                            </td>
                            <td class="col-assessor" data-sort="{{ $assessorName }}">
                                @if($assessorUserId)
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $assessorUserId }}">{{ $assessorName }}</a>
                                @else
                                    {{ $assessorName }}
                                @endif
                            </td>
                            <td class="col-created" data-sort="{{ $createdBy }}">
                                @if($assignment->assigned_by_user_id)
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $assignment->assigned_by_user_id }}">{{ $createdBy }}</a>
                                @else
                                    {{ $createdBy }}
                                @endif
                            </td>
                            <td class="col-status" data-sort="{{ $statusText }}">
                                <span class="{{ $statusClass }}">{{ $statusText }}</span>
                                @if($attempt && $attempt->results_published && $attempt->score_raw !== null && ! $assessmentRow->hidesOverallMarks())
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
                                        @if(!empty($student->id))
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                        @endif
                                        @if($attempt)
                                            <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">
                                        @endif
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                    @if(! ($assessmentRow && method_exists($assessmentRow, 'isDisabled') && $assessmentRow->isDisabled()))
                                        <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.assign', ['id' => $assessmentRow->id, 'mode' => ($assignment && $assignment->isSelfSitting()) ? 'self' : 'exam']) }}">Assign again</a>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">No assignments yet for this test. Use Assign to create sittings.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('admin.assessments.partials.user-preview-modal')
@endsection
