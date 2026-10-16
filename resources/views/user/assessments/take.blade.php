@extends('user.layout.app')
@section('title', 'Taking: ' . ($attempt->assessment->title ?? 'Assessment'))
@push('style')
<style>
    body.lms-take-mode #page-wrapper > .row.border-bottom {
        position: sticky;
        top: 0;
        z-index: 1030;
        background: #fff;
    }
    .lms-take-sticky {
        position: sticky;
        top: 52px;
        z-index: 1020;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        margin-bottom: 12px;
    }
    .lms-take-sticky .page-heading {
        margin: 0;
        padding: 12px 15px 10px;
        background: #fff;
        border-bottom: 1px solid #e7eaec;
    }
    .lms-take-sticky .page-heading h2 {
        margin: 0 0 4px;
        font-size: 20px;
    }
    .lms-take-sticky .breadcrumb {
        margin-bottom: 0;
    }
    .lms-take-sticky .lms-take-timer {
        margin: 0;
        border-radius: 0;
        border-left: none;
        border-right: none;
        padding: 10px 15px;
    }
    @media (max-width: 768px) {
        .lms-take-sticky { top: 48px; }
    }
</style>
@endpush
@section('content')
@include('admin.assessments.partials.limited-editor-assets')
@php $essayMax = 15000; @endphp
<div class="lms-take-sticky">
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>{{ $attempt->assessment->title }}</h2>
            <ol class="breadcrumb">
                <li><a href="{{ route('user.assessments.index') }}">My Tests</a></li>
                <li class="active"><strong>In progress</strong></li>
            </ol>
        </div>
    </div>
    @if($attempt->expires_at)
        <div class="alert alert-warning lms-take-timer">
            Time limit ends: <strong id="expires_at">{{ $attempt->expires_at->toIso8601String() }}</strong>
            (<span id="countdown"></span>)
        </div>
    @endif
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('user.assessments.save', $attempt->id) }}" id="attempt-form">
        @csrf
        @foreach($questions as $i => $question)
            @php $ans = $answers->get($question->id); @endphp
            <div class="ibox">
                <div class="ibox-title"><h5>Q{{ $i + 1 }}. {{ $question->typeLabel() }} ({{ lms_format_number($question->pivot->marks_override ?? $question->max_marks) }} marks)</h5></div>
                <div class="ibox-content">
                    <div class="m-b-sm">{!! $question->stem !!}</div>
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
                        <div class="form-group">
                            <label>
                                Your answer
                                <span class="lms-char-hint">(max {{ number_format($essayMax) }} characters)</span>
                            </label>
                            <textarea
                                name="answers[{{ $question->id }}][text]"
                                class="form-control"
                                rows="6"
                                data-max="{{ $essayMax }}"
                                maxlength="{{ $essayMax }}"
                                placeholder="{{ in_array($question->type, ['simulation', 'case_study'], true) ? ($question->type === 'case_study' ? 'Your case study response…' : 'Your simulation response…') : 'Your essay answer…' }}"
                            >{{ $ans?->textAnswer() }}</textarea>
                        </div>
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
document.body.classList.add('lms-take-mode');
(function () {
    var el = document.getElementById('expires_at');
    if (!el) return;
    var end = new Date(el.textContent);
    var out = document.getElementById('countdown');
    function tick() {
        var ms = end - new Date();
        if (ms <= 0) {
            out.textContent = 'Time is up — submitting…';
            var form = document.getElementById('attempt-form');
            if (form && !form.dataset.autoSubmitted) {
                form.dataset.autoSubmitted = '1';
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'submit';
                input.value = '1';
                form.appendChild(input);
                form.submit();
            }
            return;
        }
        var s = Math.floor(ms / 1000);
        var m = Math.floor(s / 60);
        s = s % 60;
        out.textContent = m + 'm ' + s + 's left';
        setTimeout(tick, 1000);
    }
    tick();
})();
</script>
@endpush
