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
    .lms-attempts-wrap { overflow-x: auto; }
    .lms-attempts-table {
        width: 100%;
        max-width: 100%;
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
    .lms-attempts-table .col-appt { width: 10%; white-space: nowrap !important; }
    .lms-attempts-table .col-test { width: 17%; }
    .lms-attempts-table .col-taker { width: 12%; }
    .lms-attempts-table .col-timing { width: 28%; }
    .lms-attempts-table .col-created { width: 8%; }
    .lms-attempts-table .col-assessor { width: 8%; }
    .lms-attempts-table .col-status { width: 11%; }
    .lms-attempts-table .col-pub { width: 6%; }
    .lms-attempts-table .col-actions {
        width: 12%;
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
                            $attempt = $row['attempt'] ?? null;
                            $assignment = $row['assignment'];
                            $assessmentRow = $row['assessment'];
                            $student = $row['student'];
                            $attemptNo = (int) ($row['attempt_number'] ?? 1);
                            $attemptTotal = (int) ($row['attempt_total'] ?? 1);
                            if ($attempt) {
                                $statusClass = $attempt->statusColorClass();
                                $statusText = $attempt->statusLabel();
                            } elseif ($assignment && $assignment->isNotYetOpen()) {
                                $statusClass = 'lms-status-upcoming';
                                $statusText = 'Upcoming';
                            } elseif ($assignment && $assignment->isExpired()) {
                                $statusClass = 'lms-status-missed';
                                $statusText = 'Missed';
                            } else {
                                $statusClass = 'lms-status-available';
                                $statusText = 'Assigned';
                            }
                            $spend = $attempt?->spendMinutes();
                            $appointment = $assignment ? $assignment->appointmentWindowLabel() : '—';
                            $assessorName = $attempt
                                ? $attempt->assessorLabel($assignment)
                                : ($assignment?->marker->name ?? 'Admin / course instructors');
                            $assessorUserId = $attempt
                                ? $attempt->assessorUserId($assignment)
                                : ($assignment && $assignment->marker_user_id ? (int) $assignment->marker_user_id : null);
                            $course = $assessmentRow->course ?? null;
                            $createdBy = $assignment ? $assignment->createdByLabel() : '—';
                            $apptId = $assignment ? $assignment->displayCode() : '—';
                        @endphp
                        <tr>
                            <td class="col-appt" data-sort="{{ $assignment->id ?? 0 }}"><strong>{{ $apptId }}</strong></td>
                            <td class="col-test" data-sort="{{ $assessmentRow->title ?? '' }}">
                                <strong>{{ $assessmentRow->title ?: ('Test #'.$assessmentRow->id) }}</strong>
                                <br><small class="text-muted">{{ $assessmentRow->displayCode() }} · {{ $assessmentRow->kindLabel() }}</small>
                                <br><small><strong>Attempt {{ $attemptNo }} of {{ $attemptTotal }}</strong></small>
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
                            <td class="col-timing" data-sort="{{ optional($assignment->available_from)->timestamp ?? 0 }}">
                                <span class="lms-timing-line text-muted"><strong>Timezone:</strong> {{ $assignment->timezoneLabel() }}</span>
                                <span class="lms-timing-line"><strong>Exam Duration:</strong> {{ $fmtDuration($assessmentRow->durationMinutes()) }}</span>
                                <span class="lms-timing-line"><strong>Started:</strong> {{ ($attempt?->started_at) ? $assignment->formatInTimezone($attempt->started_at) : '—' }}</span>
                                <span class="lms-timing-line"><strong>Ended:</strong> {{ ($attempt?->submitted_at) ? $assignment->formatInTimezone($attempt->submitted_at) : '—' }}</span>
                                <span class="lms-timing-line"><strong>Time spent:</strong> {{ $spend !== null ? $fmtDuration($spend) : '—' }}</span>
                            </td>
                            <td class="col-created" data-sort="{{ $createdBy }}">
                                @if($assignment->assigned_by_user_id)
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $assignment->assigned_by_user_id }}">{{ $createdBy }}</a>
                                @else
                                    {{ $createdBy }}
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
                                @if($attempt && $attempt->score_raw !== null)
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
                                    $canManageSitting = $assessmentRow
                                        && app(\App\Services\LmsAssessmentService::class)->canManageSittingsFor($assessmentRow);
                                @endphp
                                @if($attempt && in_array($attempt->status, ['submitted', 'graded', 'expired'], true))
                                    <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.mark', $attempt->id) }}">View Details</a>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.attempt-pdf', $attempt->id) }}" target="_blank">Download PDF</a>
                                    @unless($attempt->results_published)
                                        <form action="{{ route('admin.assessments.tests.publish-attempt', $attempt->id) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-xs btn-primary" type="submit">Publish</button>
                                        </form>
                                    @endunless
                                @endif
                                @if($canManageSitting && $assignment)
                                    <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.edit-assignment', $assignment->id) }}">Edit</a>
                                    <form action="{{ route('admin.assessments.tests.destroy-assignment', $assignment->id) }}" method="POST" onsubmit="return confirm('Delete this sitting ({{ $apptId }})?');">
                                        @csrf
                                        @method('DELETE')
                                        @if($attempt)
                                            <input type="hidden" name="attempt_id" value="{{ $attempt->id }}">
                                        @endif
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.assign', $assessmentRow->id) }}">Assign again</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted">No assignments yet for this test. Use Assign to create sittings.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('admin.assessments.partials.user-preview-modal')
@endsection
