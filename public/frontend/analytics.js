document.addEventListener('DOMContentLoaded', function () {
    (function trackTimeSpent() {
        var path = (window.location.pathname || '/').toLowerCase();
        var hidden = ['/admin', '/user', '/login', '/register', '/logout', '/password', '/captcha', '/analytics'];
        if (hidden.some(function (p) { return path === p || path.indexOf(p + '/') === 0; })) {
            return;
        }
        var tokenEl = document.querySelector('meta[name="csrf-token"]');
        if (!tokenEl) return;
        var last = Date.now();
        function ping() {
            if (document.visibilityState && document.visibilityState !== 'visible') {
                last = Date.now();
                return;
            }
            var seconds = Math.round((Date.now() - last) / 1000);
            last = Date.now();
            if (seconds < 1) return;
            fetch('/analytics/ping', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': tokenEl.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ url: window.location.href, seconds: Math.min(30, seconds) }),
                keepalive: true
            }).catch(function () {});
        }
        setInterval(ping, 15000);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') ping();
        });
        window.addEventListener('pagehide', ping);
    })();

    let scrollDepth = 0;
    let totalHeight = document.body.scrollHeight - window.innerHeight;

    // Track scroll depth
    window.addEventListener('scroll', function () {
        scrollDepth = Math.round((window.scrollY / totalHeight) * 100); // Scroll percentage
        sendBehaviorData('scroll', { depth: scrollDepth });
    });

    // Track clicks
    document.addEventListener('click', function (event) {
        const target = event.target.tagName;
        const targetId = event.target.id || null;
        sendBehaviorData('click', { target, targetId });
    });

    // Function to send behavior data to the backend
    function sendBehaviorData(eventType, data) {
        fetch('/user-behavior', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                event_type: eventType,
                data: data,
                url: window.location.href,
                session_id: sessionStorage.getItem('session_id') || generateSessionId(),
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('Success:', data);
        })
        .catch((error) => {
            console.error('Error:', error);
        });
    }

    // Generate session ID if not already stored
    function generateSessionId() {
        const sessionId = 'sess_' + Math.random().toString(36).substr(2, 9);
        sessionStorage.setItem('session_id', sessionId);
        return sessionId;
    }
});
