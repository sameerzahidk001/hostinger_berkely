@extends('user.layout.app')
@section('title', $section === 'mocks' ? 'Mock Exams' : ($section === 'skill' ? 'Skill Tests' : 'My Tests'))
@include('admin.assessments.partials.sortable-table-assets')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $section === 'mocks' ? 'Mock Exams' : ($section === 'skill' ? 'Skill Tests' : 'My Tests') }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Home</a></li>
            <li class="active"><strong>{{ $section === 'mocks' ? 'Mock Exams' : ($section === 'skill' ? 'Skill Tests' : 'My Tests') }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content" id="assessments-portal">
    <style>
        /* Student theme sets .nav > li > a { color: white } for the sidebar —
           that makes content tabs invisible on a light background. */
        #assessments-portal .nav-tabs > li > a,
        #assessments-portal .nav-pills > li > a {
            color: #2f4050 !important;
            font-weight: 600;
            background: #f5f5f5;
            border: 1px solid #e7eaec;
            margin-right: 4px;
            padding: 8px 14px;
        }
        #assessments-portal .nav-tabs > li > a:hover,
        #assessments-portal .nav-tabs > li > a:focus {
            color: #1ab394 !important;
            background: #fff;
        }
        #assessments-portal .nav-tabs > li.active > a,
        #assessments-portal .nav-tabs > li.active > a:hover,
        #assessments-portal .nav-tabs > li.active > a:focus {
            color: #fff !important;
            background: #1ab394;
            border-color: #1ab394;
        }
        /* Distinct status-tab colors (Available / Upcoming / …) */
        #assessments-portal .nav-pills > li > a.tab-available { background: #e8f8f5; border-color: #1ab394; color: #0f7a66 !important; }
        #assessments-portal .nav-pills > li > a.tab-upcoming { background: #eaf2ff; border-color: #3b82f6; color: #1d4ed8 !important; }
        #assessments-portal .nav-pills > li > a.tab-in-progress { background: #fff4e5; border-color: #f59e0b; color: #b45309 !important; }
        #assessments-portal .nav-pills > li > a.tab-submitted { background: #f3e8ff; border-color: #8b5cf6; color: #6d28d9 !important; }
        #assessments-portal .nav-pills > li > a.tab-results { background: #ecfeff; border-color: #06b6d4; color: #0e7490 !important; }
        #assessments-portal .nav-pills > li > a.tab-missed { background: #fef2f2; border-color: #ef4444; color: #b91c1c !important; }
        #assessments-portal .nav-pills > li.active > a.tab-available,
        #assessments-portal .nav-pills > li.active > a.tab-available:hover,
        #assessments-portal .nav-pills > li.active > a.tab-available:focus { background: #1ab394; border-color: #1ab394; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-upcoming,
        #assessments-portal .nav-pills > li.active > a.tab-upcoming:hover,
        #assessments-portal .nav-pills > li.active > a.tab-upcoming:focus { background: #3b82f6; border-color: #3b82f6; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-in-progress,
        #assessments-portal .nav-pills > li.active > a.tab-in-progress:hover,
        #assessments-portal .nav-pills > li.active > a.tab-in-progress:focus { background: #f59e0b; border-color: #f59e0b; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-submitted,
        #assessments-portal .nav-pills > li.active > a.tab-submitted:hover,
        #assessments-portal .nav-pills > li.active > a.tab-submitted:focus { background: #8b5cf6; border-color: #8b5cf6; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-results,
        #assessments-portal .nav-pills > li.active > a.tab-results:hover,
        #assessments-portal .nav-pills > li.active > a.tab-results:focus { background: #06b6d4; border-color: #06b6d4; color: #fff !important; }
        #assessments-portal .nav-pills > li.active > a.tab-missed,
        #assessments-portal .nav-pills > li.active > a.tab-missed:hover,
        #assessments-portal .nav-pills > li.active > a.tab-missed:focus { background: #ef4444; border-color: #ef4444; color: #fff !important; }
        /* Status colours inside the same table row */
        #assessments-portal .lms-row-status { font-weight: 700; }
        #assessments-portal .lms-row-available { color: #0f7a66; }
        #assessments-portal .lms-row-upcoming { color: #1d4ed8; }
        #assessments-portal .lms-row-in_progress { color: #b45309; }
        #assessments-portal .lms-row-submitted,
        #assessments-portal .lms-row-completed { color: #f59e0b; } /* Marking Required — orange */
        #assessments-portal .lms-row-graded,
        #assessments-portal .lms-row-marked { color: #1ab394; } /* Completed and Marked — green */
        #assessments-portal .lms-row-missed,
        #assessments-portal .lms-row-expired { color: #ed5565; } /* Missed — red */
        #assessments-portal .lms-assess-wrap { overflow-x: hidden; }
        #assessments-portal .lms-assess-table {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
            margin-bottom: 0;
        }
        #assessments-portal .lms-assess-table > thead > tr > th,
        #assessments-portal .lms-assess-table > tbody > tr > td {
            vertical-align: middle;
            padding: 6px 6px;
            font-size: 12px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            white-space: normal;
        }
        #assessments-portal .lms-assess-table .col-appt { width: 10%; white-space: nowrap; }
        #assessments-portal .lms-assess-table .col-title { width: 26%; }
        #assessments-portal .lms-assess-table .col-timing { width: 30%; }
        #assessments-portal .lms-assess-table .col-assessor { width: 12%; }
        #assessments-portal .lms-assess-table .col-status { width: 12%; }
        #assessments-portal .lms-assess-table th.col-appt,
        #assessments-portal .lms-assess-table td.col-appt {
            white-space: nowrap !important;
        }
        #assessments-portal .lms-assess-table .col-action {
            width: 14%;
            overflow-wrap: normal;
            word-break: normal;
        }
        #assessments-portal .lms-assess-table .col-action .btn {
            display: inline-block;
            width: auto;
            max-width: 100%;
            margin: 0 0 4px 0;
            white-space: nowrap;
            text-align: center;
            box-sizing: border-box;
            font-weight: 700;
        }
        #assessments-portal .lms-assess-table .col-action .btn-default,
        #assessments-portal .lms-assess-table .col-action .btn-warning {
            color: #fff !important;
            background: #f0ad4e;
            border-color: #eea236;
        }
        #assessments-portal .lms-assess-table .col-action .btn-success,
        #assessments-portal .lms-assess-table .col-action .btn-info {
            color: #fff !important;
            background: #5bc0de;
            border-color: #46b8da;
        }
        #assessments-portal .lms-assess-table .col-action .btn-primary {
            color: #fff !important;
            font-weight: 700;
        }
        #assessments-portal .lms-assess-table .lms-timing-line { display: block; line-height: 1.35; }
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
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <ul class="nav nav-tabs" style="margin-bottom:15px;">
        <li class="{{ $section === 'all' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'all', 'tab' => $tab]) }}">All</a></li>
        <li class="{{ $section === 'mocks' ? 'active' : '' }}"><a href="{{ route('user.assessments.mocks', ['tab' => $tab]) }}">Mock Exams</a></li>
        <li class="{{ $section === 'practice' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'practice', 'tab' => $tab]) }}">Practice / General</a></li>
        @if(!empty($isStaff))
            <li class="{{ $section === 'skill' ? 'active' : '' }}"><a href="{{ route('user.assessments.index', ['section' => 'skill', 'tab' => $tab]) }}">Skill tests</a></li>
        @endif
    </ul>

    <ul class="nav nav-pills" style="margin-bottom:15px;">
        <li class="{{ $tab === 'available' ? 'active' : '' }}">
            <a class="tab-available" href="{{ request()->fullUrlWithQuery(['tab' => 'available']) }}">Available ({{ $counts['available'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'upcoming' ? 'active' : '' }}">
            <a class="tab-upcoming" href="{{ request()->fullUrlWithQuery(['tab' => 'upcoming']) }}">Upcoming ({{ $counts['upcoming'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'in_progress' ? 'active' : '' }}">
            <a class="tab-in-progress" href="{{ request()->fullUrlWithQuery(['tab' => 'in_progress']) }}">In progress ({{ $counts['in_progress'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'submitted' ? 'active' : '' }}">
            <a class="tab-submitted" href="{{ request()->fullUrlWithQuery(['tab' => 'submitted']) }}">Submitted ({{ $counts['submitted'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'results' ? 'active' : '' }}">
            <a class="tab-results" href="{{ request()->fullUrlWithQuery(['tab' => 'results']) }}">Results ({{ $counts['results'] ?? 0 }})</a>
        </li>
        <li class="{{ $tab === 'missed' ? 'active' : '' }}">
            <a class="tab-missed" href="{{ request()->fullUrlWithQuery(['tab' => 'missed']) }}">Missed ({{ $counts['missed'] ?? 0 }})</a>
        </li>
    </ul>

    @php
        $nextOpenAtMs = null;
        if ($tab === 'upcoming') {
            foreach ($list as $row) {
                $start = $row['assignment']?->windowStart();
                if ($start) {
                    $ms = $start->getTimestampMs();
                    if ($ms > now($row['assignment']->timezoneName())->getTimestampMs() && ($nextOpenAtMs === null || $ms < $nextOpenAtMs)) {
                        $nextOpenAtMs = $ms;
                    }
                }
            }
        }
        $availableTabUrl = request()->fullUrlWithQuery(['tab' => 'available']);
    @endphp
    <div class="ibox"><div class="ibox-content lms-assess-wrap">
        <table class="table table-striped table-bordered lms-assess-table js-sortable-table">
            <thead>
                <tr>
                    <th class="col-appt" data-sort-col="0" style="white-space:nowrap;">Appointment ID <span class="sort-ind"></span></th>
                    <th class="col-title" data-sort-col="1">Test <span class="sort-ind"></span></th>
                    <th class="col-timing" data-sort-col="2">Timing <span class="sort-ind"></span></th>
                    <th class="col-assessor" data-sort-col="3">Assessor <span class="sort-ind"></span></th>
                    <th class="col-status" data-sort-col="4">Status <span class="sort-ind"></span></th>
                    <th class="col-action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list as $row)
                    @php
                        $a = $row['assessment'];
                        $asg = $row['assignment'];
                        $att = $row['attempt'] ?? null;
                        $spend = $att?->spendMinutes();
                        $used = (int) ($row['attempts_used'] ?? 0);
                        $maxAtt = $row['max_attempts'] ?? null;
                        $canRetake = !empty($row['can_retake']);
                        $attemptNo = (int) ($row['attempt_number'] ?? 1);
                        $attemptTotal = max(1, (int) ($row['attempt_total'] ?? ($maxAtt ?: 1)), $attemptNo);
                        $apptId = $asg ? $asg->displayCode() : '—';
                        $course = $a->course ?? null;
                        $assessorName = $att
                            ? $att->assessorLabel($asg)
                            : ($asg?->marker?->name ?? '—');
                        $assessorEmail = $asg?->marker?->email ?? null;
                        $canDownloadPdf = $att && (
                            $att->results_published
                            || ($a->results_mode === 'instant_mcq' && $att->status === 'graded')
                        );
                        $statusSort = $att ? $att->statusLabel() : ($tab === 'missed' ? 'Missed' : ($tab === 'upcoming' ? 'Upcoming' : 'Available'));
                    @endphp
                    <tr>
                        <td class="col-appt" data-sort="{{ $asg->id ?? 0 }}" style="white-space:nowrap;"><strong>{{ $apptId }}</strong></td>
                        <td class="col-title" data-sort="{{ $a->title }}">
                            {{ $a->title }}
                            <br><small class="text-muted">{{ $a->displayCode() }} · {{ $a->kindLabel() }}</small>
                            <br><small><strong>Attempt {{ $attemptNo }} of {{ $attemptTotal }}</strong></small>
                            @if($course)
                                <br><small>
                                    @if(!empty($course->slug))
                                        <a href="{{ route('course.details', ['course' => $course->slug]) }}" target="_blank" rel="noopener noreferrer">{{ $course->title }}</a>
                                    @else
                                        {{ $course->title }}
                                    @endif
                                </small>
                            @endif
                        </td>
                        <td class="col-timing" data-sort="{{ optional($asg?->available_from)->timestamp ?? 0 }}">
                            <span class="lms-timing-line"><strong>Exam Appointment Timing:</strong> {{ $asg ? $asg->appointmentWindowLabel() : '—' }}</span>
                            <span class="lms-timing-line"><strong>Exam Duration:</strong> {{ $fmtDuration($a->duration_minutes ?? null) }}</span>
                            <span class="lms-timing-line"><strong>Started:</strong> {{ ($asg && $att?->started_at) ? $asg->formatInTimezone($att->started_at) : (optional($att?->started_at)->format('d/m/y H:i') ?? '—') }}</span>
                            <span class="lms-timing-line"><strong>Ended:</strong> {{ ($asg && $att?->submitted_at) ? $asg->formatInTimezone($att->submitted_at) : (optional($att?->submitted_at)->format('d/m/y H:i') ?? '—') }}</span>
                            <span class="lms-timing-line"><strong>Duration spent:</strong> {{ $spend !== null ? $fmtDuration($spend) : '—' }}</span>
                            @if($asg)
                                <span class="lms-timing-line text-muted"><strong>Timezone:</strong> {{ $asg->timezoneLabel() }}</span>
                            @endif
                        </td>
                        <td class="col-assessor" data-sort="{{ $assessorName }}">
                            @if($assessorEmail && $assessorName !== '—')
                                <a href="mailto:{{ $assessorEmail }}">{{ $assessorName }}</a>
                            @else
                                {{ $assessorName }}
                            @endif
                        </td>
                        <td class="col-status" data-sort="{{ $statusSort }}">
                            @if($att)
                                <span class="lms-row-status {{ str_replace('lms-status-', 'lms-row-', $att->statusColorClass()) }}">{{ $att->statusLabel() }}</span>
                                @if($att->results_published && $att->score_raw !== null)
                                    <br>{{ lms_format_number($att->score_raw) }}/{{ lms_format_number($att->score_max) }}
                                    @if($att->percent() !== null) ({{ lms_format_number($att->percent()) }}%) @endif
                                @endif
                            @elseif($tab === 'missed')
                                <span class="lms-row-status lms-row-missed">Missed</span>
                            @elseif($tab === 'upcoming')
                                <span class="lms-row-status lms-row-upcoming">Upcoming</span>
                                <br><small class="text-muted">Opens {{ $asg?->windowStart()?->format('d M Y H:i') ?: 'soon' }}</small>
                            @else
                                <span class="lms-row-status lms-row-available">Available</span>
                            @endif
                        </td>
                        <td class="col-action">
                            @if($tab === 'available')
                                <a class="btn btn-xs btn-primary" href="{{ route('user.assessments.start', $a->id) }}">{{ ($canRetake || $attemptNo > 1) ? 'Start attempt '.$attemptNo : 'Start' }}</a>
                            @elseif($tab === 'in_progress' && $att)
                                <a class="btn btn-xs btn-primary" href="{{ route('user.assessments.take', $att->id) }}">Continue</a>
                            @elseif($att)
                                <a class="btn btn-xs btn-warning" href="{{ route('user.assessments.result', $att->id) }}">View Details</a>
                                @if($canRetake)
                                    <a class="btn btn-xs btn-primary" href="{{ route('user.assessments.start', $a->id) }}">Retake</a>
                                @endif
                            @elseif($tab === 'upcoming')
                                <span class="text-muted">Not open yet</span>
                            @elseif($tab === 'missed')
                                <span class="text-muted">Ended</span>
                            @endif
                            @if($canDownloadPdf)
                                <a class="btn btn-xs btn-info" href="{{ route('user.assessments.result-pdf', $att->id) }}" target="_blank">Download PDF</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">Nothing in this tab.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div>
</div>
@if($tab === 'upcoming' && $nextOpenAtMs)
@push('script')
<script>
(function () {
    var openAt = {{ (int) $nextOpenAtMs }};
    var goUrl = @json($availableTabUrl);
    var delay = openAt - Date.now();
    if (delay < 0) delay = 0;
    // Cap at 6h so a long-open page still refreshes reasonably.
    if (delay > 6 * 60 * 60 * 1000) return;
    setTimeout(function () {
        window.location.href = goUrl;
    }, delay + 400);
})();
</script>
@endpush
@endif
@endsection
