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
        background: #0d3b66;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .18);
        margin: 0;
        color: #fff;
    }
    body.lms-take-mode #page-wrapper > .wrapper-content,
    body.lms-take-mode .wrapper.wrapper-content {
        margin-top: 190px;
    }
    @media (max-width: 768px) {
        .lms-take-sticky { left: 0; }
        body.lms-take-mode #page-wrapper > .wrapper-content,
        body.lms-take-mode .wrapper.wrapper-content { margin-top: 160px; }
    }
    .lms-take-sticky .page-heading {
        margin: 0;
        padding: 12px 15px 8px;
        background: #0d3b66;
        border-bottom: 1px solid #16497a;
        color: #fff;
    }
    .lms-take-sticky .page-heading h2 {
        margin: 0 0 4px;
        font-size: 20px;
        color: #fff;
    }
    .lms-take-sticky .breadcrumb {
        margin-bottom: 0;
        background: transparent;
    }
    .lms-take-sticky .breadcrumb > li,
    .lms-take-sticky .breadcrumb > li a,
    .lms-take-sticky .breadcrumb > li.active {
        color: #d7e6f5;
    }
    .lms-take-sticky .lms-take-timer {
        margin: 0;
        border-radius: 0;
        border: none;
        padding: 10px 15px;
        font-weight: 600;
        background: #123f6b;
        color: #fff;
    }
    .lms-take-sticky .lms-q-progress {
        padding: 8px 15px;
        font-size: 13px;
        color: #0d3b66;
        background: #fff;
        border-bottom: 1px solid #d9e4f0;
    }
    .lms-take-head-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
    }
    .lms-take-meta { flex: 1; min-width: 0; }
    .lms-take-line {
        margin: 0 0 4px;
        font-size: 13px;
        line-height: 1.4;
        color: #fff;
    }
    .lms-take-line strong { color: #fff; }
    .lms-res-btns { margin: 0; display: flex; flex-wrap: wrap; gap: 8px; justify-content: flex-end; flex-shrink: 0; }
    .lms-q-step textarea.form-control,
    .lms-q-step textarea.form-control:focus {
        color: #0056b3 !important;
    }
    .lms-q-step .ibox-content > .m-b-sm,
    .lms-q-step .ibox-content > .m-b-sm * {
        color: #000;
    }
    .lms-res-btn {
        display: inline-block;
        background: #f0ad4e;
        color: #1a1a1a !important;
        border: 1px solid #eea236;
        border-radius: 3px;
        padding: 6px 14px;
        font-weight: 700;
        font-size: 13px;
        text-decoration: none !important;
    }
    .lms-res-btn:hover, .lms-res-btn:focus { background: #ec971f; color: #1a1a1a !important; border-color: #d58512; }
    .lms-res-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 2000;
        background: rgba(8, 24, 48, .55);
    }
    .lms-res-modal.is-open { display: flex; align-items: stretch; justify-content: center; padding: 24px; }
    .lms-res-dialog {
        background: #fff;
        width: min(1100px, 96vw);
        height: min(90vh, 860px);
        display: flex;
        flex-direction: column;
        box-shadow: 0 8px 32px rgba(0,0,0,.35);
        border: 1px solid #0d3b66;
    }
    .lms-res-dialog-head {
        background: #0d3b66;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 10px;
        min-height: 46px;
    }
    .lms-res-tab {
        background: #1d4e89;
        color: #fff;
        border: 0;
        padding: 8px 16px;
        font-weight: 700;
        cursor: pointer;
    }
    .lms-res-tab.is-active { background: #fff; color: #0d3b66; }
    .lms-res-close {
        margin-left: auto;
        background: transparent;
        border: 0;
        color: #fff;
        font-size: 22px;
        line-height: 1;
        cursor: pointer;
        padding: 4px 8px;
    }
    .lms-res-dialog-body { flex: 1; background: #fff; min-height: 0; }
    .lms-res-dialog-body iframe, .lms-res-dialog-body img {
        width: 100%;
        height: 100%;
        border: 0;
        background: #fff;
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
    $refFiles = $attempt->assessment->resourceFiles('reference');
    $preFiles = $attempt->assessment->resourceFiles('preseen');
    $assignment = $assignment ?? null;
    $resModalFiles = [
        'reference' => collect($refFiles)->values()->map(function ($f, $i) use ($attempt) {
            return [
                'name' => $f['name'],
                'url' => route('user.assessments.resource', ['attemptId' => $attempt->id, 'kind' => 'reference', 'index' => $i, 'embed' => 1]),
                'open' => route('user.assessments.resource', ['attemptId' => $attempt->id, 'kind' => 'reference', 'index' => $i]),
            ];
        })->values()->all(),
        'preseen' => collect($preFiles)->values()->map(function ($f, $i) use ($attempt) {
            return [
                'name' => $f['name'],
                'url' => route('user.assessments.resource', ['attemptId' => $attempt->id, 'kind' => 'preseen', 'index' => $i, 'embed' => 1]),
                'open' => route('user.assessments.resource', ['attemptId' => $attempt->id, 'kind' => 'preseen', 'index' => $i]),
            ];
        })->values()->all(),
    ];
@endphp
<div class="lms-take-sticky">
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-12">
            <div class="lms-take-head-row">
                <div class="lms-take-meta">
                    <p class="lms-take-line"><strong>Test ID:</strong> {{ $attempt->assessment->displayCode() }} - <strong>Test Name:</strong> {{ $attempt->assessment->title }}</p>
                    <p class="lms-take-line"><strong>Course name:</strong> {{ $attempt->assessment->course->title ?? '—' }}</p>
                    <p class="lms-take-line" style="margin-bottom:0;"><strong>Appointment ID:</strong> {{ $assignment?->displayCode() ?? '—' }} · {{ $assignment ? $assignment->appointmentWindowLabel('d/m/Y H:i') : '—' }}</p>
                </div>
                @if($refFiles !== [] || $preFiles !== [])
                    <div class="lms-res-btns">
                        @if($refFiles !== [])
                            <a href="#" class="lms-res-btn js-open-res" data-kind="reference">Reference Material</a>
                        @endif
                        @if($preFiles !== [])
                            <a href="#" class="lms-res-btn js-open-res" data-kind="preseen">Pre-seen</a>
                        @endif
                    </div>
                @endif
            </div>
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
@if($refFiles !== [] || $preFiles !== [])
<div class="lms-res-modal" id="lms-res-modal" aria-hidden="true">
    <div class="lms-res-dialog">
        <div class="lms-res-dialog-head">
            @if($refFiles !== [])
                <button type="button" class="lms-res-tab js-res-kind" data-kind="reference">Reference Material</button>
            @endif
            @if($preFiles !== [])
                <button type="button" class="lms-res-tab js-res-kind" data-kind="preseen">Pre-seen</button>
            @endif
            @if(count($refFiles) > 1 || count($preFiles) > 1)
                <select id="lms-res-file" class="form-control input-sm" style="width:auto;max-width:280px;color:#0d3b66;"></select>
            @endif
            <a href="#" class="lms-res-btn" id="lms-res-open" target="_blank" rel="noopener" style="margin-left:8px;">Open file</a>
            <button type="button" class="lms-res-close" id="lms-res-close" title="Close">&times;</button>
        </div>
        <div class="lms-res-dialog-body">
            <iframe id="lms-res-frame" title="Resource"></iframe>
        </div>
    </div>
</div>
@endif
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
    var startedMs = Date.parse(@json(optional($attempt->started_at)->toIso8601String())) || Date.now();
    var qEnds = [];
    (function buildEnds() {
        var cursor = startedMs;
        steps.forEach(function (el) {
            var mins = parseInt(el.getAttribute('data-q-minutes') || '0', 10);
            if (!mins || mins < 1) {
                qEnds.push(null);
                return;
            }
            cursor += mins * 60 * 1000;
            qEnds.push(cursor);
        });
    })();

    function clearQTimer() {
        if (qTimerId) { clearTimeout(qTimerId); qTimerId = null; }
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
            if (idx >= steps.length - 1) return;
            saveProgressThen(function () {
                if (form && form.dataset.autoSubmitted) return;
                resumeFromClock();
            });
            return;
        }
        var s = Math.floor(ms / 1000);
        var m = Math.floor(s / 60);
        s = s % 60;
        qCountdown.textContent = (m > 0 ? (m + 'm ') : '') + s + 's';
        qTimerId = setTimeout(tickQ, 1000);
    }

    function startQTimer(stepIndex) {
        clearQTimer();
        qEndsAt = qEnds[stepIndex] || null;
        if (!qEndsAt) return;
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
        startQTimer(idx);
        try {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (e) {}
    }

    function resumeFromClock() {
        var i = 0;
        while (i < steps.length) {
            if (!qEnds[i] || Date.now() < qEnds[i]) {
                showStep(i);
                return;
            }
            i++;
        }
        showStep(steps.length - 1);
    }

    if (btnBack) btnBack.addEventListener('click', function () {
        saveProgressThen(function () { showStep(idx - 1); });
    });
    if (btnNext) btnNext.addEventListener('click', function () {
        saveProgressThen(function () { showStep(idx + 1); });
    });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') silentSave();
        if (document.visibilityState === 'visible') resumeFromClock();
    });
    window.addEventListener('pageshow', function () { resumeFromClock(); });
    resumeFromClock();
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
(function () {
    var modal = document.getElementById('lms-res-modal');
    if (!modal) return;
    var files = @json($resModalFiles);
    var frame = document.getElementById('lms-res-frame');
    var fileSel = document.getElementById('lms-res-file');
    var openBtn = document.getElementById('lms-res-open');
    var kind = 'reference';
    function show(k, idx) {
        kind = k;
        idx = idx || 0;
        var list = files[k] || [];
        document.querySelectorAll('.js-res-kind').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-kind') === k);
        });
        if (fileSel) {
            fileSel.innerHTML = '';
            list.forEach(function (f, i) {
                var o = document.createElement('option');
                o.value = String(i);
                o.textContent = f.name;
                if (i === idx) o.selected = true;
                fileSel.appendChild(o);
            });
            fileSel.style.display = list.length > 1 ? '' : 'none';
        }
        if (list[idx]) {
            if (frame) frame.src = list[idx].url;
            if (openBtn) openBtn.href = list[idx].open || list[idx].url;
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }
    document.querySelectorAll('.js-open-res').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            show(btn.getAttribute('data-kind') || 'reference', 0);
        });
    });
    document.querySelectorAll('.js-res-kind').forEach(function (btn) {
        btn.addEventListener('click', function () {
            show(btn.getAttribute('data-kind') || 'reference', 0);
        });
    });
    if (fileSel) {
        fileSel.addEventListener('change', function () {
            show(kind, parseInt(fileSel.value, 10) || 0);
        });
    }
    var closeBtn = document.getElementById('lms-res-close');
    if (closeBtn) closeBtn.addEventListener('click', function () {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (frame) frame.src = 'about:blank';
    });
})();
</script>
@endpush
