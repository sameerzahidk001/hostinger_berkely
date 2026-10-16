{{-- Shown after CSRF/session expiry: stay on the same page with a refresh prompt (no 419 error screen). --}}
@if(session('session_expired'))
<style>
    #lms-session-expired-overlay {
        position: fixed;
        inset: 0;
        z-index: 100000;
        background: rgba(0, 4, 53, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    #lms-session-expired-box {
        background: #fff;
        color: #1f2937;
        max-width: 420px;
        width: 100%;
        border-radius: 8px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
        padding: 22px 24px;
        text-align: center;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }
    #lms-session-expired-box h3 {
        margin: 0 0 8px;
        font-size: 18px;
        font-weight: 700;
        color: #000435;
    }
    #lms-session-expired-box p {
        margin: 0 0 16px;
        font-size: 14px;
        line-height: 1.45;
        color: #4b5563;
    }
    #lms-session-expired-box .lms-se-actions {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
    }
    #lms-session-expired-box .lms-se-btn {
        display: inline-block;
        border: none;
        border-radius: 4px;
        padding: 9px 18px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
    }
    #lms-session-expired-box .lms-se-btn-primary {
        background: #e6a60b;
        color: #000435;
    }
    #lms-session-expired-box .lms-se-btn-muted {
        background: #e5e7eb;
        color: #111827;
    }
</style>
<div id="lms-session-expired-overlay" role="dialog" aria-modal="true" aria-labelledby="lms-session-expired-title">
    <div id="lms-session-expired-box">
        <h3 id="lms-session-expired-title">Session expired</h3>
        <p>Your session timed out. Refresh this page to continue — you will not see an error screen.</p>
        <div class="lms-se-actions">
            <button type="button" class="lms-se-btn lms-se-btn-primary" id="lms-session-expired-refresh">Refresh</button>
            <a href="{{ url('/') }}" class="lms-se-btn lms-se-btn-muted">Home</a>
        </div>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('lms-session-expired-overlay');
    var btn = document.getElementById('lms-session-expired-refresh');
    if (!overlay || !btn) return;
    function refreshNow() {
        window.location.reload();
    }
    btn.addEventListener('click', refreshNow);
    // Auto-reload shortly so the form gets a fresh CSRF token without a 419 page.
    setTimeout(refreshNow, 1600);
})();
</script>
@endif
<script>
(function () {
    function showSessionExpiredPrompt() {
        if (document.getElementById('lms-session-expired-overlay')) {
            return;
        }
        var overlay = document.createElement('div');
        overlay.id = 'lms-session-expired-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:100000;background:rgba(0,4,53,.55);display:flex;align-items:center;justify-content:center;padding:16px;';
        overlay.innerHTML =
            '<div style="background:#fff;color:#1f2937;max-width:420px;width:100%;border-radius:8px;box-shadow:0 12px 40px rgba(0,0,0,.25);padding:22px 24px;text-align:center;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;">' +
            '<h3 style="margin:0 0 8px;font-size:18px;font-weight:700;color:#000435;">Session expired</h3>' +
            '<p style="margin:0 0 16px;font-size:14px;line-height:1.45;color:#4b5563;">Your session timed out. Refresh this page to continue.</p>' +
            '<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">' +
            '<button type="button" id="lms-session-expired-refresh" style="border:none;border-radius:4px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;background:#e6a60b;color:#000435;">Refresh</button>' +
            '<a href="/" style="display:inline-block;border-radius:4px;padding:9px 18px;font-size:13px;font-weight:700;text-decoration:none;background:#e5e7eb;color:#111827;">Home</a>' +
            '</div></div>';
        document.body.appendChild(overlay);
        var btn = document.getElementById('lms-session-expired-refresh');
        if (btn) {
            btn.addEventListener('click', function () { window.location.reload(); });
        }
        setTimeout(function () { window.location.reload(); }, 1600);
    }

    if (window.jQuery) {
        jQuery(document).ajaxError(function (_e, xhr) {
            if (xhr && xhr.status === 419) {
                showSessionExpiredPrompt();
            }
        });
    }

    if (window.fetch) {
        var origFetch = window.fetch;
        window.fetch = function () {
            return origFetch.apply(this, arguments).then(function (res) {
                if (res && res.status === 419) {
                    showSessionExpiredPrompt();
                }
                return res;
            });
        };
    }
})();
</script>
