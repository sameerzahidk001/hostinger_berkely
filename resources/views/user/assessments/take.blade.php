@extends('user.layout.app')
@section('title', 'Taking: ' . ($attempt->assessment->title ?? 'Assessment'))
@push('style')
<style>
    /* Fixed take header — sticky fails when ancestors use overflow. */
    body.lms-take-mode,
    body.lms-take-mode #wrapper {
        overflow: visible !important;
    }
    body.lms-take-mode #page-wrapper {
        overflow: visible !important;
        padding-top: 0;
    }
    body.lms-take-mode .wrapper-content {
        overflow: visible !important;
        padding-top: 8px;
    }
    .lms-take-sticky {
        position: fixed;
        top: 0;
        left: 220px;
        right: 0;
        z-index: 1040;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
        margin: 0;
    }
    body.lms-take-mode #page-wrapper > .wrapper-content,
    body.lms-take-mode .wrapper.wrapper-content {
        margin-top: 150px;
    }
    @media (max-width: 768px) {
        .lms-take-sticky { left: 0; }
        body.lms-take-mode #page-wrapper > .wrapper-content,
        body.lms-take-mode .wrapper.wrapper-content { margin-top: 160px; }
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
    .lms-q-timer { color: #c0392b; font-weight: 700; margin-left: 12px; }
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
        <span class="lms-q-timer" id="lms-q-timer" style="display:none;">Question time: <strong id="lms-q-countdown">—</strong></span>
        <span id="lms-autosave-hint" class="text-muted" style="margin-left:12px;font-size:12px;"></span>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('user.assessments.save', $attempt->id) }}" id="attempt-form" data-result-url="{{ route('user.assessments.result', $attempt->id) }}">
        @csrf
        @forelse($questions as $i => $question)
            @php
                $ans = $answers->get($question->id);
                $qMins = (int) ($question->time_limit_minutes ?? 0);
                $subs = $question->hasSubQuestions() ? $question->subQuestions() : [];
            @endphp
            <div class="ibox lms-q-step {{ $i === 0 ? 'is-active' : '' }}" data-q-index="{{ $i }}" data-q-minutes="{{ $qMins }}">
                <div class="ibox-title">
                    <h5>Q{{ $i + 1 }}. {{ $question->typeLabel() }} ({{ lms_format_number($question->pivot->marks_override ?? $question->max_marks) }} marks)
                        @if($qMins > 0)
                            <small class="text-muted">· {{ $qMins }} min for this question</small>
                        @endif
                    </h5>
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
                    @elseif(count($subs) > 0)
                        @foreach($subs as $sub)
                            @php $sk = (string) ($sub['key'] ?? $loop->index); @endphp
                            <div class="form-group" style="border-left:3px solid #e7eaec;padding-left:12px;margin-bottom:16px;">
                                <label>
                                    <strong>{{ $sk }}.</strong>
                                    {{ $sub['text'] ?? '' }}
                                    <span class="text-muted">({{ lms_format_number($sub['percent'] ?? 0) }}% · {{ lms_format_number($sub['marks'] ?? 0) }} marks)</span>
                                </label>
                                <textarea
                                    name="answers[{{ $question->id }}][parts][{{ $sk }}]"
                                    class="form-control"
                                    rows="4"
                                    data-max="{{ $essayMax }}"
                                    maxlength="{{ $essayMax }}"
                                    placeholder="Your answer for {{ $sk }}…"
                                >{{ $ans?->partAnswer($sk) }}</textarea>
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
                                placeholder="Your answer…"
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
            <input type="hidden" name="submit" id="lms-submit-flag" value="0">
            <button type="submit" name="save_mode" value="0" class="btn btn-default">Save progress</button>
            <button type="submit" name="save_mode" value="1" class="btn btn-primary" id="lms-submit-btn">Submit</button>
        </div>
    </form>
