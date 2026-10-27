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
        margin-top: 230px;
    }
    @media (max-width: 768px) {
        .lms-take-sticky { left: 0; }
        body.lms-take-mode #page-wrapper > .wrapper-content,
        body.lms-take-mode .wrapper.wrapper-content { margin-top: 210px; }
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
    .lms-q-step textarea.lms-written-answer {
        min-height: 18em;
    }
    .lms-written-answer {
        -webkit-user-select: text;
        user-select: text;
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
    .lms-submit-screen-btn {
        display: inline-block;
        background: #1ab394;
        color: #fff !important;
        border: 1px solid #18a689;
        border-radius: 3px;
        padding: 6px 16px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
    }
    .lms-submit-screen-btn:hover, .lms-submit-screen-btn:focus { background: #18a689; color: #fff !important; }
    .lms-pause-screen-btn {
        display: inline-block;
        background: #f8ac59;
        color: #1a1a1a !important;
        border: 1px solid #f7a54a;
        border-radius: 3px;
        padding: 6px 16px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
    }
    .lms-pause-screen-btn.is-paused {
        background: #1c84c6;
        border-color: #1a7bb9;
        color: #fff !important;
    }
    .lms-res-btns { position: relative; z-index: 1060; pointer-events: auto; }
    .lms-pause-screen-btn, .lms-submit-screen-btn, .lms-res-btn {
        position: relative;
        z-index: 1061;
        pointer-events: auto;
    }
    body.lms-intro-open .lms-q-step,
    body.lms-intro-open .lms-q-nav,
    body.lms-intro-open .lms-q-progress {
        display: none !important;
    }
    .lms-submit-modal {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 3000;
        background: rgba(8, 24, 48, .55);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .lms-submit-modal.is-open { display: flex; }
    .lms-submit-dialog {
        background: #fff;
        width: min(440px, 94vw);
        border-radius: 6px;
        box-shadow: 0 10px 36px rgba(0,0,0,.35);
        border: 1px solid #0d3b66;
        padding: 22px 22px 18px;
        color: #1f2d3d;
    }
    .lms-submit-dialog h3 {
        margin: 0 0 8px;
        font-size: 20px;
        font-weight: 700;
        color: #0d3b66;
    }
    .lms-submit-dialog p {
        margin: 0 0 16px;
        font-size: 14px;
        line-height: 1.45;
        color: #4a5568;
    }
    .lms-submit-dialog-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    .lms-submit-dialog-actions button {
        border: 0;
        border-radius: 4px;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .lms-submit-cancel { background: #eef1f4; color: #334155; }
    .lms-submit-go { background: #1ab394; color: #fff; }
    .lms-next-go { background: #1c84c6; color: #fff; }
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
    .lms-next-hint {
        color: #c0392b;
        font-size: 12px;
        font-weight: 600;
        max-width: 340px;
        line-height: 1.3;
    }
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
                'url' => route('user.assessments.resource', ['attemptId' => $attempt->id, 'kind' => 'reference', 'index' => $i]),
            ];
        })->values()->all(),
        'preseen' => collect($preFiles)->values()->map(function ($f, $i) use ($attempt) {
            return [
                'name' => $f['name'],
                'url' => route('user.assessments.resource', ['attemptId' => $attempt->id, 'kind' => 'preseen', 'index' => $i]),
            ];
        })->values()->all(),
    ];
@endphp
<div class="lms-take-sticky">
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-12">
            <div class="lms-take-head-row">
                <div class="lms-take-meta">
                    @php
                        $apptFrom = $assignment?->windowStart();
                        $apptUntil = $assignment?->appointmentEnd();
                        if ($assignment && $assignment->isSelfSitting()) {
                            $apptTiming = 'Self test — start anytime';
                        } elseif ($apptFrom && $apptUntil) {
                            $apptTiming = $apptFrom->format('d/m/Y H:i') . ' → ' . $apptUntil->format('d/m/Y H:i');
                        } elseif ($apptFrom) {
                            $apptTiming = $apptFrom->format('d/m/Y H:i');
                        } else {
                            $apptTiming = '—';
                        }
                    @endphp
                    <p class="lms-take-line"><strong>Test ID:</strong> {{ $attempt->assessment->displayCode() }} - <strong>Test Name:</strong> {{ $attempt->assessment->title }}</p>
                    <p class="lms-take-line"><strong>Course name:</strong> {{ $attempt->assessment->course->title ?? '—' }}</p>
                    <p class="lms-take-line"><strong>Appointment ID:</strong> {{ $assignment?->displayCode() ?? '—' }}</p>
                    <p class="lms-take-line"><strong>Test Appointment Timing:</strong> {{ $apptTiming }}</p>
                    <p class="lms-take-line" style="margin-bottom:0;"><strong>Time zone:</strong> {{ $assignment?->timezoneLabel() ?? (function_exists('viewer_timezone_label') ? viewer_timezone_label() : config('app.timezone')) }}</p>
                </div>
                <div class="lms-res-btns">
                    @if($refFiles !== [])
                        <a href="#" class="lms-res-btn js-open-res" data-kind="reference">Reference Material</a>
                    @endif
                    @if($preFiles !== [])
                        <a href="#" class="lms-res-btn js-open-res" data-kind="preseen">Pre-seen</a>
                    @endif
                    @if($attempt->assessment->allowsPause())
                        <button type="button" class="lms-pause-screen-btn {{ $attempt->isPaused() ? 'is-paused' : '' }}" id="lms-pause-btn">{{ $attempt->isPaused() ? 'Resume' : 'Pause' }}</button>
                    @endif
                    <button type="button" class="lms-submit-screen-btn" id="lms-submit-open">Submit</button>
                </div>
            </div>
        </div>
    </div>
    @if($attempt->expires_at)
        @php
            $examTzName = $assignment?->timezoneName()
                ?: (function_exists('viewer_timezone') ? viewer_timezone() : config('app.timezone'));
            $examTzLabel = $assignment?->timezoneLabel()
                ?: (function_exists('viewer_timezone_label') ? viewer_timezone_label($examTzName) : $examTzName);
            try {
                $examEndsLabel = $attempt->expires_at->copy()->timezone($examTzName)->format('d/m/Y H:i');
            } catch (\Throwable $e) {
                $examEndsLabel = $attempt->expires_at->format('d/m/Y H:i');
            }
        @endphp
        <div class="alert alert-warning lms-take-timer">
            Test Ends:
            <strong id="expires_at_label">{{ $examEndsLabel }} ({{ $examTzLabel }})</strong>
            <span class="hidden" id="expires_at">{{ $attempt->expires_at->toIso8601String() }}</span>
            · Remaining: <strong id="countdown">{{ $attempt->isPaused() ? 'Paused' : '—' }}</strong>
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
        @if(trim(strip_tags((string) ($attempt->assessment->description ?? ''))) !== '')
            <div class="ibox" id="lms-intro">
                <div class="ibox-title"><h5>Test instructions</h5></div>
                <div class="ibox-content">
                    <div class="lms-qa-question m-b-sm">{!! $attempt->assessment->description !!}</div>
                    <button type="button" class="btn btn-primary" id="lms-intro-continue">Continue to Q1</button>
                </div>
            </div>
        @endif
        @forelse($questions as $i => $question)
            @php
                $ans = $answers->get($question->id);
                $qMins = (int) ($question->time_limit_minutes ?? 0);
                $subs = $question->hasSubQuestions() ? $question->subQuestions() : [];
            @endphp
            <div class="ibox lms-q-step {{ $i === 0 ? 'is-active' : '' }}" data-q-index="{{ $i }}" data-q-minutes="{{ $qMins }}">
                <div class="ibox-title">
                    <h5>Q{{ $i + 1 }}. {{ $question->typeLabel() }}@if(! $attempt->assessment->isPractice() || $question->isMcq()) ({{ lms_format_number($question->pivot->marks_override ?? $question->max_marks) }} marks)@endif
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
                                    <strong>({{ chr(97 + $loop->index) }})</strong>
                                    {{ $sub['text'] ?? '' }}
                                    @if(! $attempt->assessment->isPractice())
                                        <span class="text-muted">({{ lms_format_number($sub['percent'] ?? 0) }}% · {{ lms_format_number($sub['marks'] ?? 0) }} marks)</span>
                                    @endif
                                </label>
                                <textarea
                                    name="answers[{{ $question->id }}][parts][{{ $sk }}]"
                                    class="form-control lms-written-answer"
                                    rows="12"
                                    data-max="{{ $essayMax }}"
                                    maxlength="{{ $essayMax }}"
                                    placeholder="Your answer…"
                                    autocomplete="off"
                                    spellcheck="true"
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
                                class="form-control lms-written-answer"
                                rows="18"
                                data-max="{{ $essayMax }}"
                                maxlength="{{ $essayMax }}"
                                placeholder="Your answer…"
                                autocomplete="off"
                                spellcheck="true"
                            >{{ $ans?->textAnswer() }}</textarea>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="alert alert-warning">No questions on this test.</div>
        @endforelse

        <div class="lms-q-nav">
            <button type="button" class="btn btn-primary" id="lms-q-next" @disabled($qTotal <= 1)>Next</button>
            <span class="lms-next-hint">If you press Next, you cannot come back to this question.</span>
            <span class="lms-q-nav-spacer"></span>
            <input type="hidden" name="submit" id="lms-submit-flag" value="0">
            <button type="submit" name="save_mode" value="0" class="btn btn-default">Save progress</button>
        </div>
    </form>
</div>
<div class="lms-submit-modal" id="lms-next-modal" aria-hidden="true">
    <div class="lms-submit-dialog" role="dialog" aria-modal="true" aria-labelledby="lms-next-title">
        <h3 id="lms-next-title">Go to the next question?</h3>
        <p>If you press Next, you cannot come back to the current question. Unused time on this question will not carry forward.</p>
        <div class="lms-submit-dialog-actions">
            <button type="button" class="lms-submit-cancel" id="lms-next-cancel">Cancel</button>
            <button type="button" class="lms-next-go" id="lms-next-confirm">Next</button>
        </div>
    </div>
</div>
<div class="lms-submit-modal" id="lms-submit-modal" aria-hidden="true">
    <div class="lms-submit-dialog" role="dialog" aria-modal="true" aria-labelledby="lms-submit-title">
        <h3 id="lms-submit-title">Submit this exam?</h3>
        <p>Answers will be sent for marking. You cannot change them after submit.</p>
        <div class="lms-submit-dialog-actions">
            <button type="button" class="lms-submit-cancel" id="lms-submit-cancel">Cancel</button>
            <button type="button" class="lms-submit-go" id="lms-submit-confirm">Submit</button>
        </div>
    </div>
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
    var btnNext = document.getElementById('lms-q-next');
    var nextHint = document.querySelector('.lms-next-hint');
    var curLabel = document.getElementById('lms-q-current');
    var qTimerWrap = document.getElementById('lms-q-timer');
    var qCountdown = document.getElementById('lms-q-countdown');
    var form = document.getElementById('attempt-form');
    var idx = 0;
    var qTimerId = null;
    var qEndsAt = null;
    var attemptId = @json($attempt->id);
    window.lmsExamPaused = @json($attempt->isPaused());
    var idxKey = 'lms-take-idx-' + attemptId;
    var nextModal = document.getElementById('lms-next-modal');
    var nextCancel = document.getElementById('lms-next-cancel');
    var nextConfirm = document.getElementById('lms-next-confirm');

    function qMinutes(i) {
        if (!steps[i]) return 0;
        return parseInt(steps[i].getAttribute('data-q-minutes') || '0', 10) || 0;
    }
    function endKey(i) {
        return 'lms-take-end-' + attemptId + '-' + i;
    }
    function readStore(key) {
        try { return sessionStorage.getItem(key); } catch (e) { return null; }
    }
    function writeStore(key, val) {
        try { sessionStorage.setItem(key, val); } catch (e) {}
    }

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

    function remainKey(i) {
        return 'lms-take-remain-' + attemptId + '-' + i;
    }
    function freezeQTimer() {
        if (!qEndsAt) return;
        var remain = Math.max(0, qEndsAt - Date.now());
        writeStore(remainKey(idx), String(remain));
        if (qTimerId) { clearTimeout(qTimerId); qTimerId = null; }
        if (qCountdown) qCountdown.textContent = 'Paused';
    }
    function thawQTimer() {
        var remain = parseInt(readStore(remainKey(idx)) || '0', 10);
        if (remain > 0) {
            qEndsAt = Date.now() + remain;
            writeStore(endKey(idx), String(qEndsAt));
            writeStore(remainKey(idx), '');
        }
        tickQ();
    }
    window.lmsFreezeQuestionTimer = freezeQTimer;
    window.lmsThawQuestionTimer = thawQTimer;

    function tickQ() {
        if (window.lmsExamPaused) {
            if (qCountdown) qCountdown.textContent = 'Paused';
            return;
        }
        if (!qEndsAt || !qCountdown) return;
        var ms = qEndsAt - Date.now();
        if (ms <= 0) {
            clearQTimer();
            qCountdown.textContent = '0s';
            if (form && form.dataset.autoSubmitted) return;
            if (idx >= steps.length - 1) {
                if (typeof window.lmsExamAutoSubmit === 'function') {
                    window.lmsExamAutoSubmit();
                }
                return;
            }
            saveProgressThen(function () {
                if (form && form.dataset.autoSubmitted) return;
                goToQuestion(idx + 1);
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
        var mins = qMinutes(stepIndex);
        if (!mins) {
            qEndsAt = null;
            return;
        }
        var remainStored = parseInt(readStore(remainKey(stepIndex)) || '0', 10);
        if (window.lmsExamPaused && remainStored > 0) {
            qEndsAt = Date.now() + remainStored;
            if (qTimerWrap) qTimerWrap.style.display = 'inline';
            freezeQTimer();
            return;
        }
        var stored = parseInt(readStore(endKey(stepIndex)) || '0', 10);
        if (stored > Date.now()) {
            qEndsAt = stored;
        } else if (stored && stored <= Date.now()) {
            qEndsAt = stored;
        } else {
            qEndsAt = Date.now() + mins * 60 * 1000;
            writeStore(endKey(stepIndex), String(qEndsAt));
        }
        if (qTimerWrap) qTimerWrap.style.display = 'inline';
        if (window.lmsExamPaused) {
            freezeQTimer();
            return;
        }
        tickQ();
    }

    function showStep(i) {
        if (!steps.length) return;
        idx = Math.max(0, Math.min(i, steps.length - 1));
        writeStore(idxKey, String(idx));
        steps.forEach(function (el, n) {
            el.classList.toggle('is-active', n === idx);
        });
        if (curLabel) curLabel.textContent = String(idx + 1);
        if (btnNext) btnNext.disabled = idx >= steps.length - 1;
        if (nextHint) nextHint.style.display = idx >= steps.length - 1 ? 'none' : '';
        startQTimer(idx);
        try {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch (e) {}
    }

    function goToQuestion(i) {
        showStep(i);
    }

    function resumeFromClock() {
        if (window.lmsExamPaused) {
            var pausedIdx = parseInt(readStore(idxKey) || '0', 10) || 0;
            showStep(pausedIdx);
            return;
        }
        var storedIdx = parseInt(readStore(idxKey) || '0', 10) || 0;
        storedIdx = Math.max(0, Math.min(storedIdx, Math.max(0, steps.length - 1)));
        while (storedIdx < steps.length) {
            var mins = qMinutes(storedIdx);
            var storedEnd = parseInt(readStore(endKey(storedIdx)) || '0', 10);
            if (mins > 0 && storedEnd && Date.now() >= storedEnd) {
                storedIdx++;
                writeStore(idxKey, String(storedIdx));
                continue;
            }
            break;
        }
        if (storedIdx >= steps.length) {
            showStep(steps.length - 1);
            if (typeof window.lmsExamAutoSubmit === 'function') {
                window.lmsExamAutoSubmit();
            }
            return;
        }
        showStep(storedIdx);
    }

    function openNextPopup() {
        if (!nextModal) {
            saveProgressThen(function () { goToQuestion(idx + 1); });
            return;
        }
        nextModal.classList.add('is-open');
        nextModal.setAttribute('aria-hidden', 'false');
        if (nextConfirm) {
            try { nextConfirm.focus(); } catch (e) {}
        }
    }
    function closeNextPopup() {
        if (!nextModal) return;
        nextModal.classList.remove('is-open');
        nextModal.setAttribute('aria-hidden', 'true');
    }

    if (btnNext) btnNext.addEventListener('click', function () {
        if (idx >= steps.length - 1) return;
        openNextPopup();
    });
    if (nextCancel) nextCancel.addEventListener('click', function (e) {
        e.preventDefault();
        closeNextPopup();
    });
    if (nextConfirm) nextConfirm.addEventListener('click', function (e) {
        e.preventDefault();
        closeNextPopup();
        saveProgressThen(function () { goToQuestion(idx + 1); });
    });
    if (nextModal) nextModal.addEventListener('click', function (e) {
        if (e.target === nextModal) closeNextPopup();
    });
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') silentSave();
        if (document.visibilityState === 'visible' && window.lmsExamClockReady) resumeFromClock();
    });
    var intro = document.getElementById('lms-intro');
    var introKey = 'lms-take-intro-' + attemptId;
    function finishIntro() {
        if (intro) intro.style.display = 'none';
        document.body.classList.remove('lms-intro-open');
        writeStore(introKey, '1');
        window.lmsExamClockReady = true;
        resumeFromClock();
        if (typeof window.lmsStartExamTick === 'function') window.lmsStartExamTick();
    }
    if (intro && readStore(introKey) !== '1') {
        document.body.classList.add('lms-intro-open');
        window.lmsExamClockReady = false;
        var introBtn = document.getElementById('lms-intro-continue');
        if (introBtn) introBtn.addEventListener('click', function (e) {
            e.preventDefault();
            finishIntro();
        });
    } else {
        if (intro) intro.style.display = 'none';
        window.lmsExamClockReady = true;
        window.addEventListener('pageshow', function () { resumeFromClock(); });
        setTimeout(resumeFromClock, 0);
    }
})();

(function () {
    var el = document.getElementById('expires_at');
    var form = document.getElementById('attempt-form');
    if (!form) return;
    var out = document.getElementById('countdown');
    var resultUrl = form.getAttribute('data-result-url') || '';
    var end = el ? new Date(el.textContent.trim()) : null;
    if (end && isNaN(end.getTime())) end = null;
    var submitOpen = document.getElementById('lms-submit-open');
    var submitModal = document.getElementById('lms-submit-modal');
    var submitCancel = document.getElementById('lms-submit-cancel');
    var submitConfirm = document.getElementById('lms-submit-confirm');

    function openSubmitPopup() {
        if (!submitModal || form.dataset.autoSubmitted) return;
        submitModal.classList.add('is-open');
        submitModal.setAttribute('aria-hidden', 'false');
        if (submitConfirm) {
            try { submitConfirm.focus(); } catch (e) {}
        }
    }
    function closeSubmitPopup() {
        if (!submitModal) return;
        submitModal.classList.remove('is-open');
        submitModal.setAttribute('aria-hidden', 'true');
    }
    function confirmSubmitExam() {
        if (form.dataset.autoSubmitted) return;
        closeSubmitPopup();
        var flag = document.getElementById('lms-submit-flag');
        if (flag) flag.value = '1';
        var saveMode = document.createElement('input');
        saveMode.type = 'hidden';
        saveMode.name = 'save_mode';
        saveMode.value = '1';
        form.appendChild(saveMode);
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            HTMLFormElement.prototype.submit.call(form);
        }
    }
    if (submitOpen) submitOpen.addEventListener('click', function (e) {
        e.preventDefault();
        openSubmitPopup();
    });
    if (submitCancel) submitCancel.addEventListener('click', function (e) {
        e.preventDefault();
        closeSubmitPopup();
    });
    if (submitConfirm) submitConfirm.addEventListener('click', function (e) {
        e.preventDefault();
        confirmSubmitExam();
    });
    if (submitModal) submitModal.addEventListener('click', function (e) {
        if (e.target === submitModal) closeSubmitPopup();
    });

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
        if (submitOpen) submitOpen.disabled = true;
        closeSubmitPopup();
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

    var pausedRemainMs = null;
    var examTickStarted = false;
    function tick() {
        if (!window.lmsExamClockReady) {
            setTimeout(tick, 400);
            return;
        }
        if (window.lmsExamPaused) {
            if (out) out.textContent = 'Paused';
            if (end && pausedRemainMs === null) {
                pausedRemainMs = Math.max(0, end.getTime() - Date.now());
            }
            setTimeout(tick, 1000);
            return;
        }
        if (pausedRemainMs !== null) {
            end = new Date(Date.now() + pausedRemainMs);
            pausedRemainMs = null;
            if (el && end) el.textContent = end.toISOString();
        }
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
    window.lmsSetExamEnd = function (iso, label) {
        if (iso) {
            end = new Date(iso);
            if (isNaN(end.getTime())) end = null;
        }
        var lab = document.getElementById('expires_at_label');
        if (lab && label) {
            var tzBits = lab.textContent.split(' (');
            lab.textContent = label + (tzBits.length > 1 ? ' (' + tzBits.slice(1).join(' (') : '');
        }
        if (el && iso) el.textContent = iso;
    };
    window.lmsStartExamTick = function () {
        if (examTickStarted) return;
        examTickStarted = true;
        tick();
    };
    if (window.lmsExamClockReady) {
        window.lmsStartExamTick();
    } else {
        setTimeout(function () {
            if (window.lmsExamClockReady) window.lmsStartExamTick();
        }, 400);
    }
    document.addEventListener('visibilitychange', function () {
        if (window.lmsExamPaused) return;
        if (end && Date.now() >= end.getTime()) autoSubmit();
    });
    window.addEventListener('pagehide', function () {
        if (window.lmsExamPaused) return;
        if (!end || Date.now() < end.getTime() || form.dataset.autoSubmitted) return;
        try {
            var fd = new FormData(form);
            fd.set('submit', '1');
            if (navigator.sendBeacon) navigator.sendBeacon(form.action, fd);
        } catch (e) {}
    });
})();
(function () {
    var btn = document.getElementById('lms-pause-btn');
    if (!btn) return;
    var token = document.querySelector('#attempt-form input[name="_token"]');
    var form = document.getElementById('attempt-form');
    var pauseUrl = form ? form.action : @json(route('user.assessments.save', $attempt->id));
    var busy = false;
    function setPausedUi(paused) {
        window.lmsExamPaused = !!paused;
        btn.textContent = paused ? 'Resume' : 'Pause';
        btn.classList.toggle('is-paused', !!paused);
        if (paused && typeof window.lmsFreezeQuestionTimer === 'function') {
            window.lmsFreezeQuestionTimer();
        }
        if (!paused && typeof window.lmsThawQuestionTimer === 'function') {
            window.lmsThawQuestionTimer();
        }
    }
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (busy) return;
        busy = true;
        btn.disabled = true;
        var goingPause = !window.lmsExamPaused;
        setPausedUi(goingPause);
        var fd = new FormData();
        if (token) fd.append('_token', token.value);
        fd.append('pause_action', goingPause ? 'pause' : 'resume');
        fd.append('submit', '0');
        fd.append('save_mode', '0');
        fetch(pauseUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.value : ''
            },
            body: fd
        }).then(function (res) { return res.json().catch(function () { return {}; }); })
        .then(function (data) {
            if (!data || !data.ok) {
                setPausedUi(!goingPause);
                alert((data && data.message) ? data.message : 'Could not pause or resume the timer. Please try again.');
                return;
            }
            setPausedUi(!!data.paused);
            if (!data.paused && data.expires_at && typeof window.lmsSetExamEnd === 'function') {
                window.lmsSetExamEnd(data.expires_at, data.expires_label);
            }
        }).catch(function () {
            setPausedUi(!goingPause);
            alert('Could not pause or resume the timer. Please try again.');
        }).finally(function () {
            busy = false;
            btn.disabled = false;
        });
    });
})();
(function () {
    var modal = document.getElementById('lms-res-modal');
    if (!modal) return;
    var files = @json($resModalFiles);
    var frame = document.getElementById('lms-res-frame');
    var kind = 'reference';
    function show(k) {
        kind = k;
        var list = files[k] || [];
        document.querySelectorAll('.js-res-kind').forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-kind') === k);
        });
        if (frame && list[0]) frame.src = list[0].url;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }
    document.querySelectorAll('.js-open-res').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            show(btn.getAttribute('data-kind') || 'reference');
        });
    });
    document.querySelectorAll('.js-res-kind').forEach(function (btn) {
        btn.addEventListener('click', function () {
            show(btn.getAttribute('data-kind') || 'reference');
        });
    });
    var closeBtn = document.getElementById('lms-res-close');
    if (closeBtn) closeBtn.addEventListener('click', function () {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        if (frame) frame.src = 'about:blank';
    });
})();

(function () {
    function isWritten(el) {
        return el && el.classList && el.classList.contains('lms-written-answer');
    }
    function block(e) {
        if (!isWritten(e.target)) return;
        e.preventDefault();
        return false;
    }
    ['copy', 'cut', 'paste', 'drop', 'dragover', 'contextmenu'].forEach(function (evt) {
        document.addEventListener(evt, block, true);
    });
    document.addEventListener('keydown', function (e) {
        if (!isWritten(e.target)) return;
        var key = (e.key || '').toLowerCase();
        if ((e.ctrlKey || e.metaKey) && (key === 'c' || key === 'v' || key === 'x' || key === 'insert')) {
            e.preventDefault();
        }
        if (e.shiftKey && key === 'insert') {
            e.preventDefault();
        }
    }, true);
})();
</script>
@endpush
