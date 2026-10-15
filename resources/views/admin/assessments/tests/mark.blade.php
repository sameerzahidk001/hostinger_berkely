@extends(lms_portal_layout())
@section('title', 'Mark attempt')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Mark: {{ $attempt->assessment->title ?? 'Attempt' }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.attempts', $attempt->assessment_id) }}">Attempts</a></li>
            <li class="active"><strong>Mark</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <p><strong>{{ $attempt->user->name ?? '' }}</strong> · {{ $attempt->statusLabel() }}
        @if($attempt->score_raw !== null)
            · Score {{ $attempt->score_raw }} / {{ $attempt->score_max }}
        @endif
    </p>
    <form method="POST" action="{{ route('admin.assessments.tests.save-marks', $attempt->id) }}">
        @csrf
        @foreach($attempt->assessment->questions as $question)
            @php $answer = $attempt->answers->firstWhere('question_id', $question->id); @endphp
            <div class="ibox">
                <div class="ibox-title"><h5>{{ $question->typeLabel() }} · {{ $question->max_marks }} marks</h5></div>
                <div class="ibox-content">
                    <p>{!! nl2br(e($question->stem)) !!}</p>
                    @if($question->isMcq())
                        <p><strong>Selected:</strong> {{ $answer?->selectedKey() ?? '—' }}
                            @if($answer && $answer->is_correct !== null)
                                · {{ $answer->is_correct ? 'Correct' : 'Incorrect' }} (auto {{ $answer->auto_score }})
                            @endif
                        </p>
                    @else
                        <p><strong>Answer:</strong></p>
                        <div class="well well-sm">{{ $answer?->textAnswer() ?: '—' }}</div>
                        @if($answer)
                            <div class="row">
                                <div class="col-md-3 form-group">
                                    <label>Manual score (max {{ $answer->max_marks }})</label>
                                    <input type="number" step="0.1" min="0" max="{{ $answer->max_marks }}" name="manual[{{ $answer->id }}]" class="form-control" value="{{ old('manual.'.$answer->id, $answer->manual_score) }}">
                                </div>
                                <div class="col-md-9 form-group">
                                    <label>Feedback</label>
                                    <input type="text" name="feedback[{{ $answer->id }}]" class="form-control" value="{{ old('feedback.'.$answer->id, $answer->feedback) }}">
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
        <div class="form-group">
            <label>Overall feedback to student</label>
            <textarea name="grader_feedback" class="form-control" rows="3">{{ old('grader_feedback', $attempt->grader_feedback) }}</textarea>
        </div>
        <button type="submit" class="btn btn-default" name="publish" value="0">Save marks</button>
        <button type="submit" class="btn btn-primary" name="publish" value="1">Save &amp; publish results</button>
        <a href="{{ route('admin.assessments.tests.attempts', $attempt->assessment_id) }}" class="btn btn-white">Back</a>
    </form>
</div>
@endsection
