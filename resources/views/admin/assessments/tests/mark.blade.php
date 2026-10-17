@extends(lms_portal_layout())
@section('title', 'Mark attempt')
@section('content')
@include('admin.assessments.partials.limited-editor-assets')
@php
    $feedbackMax = 2000;
    $overallMax = 5000;
@endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Mark: {{ $attempt->assessment->title ?? 'Attempt' }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">ALL TESTS</a></li>
            <li><a href="{{ route('admin.assessments.tests.attempts', $attempt->assessment_id) }}">Results</a></li>
            <li class="active"><strong>Mark</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @php
        $markAsg = \App\Models\LmsAssessmentAssignment::query()
            ->where('assessment_id', $attempt->assessment_id)
            ->orderBy('id')
            ->get()
            ->first(fn ($asg) => app(\App\Services\LmsAssessmentService::class)->attemptBelongsToAssignment($attempt, $asg));
        $markMeta = app(\App\Services\LmsAssessmentService::class)->sittingAttemptMeta(
            (int) $attempt->user_id,
            $attempt->assessment,
            $markAsg
        );
    @endphp
    <p><strong>{{ $attempt->user->name ?? '' }}</strong> · {{ $attempt->statusLabel() }}
        @if($attempt->score_raw !== null)
            · Score {{ lms_format_number($attempt->score_raw) }} / {{ lms_format_number($attempt->score_max) }}
        @endif
        @if($markAsg)
            · {{ $markAsg->displayCode() }}
        @endif
        · Attempt {{ $markMeta['number'] }} of {{ $markMeta['total'] }}
    </p>
    <form method="POST" action="{{ route('admin.assessments.tests.save-marks', $attempt->id) }}" id="markAttemptForm">
        @csrf
        <div class="ibox">
            <div class="ibox-title"><h5>Overall feedback to student *</h5></div>
            <div class="ibox-content">
                <div class="form-group m-b-none">
                    <label class="lms-char-hint">max {{ number_format($overallMax) }} characters</label>
                    <textarea
                        name="grader_feedback"
                        class="form-control lms-text-editor"
                        rows="4"
                        data-max="{{ $overallMax }}"
                        data-height="160"
                        data-rich="1"
                        required
                    >{{ old('grader_feedback', $attempt->grader_feedback) }}</textarea>
                    @error('grader_feedback')
                        <span class="help-block text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
        @foreach($attempt->assessment->questions as $question)
            @php $answer = $attempt->answers->firstWhere('question_id', $question->id); @endphp
            <div class="ibox">
                <div class="ibox-title"><h5>{{ $question->typeLabel() }} · {{ lms_format_number($question->max_marks) }} marks</h5></div>
                <div class="ibox-content">
                    <div class="m-b-sm">{!! $question->stem !!}</div>
                    @if($question->isMcq())
                        <p><strong>Selected:</strong> {{ $answer?->selectedKey() ?? '—' }}
                            @if($answer && $answer->is_correct !== null)
                                · {{ $answer->is_correct ? 'Correct' : 'Incorrect' }} (auto {{ lms_format_number($answer->auto_score) }})
                            @endif
                        </p>
                    @elseif($question->hasSubQuestions())
                        @foreach($question->subQuestions() as $sub)
                            @php
                                $sk = (string) ($sub['key'] ?? $loop->index);
                                $subMax = $sub['marks'] ?? 0;
                            @endphp
                            <p class="m-b-xs"><strong>{{ $sk }}.</strong> {{ $sub['text'] ?? '' }}
                                <span class="text-muted">({{ lms_format_number($sub['percent'] ?? 0) }}% of {{ lms_format_number($question->max_marks) }} = {{ lms_format_number($subMax) }} marks)</span></p>
                            <div class="well well-sm m-b-sm">{!! nl2br(e($answer?->partAnswer($sk) ?: '-')) !!}</div>
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label>Marks (max {{ lms_format_number($subMax) }})</label>
                                    <input type="number" step="0.1" min="0" max="{{ $subMax }}" name="part_score[{{ $question->id }}][{{ $sk }}]" class="form-control" value="{{ old('part_score.'.$question->id.'.'.$sk, $answer && $answer->partScore($sk) !== null ? lms_format_number($answer->partScore($sk)) : '') }}">
                                </div>
                                <div class="col-md-9 form-group">
                                    <label>
                                        Feedback
                                        <span class="lms-char-hint">(max {{ number_format($feedbackMax) }} characters)</span>
                                    </label>
                                    <textarea
                                        name="part_feedback[{{ $question->id }}][{{ $sk }}]"
                                        class="form-control lms-text-editor"
                                        rows="3"
                                        data-max="{{ $feedbackMax }}"
                                        data-height="120"
                                        data-rich="1"
                                    >{{ old('part_feedback.'.$question->id.'.'.$sk, $answer?->partFeedback($sk) ?: '') }}</textarea>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p><strong>Answer:</strong></p>
                        <div class="well well-sm">{!! nl2br(e($answer?->textAnswer() ?: '-')) !!}</div>
                        @if($answer)
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label>Marks (max {{ lms_format_number($answer->max_marks) }})</label>
                                    <input type="number" step="0.1" min="0" max="{{ $answer->max_marks }}" name="manual[{{ $answer->id }}]" class="form-control" value="{{ old('manual.'.$answer->id, $answer->manual_score !== null ? lms_format_number($answer->manual_score) : '') }}">
                                </div>
                                <div class="col-md-9 form-group">
                                    <label>
                                        Feedback
                                        <span class="lms-char-hint">(max {{ number_format($feedbackMax) }} characters)</span>
                                    </label>
                                    <textarea
                                        name="feedback[{{ $answer->id }}]"
                                        class="form-control lms-text-editor"
                                        rows="3"
                                        data-max="{{ $feedbackMax }}"
                                        data-height="120"
                                        data-rich="1"
                                    >{{ old('feedback.'.$answer->id, $answer->feedback) }}</textarea>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
        <button type="submit" class="btn btn-default" name="publish" value="0">Save marks</button>
        <button type="submit" class="btn btn-primary" name="publish" value="1">Save &amp; publish results</button>
        <a class="btn btn-success" href="{{ route('admin.assessments.tests.attempt-pdf', $attempt->id) }}" target="_blank"><i class="fa fa-file-pdf-o"></i> Download PDF</a>
        <a href="{{ route('admin.assessments.tests.attempts', $attempt->assessment_id) }}" class="btn btn-white">Back</a>
    </form>
</div>
@endsection
