@extends('user.layout.app')
@section('title', 'Taking: ' . ($attempt->assessment->title ?? 'Assessment'))
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $attempt->assessment->title }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.assessments.index') }}">Assessments</a></li>
            <li class="active"><strong>In progress</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($attempt->expires_at)
        <div class="alert alert-warning">
            Time limit ends: <strong id="expires_at">{{ $attempt->expires_at->toIso8601String() }}</strong>
            (<span id="countdown"></span>)
        </div>
    @endif
    <form method="POST" action="{{ route('user.assessments.save', $attempt->id) }}" id="attempt-form">
        @csrf
        @foreach($questions as $i => $question)
            @php $ans = $answers->get($question->id); @endphp
            <div class="ibox">
                <div class="ibox-title"><h5>Q{{ $i + 1 }}. {{ $question->typeLabel() }} ({{ $question->pivot->marks_override ?? $question->max_marks }} marks)</h5></div>
                <div class="ibox-content">
                    <p>{!! nl2br(e($question->stem)) !!}</p>
                    @if($question->isMcq())
                        @foreach($question->options() as $opt)
                            <div class="radio">
                                <label>
                                    <input type="radio" name="answers[{{ $question->id }}][selected]" value="{{ $opt['key'] ?? $loop->index }}"
                                        @checked(($ans?->selectedKey() ?? '') === (string)($opt['key'] ?? $loop->index))>
                                    {{ $opt['text'] ?? '' }}
                                </label>
                            </div>
                        @endforeach
                    @else
                        <textarea name="answers[{{ $question->id }}][text]" class="form-control" rows="6" placeholder="{{ $question->type === 'simulation' ? 'Case / simulation response…' : 'Your essay answer…' }}">{{ $ans?->textAnswer() }}</textarea>
                    @endif
                </div>
            </div>
        @endforeach
        <button type="submit" name="submit" value="0" class="btn btn-default">Save progress</button>
        <button type="submit" name="submit" value="1" class="btn btn-primary" onclick="return confirm('Submit this attempt?');">Submit</button>
    </form>
</div>
@endsection
@push('script')
<script>
(function () {
    var el = document.getElementById('expires_at');
    if (!el) return;
    var end = new Date(el.textContent);
    var cd = document.getElementById('countdown');
    function tick() {
        var ms = end - new Date();
        if (ms <= 0) {
            cd.textContent = 'Time up — submitting…';
            var f = document.getElementById('attempt-form');
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = 'submit'; input.value = '1';
            f.appendChild(input);
            f.submit();
            return;
        }
        var s = Math.floor(ms / 1000);
        var m = Math.floor(s / 60); s = s % 60;
        cd.textContent = m + 'm ' + s + 's left';
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
@endpush
