document.addEventListener('DOMContentLoaded', function () {
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
