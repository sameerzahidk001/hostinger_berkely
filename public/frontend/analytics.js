document.addEventListener('DOMContentLoaded', function () {
    var path = (window.location.pathname || '/').toLowerCase();
    var hidden = ['/admin', '/user', '/login', '/register', '/logout', '/password', '/captcha', '/analytics'];
    if (hidden.some(function (p) { return path === p || path.indexOf(p + '/') === 0; })) {
        return;
    }

    var tokenEl = document.querySelector('meta[name="csrf-token"]');
    if (!tokenEl) {
        return;
    }

    var csrf = tokenEl.getAttribute('content');
    var PING_MS = 60000;
    var CLICK_MIN_MS = 8000;
    var SCROLL_MILESTONES = [25, 50, 75, 100];
    var scrollSent = {};
    var lastClickAt = 0;
    var lastPingAt = Date.now();
    var behaviorInFlight = false;

    function ping() {
        if (document.visibilityState && document.visibilityState !== 'visible') {
            lastPingAt = Date.now();
            return;
        }
        var seconds = Math.round((Date.now() - lastPingAt) / 1000);
        lastPingAt = Date.now();
        if (seconds < 1) {
            return;
        }
        fetch('/analytics/ping', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                url: window.location.href,
                seconds: Math.min(90, seconds)
            }),
            keepalive: true
        }).catch(function () {});
    }

    setInterval(ping, PING_MS);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            ping();
        }
    });
    window.addEventListener('pagehide', ping);

    function sessionId() {
        var existing = sessionStorage.getItem('session_id');
        if (existing) {
            return existing;
        }
        var id = 'sess_' + Math.random().toString(36).substr(2, 9);
        sessionStorage.setItem('session_id', id);
        return id;
    }

    function sendBehaviorData(eventType, data) {
        if (behaviorInFlight) {
            return;
        }
        behaviorInFlight = true;
        fetch('/user-behavior', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                event_type: eventType,
                data: data,
                url: window.location.href,
                session_id: sessionId()
            }),
            keepalive: true
        }).catch(function () {}).finally(function () {
            behaviorInFlight = false;
        });
    }

    window.addEventListener('scroll', function () {
        var totalHeight = document.body.scrollHeight - window.innerHeight;
        if (totalHeight <= 0) {
            return;
        }
        var depth = Math.min(100, Math.round((window.scrollY / totalHeight) * 100));
        for (var i = 0; i < SCROLL_MILESTONES.length; i++) {
            var mark = SCROLL_MILESTONES[i];
            if (depth >= mark && !scrollSent[mark]) {
                scrollSent[mark] = true;
                sendBehaviorData('scroll', { depth: mark });
                break;
            }
        }
    }, { passive: true });

    document.addEventListener('click', function (event) {
        var now = Date.now();
        if (now - lastClickAt < CLICK_MIN_MS) {
            return;
        }
        var el = event.target;
        if (!el || !el.closest) {
            return;
        }
        var actionable = el.closest('a, button, [role="button"], input[type="submit"], input[type="button"]');
        if (!actionable) {
            return;
        }
        lastClickAt = now;
        sendBehaviorData('click', {
            target: (actionable.tagName || '').toLowerCase(),
            targetId: actionable.id || null
        });
    });
});
