{{-- Auto-logout after 15 minutes idle. Stay signed in only while a PDF/video/file is actually open. --}}
@php
    $idleMinutes = 15;
    $idleMs = $idleMinutes * 60 * 1000;
    $pingUrl = $pingUrl ?? (Auth::guard('admin')->check()
        ? route('admin.session.ping')
        : route('session.ping'));
@endphp
<script>
(function () {
    var idleLimitMs = {{ $idleMs }};
    var logoutUrl = @json($logoutUrl ?? url('/login'));
    var pingUrl = @json($pingUrl);
    var lastActivity = Date.now();
    var timer = null;
    var ignoreActivity = false;

    function logoutNow() {
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

    function markActivity() {
        if (ignoreActivity) return;
        lastActivity = Date.now();
        if (timer) clearTimeout(timer);
        timer = setTimeout(checkIdle, idleLimitMs);
    }

    function checkIdle() {
        if (isViewingMaterial()) {
            lastActivity = Date.now();
            if (timer) clearTimeout(timer);
            timer = setTimeout(checkIdle, idleLimitMs);
            pingSession();
            return;
        }
        var idleFor = Date.now() - lastActivity;
        if (idleFor >= idleLimitMs) {
            logoutNow();
            return;
        }
        timer = setTimeout(checkIdle, Math.max(1000, idleLimitMs - idleFor));
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
        ignoreActivity = true;
        checkIdle();
        ignoreActivity = false;
        if (isViewingMaterial()) {
            markActivity();
        }
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
        document.addEventListener(evt, markActivity, { passive: true });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            onUserReturn();
        }
    });
    window.addEventListener('focus', onUserReturn);

    setInterval(function () {
        if (isViewingMaterial()) {
            lastActivity = Date.now();
            pingSession();
        }
        checkIdle();
    }, 30000);

    markActivity();
})();
</script>