</div>
@endsection
@push('script')
<script>
document.body.classList.add('lms-take-mode');
document.body.classList.add('lms-exam-lock');
document.body.setAttribute('data-lms-exam-lock', '1');
(function () {
    var steps = Array.prototype.slice.call(document.querySelectorAll('.lms-q-step'));
    var btnBack = document.getElementById('lms-q-back');
    var btnNext = document.getElementById('lms-q-next');
    var curLabel = document.getElementById('lms-q-current');
    var qTimerWrap = document.getElementById('lms-q-timer');
    var qCountdown = document.getElementById('lms-q-countdown');
    var form = document.getElementById('attempt-form');
    var idx = 0;
    var qTimerId = null;
    var qEndsAt = null;

    function clearQTimer() {
        if (qTimerId) { clearTimeout(qTimerId); qTimerId = null; }
        qEndsAt = null;
        if (qTimerWrap) qTimerWrap.style.display = 'none';
    }

    var saveInFlight = false;
    var autosaveHint = document.getElementById('lms-autosave-hint');

    function showAutosaveHint(text) {
        if (!autosaveHint) return;
        autosaveHint.textContent = text || '';
    }

    function silentSave(cb) {
        if (!form) { if (cb) cb(); return; }
        if (form.dataset.autoSubmitted) { if (cb) cb(); return; }
        if (saveInFlight) { if (cb) cb(); return; }
        saveInFlight = true;
        var fd = new FormData(form);
        fd.set('submit', '0');
        fd.set('save_mode', '0');
        fetch(form.action, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(function (res) {
            return res.json().catch(function () { return {}; });
        }).then(function (data) {
            if (data && data.redirect) {
                form.dataset.autoSubmitted = '1';
                window.location.href = data.redirect;
                return;
            }
            var now = new Date();
            var hh = String(now.getHours()).padStart(2, '0');
            var mm = String(now.getMinutes()).padStart(2, '0');
            showAutosaveHint('Saved ' + hh + ':' + mm);
        }).catch(function () {
            showAutosaveHint('Save retrying…');
        }).finally(function () {
            saveInFlight = false;
            if (cb) cb();
        });
    }

    function saveProgressThen(cb) {
        silentSave(cb);
    }

    setInterval(function () {
        if (form && !form.dataset.autoSubmitted) silentSave();
    }, 5 * 60 * 1000);

    if (form) {
        form.addEventListener('input', function () {
            showAutosaveHint('Saving every 5 min…');
        });
        form.addEventListener('change', function () {
            showAutosaveHint('Saving every 5 min…');
        });
        form.addEventListener('submit', function (e) {
            if (form.dataset.autoSubmitted) return;
            var flag = document.getElementById('lms-submit-flag');
            if (flag && flag.value === '1') return;
            e.preventDefault();
            silentSave();
        });
    }

    function tickQ() {
        if (!qEndsAt || !qCountdown) return;
        var ms = qEndsAt - Date.now();
        if (ms <= 0) {
            clearQTimer();
            qCountdown.textContent = '0s';
            if (form && form.dataset.autoSubmitted) return;
            // Auto-save and move next (or stay on last).
            saveProgressThen(function () {
                if (form && form.dataset.autoSubmitted) return;
                if (idx < steps.length - 1) {
                    showStep(idx + 1);
                }
            });
            return;
        }
        var s = Math.floor(ms / 1000);
        var m = Math.floor(s / 60);
        s = s % 60;
        qCountdown.textContent = (m > 0 ? (m + 'm ') : '') + s + 's';
        qTimerId = setTimeout(tickQ, 1000);
    }

    function startQTimer(stepEl) {
        clearQTimer();
        var mins = parseInt(stepEl.getAttribute('data-q-minutes') || '0', 10);
        if (!mins || mins < 1) return;
        qEndsAt = Date.now() + mins * 60 * 1000;
        if (qTimerWrap) qTimerWrap.style.display = 'inline';
        tickQ();
    }

    function showStep(i) {
        if (!steps.length) return;
        idx = Math.max(0, Math.min(i, steps.length - 1));
        steps.forEach(function (el, n) {
            el.classList.toggle('is-active', n === idx);
        });
        if (curLabel) curLabel.textContent = String(idx + 1);
        if (btnBack) btnBack.disabled = idx <= 0;
        if (btnNext) btnNext.disabled = idx >= steps.length - 1;
        startQTimer(steps[idx]);
        try {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (e) {}
    }

    if (btnBack) btnBack.addEventListener('click', function () {
        saveProgressThen(function () { showStep(idx - 1); });
    });
    if (btnNext) btnNext.addEventListener('click', function () {
        saveProgressThen(function () { showStep(idx + 1); });
    });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') silentSave();
    });
    showStep(0);
})();

(function () {
    var el = document.getElementById('expires_at');
    var form = document.getElementById('attempt-form');
    if (!form) return;
    var out = document.getElementById('countdown');
    var resultUrl = form.getAttribute('data-result-url') || '';
    var end = el ? new Date(el.textContent.trim()) : null;
    if (end && isNaN(end.getTime())) end = null;
    var submitBtn = document.getElementById('lms-submit-btn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function (e) {
            if (form.dataset.autoSubmitted) return;
            if (!confirm('Submit this attempt?')) {
                e.preventDefault();
                return;
            }
            var flag = document.getElementById('lms-submit-flag');
            if (flag) flag.value = '1';
        });
    }

    function goResult() {
        if (resultUrl) {
            window.location.href = resultUrl;
        }
    }

    function autoSubmit() {
        if (form.dataset.autoSubmitted) return;
        form.dataset.autoSubmitted = '1';
        if (out) out.textContent = 'Time is up — submitting…';
        var flag = document.getElementById('lms-submit-flag');
        if (flag) flag.value = '1';
        var fd = new FormData(form);
        fd.set('submit', '1');
        fd.set('save_mode', '1');
        Array.prototype.forEach.call(form.querySelectorAll('button'), function (n) {
            n.disabled = true;
        });
        fetch(form.action, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).then(function (res) {
            return res.json().catch(function () { return {}; });
        }).then(function (data) {
            if (data && data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            goResult();
        }).catch(function () {
            try {
                HTMLFormElement.prototype.submit.call(form);
            } catch (e) {
                goResult();
            }
        });
        setTimeout(goResult, 4000);
    }
    window.lmsExamAutoSubmit = autoSubmit;

    function tick() {
        if (!end) return;
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
        if (end && Date.now() >= end.getTime()) autoSubmit();
    });
    window.addEventListener('pagehide', function () {
        if (!end || Date.now() < end.getTime() || form.dataset.autoSubmitted) return;
        try {
            var fd = new FormData(form);
            fd.set('submit', '1');
            if (navigator.sendBeacon) navigator.sendBeacon(form.action, fd);
        } catch (e) {}
    });
})();
</script>
@endpush
