@extends('user.layout.app')
@section('title', 'Taking: ' . ($attempt->assessment->title ?? 'Assessment'))
@push('style')
<style>
    /* Sticky works inside #page-wrapper (that element scrolls). */
    body.lms-take-mode #page-wrapper {
        overflow-x: hidden;
    }
    body.lms-take-mode .wrapper-content {
        overflow-x: visible;
    }
    .lms-take-sticky {
        position: sticky;
        top: 0;
        z-index: 1040;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
        margin: 0 0 14px;
    }
    .lms-take-sticky .page-heading {
        margin: 0;
        padding: 12px 15px 8px;
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
        font-weight: 600;
    }
    .lms-take-sticky .lms-q-progress {
        padding: 8px 15px;
        font-size: 13px;
        color: #676a6c;
        background: #f8f8f8;
        border-bottom: 1px solid #e7eaec;
    }
    .lms-q-step { display: none; }
    .lms-q-step.is-active { display: block; }
    .lms-q-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e7eaec;
    }
    .lms-q-nav .lms-q-nav-spacer { flex: 1; }
    @media (max-width: 768px) {
        .lms-take-sticky .page-heading h2 { font-size: 17px; }
    }
</style>
@endpush
@section('content')
@include('admin.assessments.partials.limited-editor-assets')
@php
    $essayMax = 15000;
    $qTotal = $questions->count();
@endphp
<div class="lms-take-sticky">
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-12">
            <h2>{{ $attempt->assessment->title }}</h2>
            <ol class="breadcrumb">
                <li><a href="{{ route('user.assessments.index') }}">My Tests</a></li>
                <li class="active"><strong>In progress</strong></li>
            </ol>
        </div>
    </div>
    @if($attempt->expires_at)
        <div class="alert alert-warning lms-take-timer">
            Test Ends:
            <strong id="expires_at_label">{{ $attempt->expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</strong>
            <span class="hidden" id="expires_at">{{ $attempt->expires_at->toIso8601String() }}</span>
            · Remaining: <strong id="countdown">—</strong>
        </div>
    @endif
    <div class="lms-q-progress">
        Question <strong id="lms-q-current">1</strong> of <strong>{{ max(1, $qTotal) }}</strong>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('user.assessments.save', $attempt->id) }}" id="attempt-form">
        @csrf
        @forelse($questions as $i => $question)
            @php $ans = $answers->get($question->id); @endphp
            <div class="ibox lms-q-step {{ $i === 0 ? 'is-active' : '' }}" data-q-index="{{ $i }}">
                <div class="ibox-title">
                    <h5>Q{{ $i + 1 }}. {{ $question->typeLabel() }} ({{ lms_format_number($question->pivot->marks_override ?? $question->max_marks) }} marks)</h5>
                </div>
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
        @empty
            <div class="alert alert-warning">No questions on this test.</div>
        @endforelse

        <div class="lms-q-nav">
            <button type="button" class="btn btn-default" id="lms-q-back" disabled>Back</button>
            <button type="button" class="btn btn-primary" id="lms-q-next" @disabled($qTotal <= 1)>Next</button>
            <span class="lms-q-nav-spacer"></span>
            <button type="submit" name="submit" value="0" class="btn btn-default">Save progress</button>
            <button type="submit" name="submit" value="1" class="btn btn-primary" onclick="return confirm('Submit this attempt?');">Submit</button>
        </div>
    </form>
</div>
@endsection
@push('script')
<script>
document.body.classList.add('lms-take-mode');
(function () {
    var steps = Array.prototype.slice.call(document.querySelectorAll('.lms-q-step'));
    var btnBack = document.getElementById('lms-q-back');
    var btnNext = document.getElementById('lms-q-next');
    var curLabel = document.getElementById('lms-q-current');
    var idx = 0;

    function showStep(i) {
        if (!steps.length) return;
        idx = Math.max(0, Math.min(i, steps.length - 1));
        steps.forEach(function (el, n) {
            el.classList.toggle('is-active', n === idx);
        });
        if (curLabel) curLabel.textContent = String(idx + 1);
        if (btnBack) btnBack.disabled = idx <= 0;
        if (btnNext) btnNext.disabled = idx >= steps.length - 1;
        try {
            var sticky = document.querySelector('.lms-take-sticky');
            if (sticky) {
                sticky.scrollIntoView({ block: 'start', behavior: 'smooth' });
            }
        } catch (e) {}
    }

    if (btnBack) btnBack.addEventListener('click', function () { showStep(idx - 1); });
    if (btnNext) btnNext.addEventListener('click', function () { showStep(idx + 1); });
    showStep(0);
})();

(function () {
    var el = document.getElementById('expires_at');
    var form = document.getElementById('attempt-form');
    if (!el || !form) return;
    var end = new Date(el.textContent.trim());
    var out = document.getElementById('countdown');

    function autoSubmit() {
        if (form.dataset.autoSubmitted) return;
        form.dataset.autoSubmitted = '1';
        if (out) out.textContent = 'Time is up — submitting…';
        var input = form.querySelector('input[name="submit"][type="hidden"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'submit';
            form.appendChild(input);
        }
        input.value = '1';
        try {
            if (navigator.sendBeacon && document.visibilityState === 'hidden') {
                var fd = new FormData(form);
                fd.set('submit', '1');
                navigator.sendBeacon(form.action, fd);
                return;
            }
        } catch (e) {}
        form.submit();
    }

    function tick() {
        var ms = end.getTime() - Date.now();
        if (ms <= 0) {
            autoSubmit();
            return;
        }
        var s = Math.floor(ms / 1000);
        var h = Math.floor(s / 3600);
        s = s % 3600;
        var m = Math.floor(s / 60);
        s = s % 60;
        if (out) {
            out.textContent = (h > 0 ? (h + 'h ') : '') + m + 'm ' + s + 's';
        }
        setTimeout(tick, 1000);
    }
    tick();
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden' && Date.now() >= end.getTime()) {
            autoSubmit();
        }
    });
    window.addEventListener('pagehide', function () {
        if (Date.now() >= end.getTime()) {
            autoSubmit();
        }
    });
})();
</script>
@endpush
