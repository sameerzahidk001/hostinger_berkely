{{-- Auto-logout after SESSION_LIFETIME minutes of no user activity --}}
@php
    $idleMinutes = (int) config('session.lifetime', 15);
    $idleMs = max(1, $idleMinutes) * 60 * 1000;
@endphp
<script>
(function () {
    var idleLimitMs = {{ $idleMs }};
    var logoutUrl = @json($logoutUrl ?? url('/login'));
    var timer = null;

    function logoutNow() {
        window.location.href = logoutUrl;
    }

    function resetIdleTimer() {
        if (timer) clearTimeout(timer);
        timer = setTimeout(logoutNow, idleLimitMs);
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
        document.addEventListener(evt, resetIdleTimer, { passive: true });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) resetIdleTimer();
    });

    resetIdleTimer();
})();
</script>
