@extends('user.layout.app')
@section('title', 'Result')
@section('content')
@include('admin.assessments.partials.qa-colors')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $attempt->assessment->title ?? 'Result' }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.assessments.index', ['tab' => 'results']) }}">My Tests</a></li>
            <li class="active"><strong>Result</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-warning">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Summary</h5></div>
        <div class="ibox-content">
            <p>Status: <strong class="{{ $attempt->statusColorClass() }}">{{ $attempt->statusLabel() }}</strong></p>
            @if(trim(strip_tags((string) ($attempt->assessment->description ?? ''))) !== '')
                <div class="m-t-sm m-b-sm">
                    <strong>Test instructions</strong>
                    <div class="well well-sm m-t-xs">{!! $attempt->assessment->description !!}</div>
                </div>
            @endif
            @if(!empty($appointmentId) || !empty($attemptLabel))
                <p class="text-muted" style="margin-bottom:6px;">
                    @if(!empty($appointmentId))Appointment ID: <strong>{{ $appointmentId }}</strong>@endif
                    @if(!empty($appointmentId) && !empty($attemptLabel)) · @endif
                    @if(!empty($attemptLabel)){{ $attemptLabel }}@endif
                </p>
            @endif
<style>
    .lms-status-completed { color: #f59e0b; }
.lms-status-marked { color: #1ab394; }
.lms-status-missed { color: #ed5565; }
.lms-status-in_progress { color: #f0ad4e; }
</style>
            @if($canSeeScore)
                <p style="font-size:22px;margin:8px 0;">
                    Score: <strong>{{ lms_format_number($attempt->score_raw) }} / {{ lms_format_number($attempt->score_max) }}</strong>
                    @if($attempt->percent() !== null) ({{ lms_format_number($attempt->percent()) }}%) @endif
                </p>
            @elseif(!empty($canSeeReview) && $attempt->assessment->hidesOverallMarks())
                {{-- Practice tests: no overall marks / percentage --}}
            @else
                <p class="text-muted">Submitted. Your score will appear here when Admin / Instructor publishes results.</p>
            @endif
            @if(!empty($canSeeReview) && $attempt->grader_feedback)
                <div class="m-t-sm lms-qa-feedback">
                    <strong>Overall feedback:</strong>
                    <div class="well well-sm m-t-xs">{!! $attempt->grader_feedback !!}</div>
                </div>
            @endif
            @php
                $startedLabel = isset($assignment) && $assignment && $attempt->started_at ? $assignment->formatInTimezone($attempt->started_at, 'd M Y H:i') : (function_exists('format_in_viewer_tz') ? format_in_viewer_tz($attempt->started_at) : (optional($attempt->started_at)->format('d M Y H:i') ?? '—'));
                $endedLabel = isset($assignment) && $assignment && $attempt->submitted_at ? $assignment->formatInTimezone($attempt->submitted_at, 'd M Y H:i') : (function_exists('format_in_viewer_tz') ? format_in_viewer_tz($attempt->submitted_at) : (optional($attempt->submitted_at)->format('d M Y H:i') ?? '—'));
            @endphp
            <p class="text-muted" style="margin-bottom:4px;">Started: {{ $startedLabel }}</p>
            <p class="text-muted" style="margin-bottom:4px;">Ended: {{ $endedLabel }}</p>
            @if($attempt->pauseSecondsTotal() > 0)
                <p class="text-muted" style="margin-bottom:4px;"><strong>Pause duration:</strong> {{ $attempt->pauseDurationLabel() }}</p>
                @foreach($attempt->pauseLog() as $pi => $plog)
                    @php
                        $pStart = !empty($plog['started_at']) ? \Carbon\Carbon::parse($plog['started_at']) : null;
                        $pEnd = !empty($plog['ended_at']) ? \Carbon\Carbon::parse($plog['ended_at']) : null;
                        $pStartL = ($assignment && $pStart) ? $assignment->formatInTimezone($pStart, 'd M Y H:i:s') : optional($pStart)->format('d M Y H:i:s');
                        $pEndL = ($assignment && $pEnd) ? $assignment->formatInTimezone($pEnd, 'd M Y H:i:s') : optional($pEnd)->format('d M Y H:i:s');
                    @endphp
                    <p class="text-muted" style="margin-bottom:2px;font-size:12px;">Pause {{ $pi + 1 }}: started {{ $pStartL ?? '—' }} · ended {{ $pEndL ?? '—' }}</p>
                @endforeach
            @endif
            <p class="text-muted">Submitted: {{ $endedLabel }}</p>
        </div>
    </div>

    @if(!empty($canSeeReview))
        @foreach($attempt->assessment->questions as $i => $question)
            @php
                $answer = $attempt->answers->firstWhere('question_id', $question->id);
                $showMarks = $question->isMcq() || ! $attempt->assessment->hidesOverallMarks();
            @endphp
            <div class="ibox">
                <div class="ibox-title"><h5>Q{{ $i + 1 }} · {{ $question->typeLabel() }}</h5></div>
                <div class="ibox-content">
                    <div class="m-b-sm lms-qa-question">{!! $question->stem !!}</div>
                    @if($question->isMcq())
                        <p class="lms-qa-answer">Your answer: {{ $answer?->selectedKey() ?? '—' }}
                            @if($answer && $answer->is_correct !== null)
                                · {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                            @endif
                            @if($showMarks)
                                · {{ lms_format_number($answer?->finalScore() ?? 0) }} / {{ lms_format_number($answer->max_marks ?? $question->max_marks) }}
                            @endif
                        </p>
                    @elseif($question->hasSubQuestions())
                        @foreach($question->subQuestions() as $sub)
                            @php $sk = (string) ($sub['key'] ?? $loop->index); @endphp
                            <p class="m-b-xs lms-qa-question"><strong>({{ chr(97 + $loop->index) }})</strong> {{ $sub['text'] ?? '' }}
                                @if($showMarks)
                                    <span class="text-muted">({{ lms_format_number($sub['percent'] ?? 0) }}% · {{ lms_format_number($sub['marks'] ?? 0) }} marks)</span>
                                @endif
                            </p>
                            <div class="well well-sm m-b-sm lms-qa-answer">{!! nl2br(e($answer?->partAnswer($sk) ?: '-')) !!}</div>
                            @if($showMarks)
                                <p>Marks: {{ lms_format_number($answer?->partScore($sk)) }} / {{ lms_format_number($sub['marks'] ?? 0) }}</p>
                            @endif
                            @if($answer && trim(strip_tags($answer->partFeedback($sk))) !== '')
                                <div class="m-b-sm lms-qa-feedback"><em>Feedback:</em><div class="well well-sm m-t-xs">{!! $answer->partFeedback($sk) !!}</div></div>
                            @endif
                        @endforeach
                        @if($showMarks)
                            <p><strong>Question total:</strong> {{ lms_format_number($answer?->finalScore()) }} / {{ lms_format_number($answer->max_marks ?? $question->max_marks) }}</p>
                        @endif
                    @else
                        <div class="well well-sm lms-qa-answer">{!! nl2br(e($answer?->textAnswer() ?: '-')) !!}</div>
                        @if($showMarks)
                            <p>Score: {{ lms_format_number($answer?->finalScore()) }} / {{ lms_format_number($answer->max_marks ?? $question->max_marks) }}</p>
                        @endif
                        @if($answer && $answer->feedback)
                            <div class="m-t-sm lms-qa-feedback"><em>Feedback:</em><div class="well well-sm m-t-xs">{!! $answer->feedback !!}</div></div>
                        @endif
                    @endif
                    @if(trim(strip_tags((string) ($question->explanation ?? ''))) !== '')
                        <div class="m-t-sm lms-qa-feedback">
                            <strong>Answer explanation:</strong>
                            <div class="well well-sm m-t-xs">{!! $question->explanation !!}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    @if(!empty($canSeeReview))
        <a class="btn btn-success" href="{{ route('user.assessments.result-pdf', $attempt->id) }}" target="_blank"><i class="fa fa-file-pdf-o"></i> Download PDF</a>
    @endif
    @if(!empty($canRetake))
        <a class="btn btn-primary" href="{{ route('user.assessments.start', array_filter(['id' => $attempt->assessment_id, 'assignment' => $assignment->id ?? null])) }}">Retake</a>
    @endif
    <a href="{{ route('user.assessments.index', ['tab' => 'results']) }}" class="btn btn-default">Back to results</a>
</div>
@endsection
