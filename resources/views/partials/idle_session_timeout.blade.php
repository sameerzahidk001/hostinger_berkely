{{-- Auto-logout after 15 minutes of no user activity --}}
@php
    // Hard-require 15-minute idle logout regardless of cached config on the server.
    $idleMinutes = 15;
    $idleMs = $idleMinutes * 60 * 1000;
@endphp
<script>
(function () {
    var idleLimitMs = {{ $idleMs }};
    var logoutUrl = @json($logoutUrl ?? url('/login'));
    var lastActivity = Date.now();
    var timer = null;

    function logoutNow() {
        window.location.href = logoutUrl;
    }

    function markActivity() {
        lastActivity = Date.now();
        if (timer) clearTimeout(timer);
        timer = setTimeout(checkIdle, idleLimitMs);
    }

    function checkIdle() {
        var idleFor = Date.now() - lastActivity;
        if (idleFor >= idleLimitMs) {
            logoutNow();
            return;
        }
        timer = setTimeout(checkIdle, Math.max(1000, idleLimitMs - idleFor));
    }

    ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
        document.addEventListener(evt, markActivity, { passive: true });
    });

    // Browsers throttle timers in background tabs; re-check when user returns.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            checkIdle();
        }
    });
    window.addEventListener('focus', checkIdle);

    // Backup poll every 30s in case long-running timers were throttled.
    setInterval(checkIdle, 30000);

    markActivity();
})();
</script>
