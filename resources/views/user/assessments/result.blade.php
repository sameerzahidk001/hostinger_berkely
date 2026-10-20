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
                @if($attempt->grader_feedback)
                    <div class="m-t-sm lms-qa-feedback">
                        <strong>Overall feedback:</strong>
                        <div class="well well-sm m-t-xs">{!! $attempt->grader_feedback !!}</div>
                    </div>
                @endif
            @else
                <p class="text-muted">Submitted. Your score will appear here when Admin / Instructor publishes results.</p>
            @endif
            <p class="text-muted">Submitted: {{ isset($assignment) && $assignment && $attempt->submitted_at ? $assignment->formatInTimezone($attempt->submitted_at, 'd M Y H:i') : (function_exists('format_in_viewer_tz') ? format_in_viewer_tz($attempt->submitted_at) : (optional($attempt->submitted_at)->format('d M Y H:i') ?? '—')) }}</p>
        </div>
    </div>

    @if($canSeeScore)
        @foreach($attempt->assessment->questions as $i => $question)
            @php $answer = $attempt->answers->firstWhere('question_id', $question->id); @endphp
            <div class="ibox">
                <div class="ibox-title"><h5>Q{{ $i + 1 }} · {{ $question->typeLabel() }}</h5></div>
                <div class="ibox-content">
                    <div class="m-b-sm lms-qa-question">{!! $question->stem !!}</div>
                    @if($question->isMcq())
                        <p class="lms-qa-answer">Your answer: {{ $answer?->selectedKey() ?? '—' }}
                            @if($answer && $answer->is_correct !== null)
                                · {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                            @endif
                            · {{ lms_format_number($answer?->finalScore() ?? 0) }} / {{ lms_format_number($answer->max_marks ?? $question->max_marks) }}
                        </p>
                    @elseif($question->hasSubQuestions())
                        @foreach($question->subQuestions() as $sub)
                            @php $sk = (string) ($sub['key'] ?? $loop->index); @endphp
                            <p class="m-b-xs lms-qa-question"><strong>{{ $sk }}.</strong> {{ $sub['text'] ?? '' }}
                                <span class="text-muted">({{ lms_format_number($sub['percent'] ?? 0) }}% · {{ lms_format_number($sub['marks'] ?? 0) }} marks)</span></p>
                            <div class="well well-sm m-b-sm lms-qa-answer">{!! nl2br(e($answer?->partAnswer($sk) ?: '-')) !!}</div>
                            <p>Marks: {{ lms_format_number($answer?->partScore($sk)) }} / {{ lms_format_number($sub['marks'] ?? 0) }}</p>
                            @if($answer && trim(strip_tags($answer->partFeedback($sk))) !== '')
                                <div class="m-b-sm lms-qa-feedback"><em>Feedback:</em><div class="well well-sm m-t-xs">{!! $answer->partFeedback($sk) !!}</div></div>
                            @endif
                        @endforeach
                        <p><strong>Question total:</strong> {{ lms_format_number($answer?->finalScore()) }} / {{ lms_format_number($answer->max_marks ?? $question->max_marks) }}</p>
                    @else
                        <div class="well well-sm lms-qa-answer">{!! nl2br(e($answer?->textAnswer() ?: '-')) !!}</div>
                        <p>Score: {{ lms_format_number($answer?->finalScore()) }} / {{ lms_format_number($answer->max_marks ?? $question->max_marks) }}</p>
                        @if($answer && $answer->feedback)
                            <div class="m-t-sm lms-qa-feedback"><em>Feedback:</em><div class="well well-sm m-t-xs">{!! $answer->feedback !!}</div></div>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    @if($canSeeScore)
        <a class="btn btn-success" href="{{ route('user.assessments.result-pdf', $attempt->id) }}" target="_blank"><i class="fa fa-file-pdf-o"></i> Download PDF</a>
    @endif
    @if(!empty($canRetake))
        <a class="btn btn-primary" href="{{ route('user.assessments.start', $attempt->assessment_id) }}">Retake</a>
    @endif
    <a href="{{ route('user.assessments.index', ['tab' => 'results']) }}" class="btn btn-default">Back to results</a>
</div>
@endsection
