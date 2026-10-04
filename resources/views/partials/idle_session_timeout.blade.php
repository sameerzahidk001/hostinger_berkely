{{-- Keep the session alive while the user is viewing PDFs, videos, or other materials. --}}
@php
    $idleMinutes = 120;
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
        lastActivity = Date.now();
        if (timer) clearTimeout(timer);
        timer = setTimeout(checkIdle, idleLimitMs);
    }

    function checkIdle() {
        if (isViewingMaterial()) {
            markActivity();
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

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
        document.addEventListener(evt, markActivity, { passive: true });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            if (isViewingMaterial()) {
                markActivity();
            }
            checkIdle();
        }
    });
    window.addEventListener('focus', checkIdle);

    setInterval(function () {
        if (isViewingMaterial()) {
            markActivity();
        }
        pingSession();
        checkIdle();
    }, 60000);

    markActivity();
    pingSession();
})();
</script>
