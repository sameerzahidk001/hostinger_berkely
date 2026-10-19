{{-- Auto-logout after 15 minutes idle. Warn 2 minutes before so the user can resume or leave. --}}
@php
    $idleMinutes = 15;
    $warnSeconds = 120;
    $idleMs = $idleMinutes * 60 * 1000;
    $warnMs = $warnSeconds * 1000;
    $pingUrl = $pingUrl ?? (Auth::guard('admin')->check()
        ? route('admin.session.ping')
        : route('session.ping'));
@endphp
<style>
    #lms-idle-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 200000;
        background: rgba(20, 28, 40, .55);
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    #lms-idle-overlay.is-open { display: flex; }
    #lms-idle-dialog {
        width: 100%;
        max-width: 420px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 12px 40px rgba(0,0,0,.28);
        padding: 22px 22px 18px;
        font-family: inherit;
        color: #1f2d3d;
    }
    #lms-idle-dialog h3 {
        margin: 0 0 8px;
        font-size: 18px;
        font-weight: 700;
    }
    #lms-idle-dialog p {
        margin: 0 0 8px;
        font-size: 14px;
        line-height: 1.45;
        color: #4a5568;
    }
    #lms-idle-count {
        font-weight: 700;
        color: #c0392b;
        font-variant-numeric: tabular-nums;
    }
    #lms-idle-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 16px;
    }
    #lms-idle-actions button {
        border: 0;
        border-radius: 4px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    #lms-idle-leave { background: #eef1f4; color: #334155; }
    #lms-idle-resume { background: #1ab394; color: #fff; }
</style>
<div id="lms-idle-overlay" role="dialog" aria-modal="true" aria-labelledby="lms-idle-title" aria-hidden="true">
    <div id="lms-idle-dialog">
        <h3 id="lms-idle-title">Session about to end</h3>
        <p>You have been idle. Unsaved page edits will be lost if this session ends.</p>
        <p>Ends in <span id="lms-idle-count">2:00</span>.</p>
        <div id="lms-idle-actions">
            <button type="button" id="lms-idle-leave">Leave session</button>
            <button type="button" id="lms-idle-resume">Resume session</button>
        </div>
    </div>
</div>
<script>
(function () {
    var idleLimitMs = {{ $idleMs }};
    var warnMs = {{ $warnMs }};
    var logoutUrl = @json($logoutUrl ?? url('/login'));
    var pingUrl = @json($pingUrl);
    var lastActivity = Date.now();
    var timer = null;
    var ignoreActivity = false;
    var warningOpen = false;
    var countdownTimer = null;
    var ckBound = {};

    var overlay = document.getElementById('lms-idle-overlay');
    var countEl = document.getElementById('lms-idle-count');
    var resumeBtn = document.getElementById('lms-idle-resume');
    var leaveBtn = document.getElementById('lms-idle-leave');

    function logoutNow() {
        hideWarning();
        window.location.href = logoutUrl;
    }

    function isViewingMaterial() {
        var viewer = document.getElementById('smViewerBackdrop');
        if (viewer && viewer.classList.contains('open')) {
            return true;
        }
        var media = document.querySelectorAll('video, audio');
        for (var i = 0; i < media.length; i++) {
            if (!media[i].paused && !media[i].ended) {
                return true;
            }
        }
        return false;
    }

    function remainingMs() {
        return idleLimitMs - (Date.now() - lastActivity);
    }

    function formatRemain(ms) {
        var s = Math.max(0, Math.ceil(ms / 1000));
        var m = Math.floor(s / 60);
        var r = s % 60;
        return m + ':' + (r < 10 ? '0' : '') + r;
    }

    function hideWarning() {
        warningOpen = false;
        if (overlay) {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
        }
        if (countdownTimer) {
            clearInterval(countdownTimer);
            countdownTimer = null;
        }
    }

    function showWarning() {
        if (warningOpen || !overlay) return;
        warningOpen = true;
        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
        pingSession();
        if (countEl) countEl.textContent = formatRemain(remainingMs());
        if (countdownTimer) clearInterval(countdownTimer);
        countdownTimer = setInterval(function () {
            var left = remainingMs();
            if (countEl) countEl.textContent = formatRemain(left);
            if (left <= 0) {
                logoutNow();
            }
        }, 250);
        if (resumeBtn) {
            try { resumeBtn.focus(); } catch (e) {}
        }
    }

    function resumeSession() {
        hideWarning();
        lastActivity = Date.now();
        pingSession();
        scheduleCheck();
    }

    function markActivity() {
        if (ignoreActivity || warningOpen) return;
        lastActivity = Date.now();
        scheduleCheck();
    }

    function scheduleCheck() {
        if (timer) clearTimeout(timer);
        var left = remainingMs();
        if (left <= 0) {
            timer = setTimeout(checkIdle, 250);
            return;
        }
        var untilWarn = left - warnMs;
        timer = setTimeout(checkIdle, Math.max(250, untilWarn > 0 ? untilWarn : left));
    }

    function checkIdle() {
        if (isViewingMaterial()) {
            lastActivity = Date.now();
            hideWarning();
            pingSession();
            scheduleCheck();
            return;
        }
        var left = remainingMs();
        if (left <= 0) {
            logoutNow();
            return;
        }
        if (left <= warnMs) {
            showWarning();
        }
        scheduleCheck();
    }

    function pingSession() {
        if (!pingUrl) return;
        fetch(pingUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        }).catch(function () {});
    }

    function onUserReturn() {
        if (warningOpen) return;
        ignoreActivity = true;
        checkIdle();
        ignoreActivity = false;
        if (isViewingMaterial()) {
            markActivity();
        }
    }

    function bindCkeditor() {
        if (!window.CKEDITOR || !CKEDITOR.instances) return;
        Object.keys(CKEDITOR.instances).forEach(function (name) {
            if (ckBound[name]) return;
            var inst = CKEDITOR.instances[name];
            if (!inst) return;
            ckBound[name] = true;
            inst.on('key', markActivity);
            inst.on('change', markActivity);
            inst.on('focus', markActivity);
        });
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click', 'input'].forEach(function (evt) {
        document.addEventListener(evt, markActivity, { passive: true, capture: true });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            onUserReturn();
        }
    });
    window.addEventListener('focus', onUserReturn);

    if (resumeBtn) resumeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        resumeSession();
    });
    if (leaveBtn) leaveBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        logoutNow();
    });

    setInterval(function () {
        bindCkeditor();
        if (isViewingMaterial()) {
            lastActivity = Date.now();
            hideWarning();
            pingSession();
        }
        checkIdle();
    }, 30000);

    bindCkeditor();
    if (window.CKEDITOR) {
        CKEDITOR.on('instanceReady', bindCkeditor);
    }
    markActivity();
})();
</script>
