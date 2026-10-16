{{-- Read-only user profile popup (instructors cannot open admin user edit). --}}
<style>
    #lms-user-preview-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 100050;
        background: rgba(0, 4, 53, 0.55);
        align-items: center;
        justify-content: center;
        padding: 16px;
    }
    #lms-user-preview-overlay.is-open { display: flex; }
    #lms-user-preview-box {
        background: #fff;
        color: #1f2937;
        max-width: 480px;
        width: 100%;
        max-height: min(90vh, 640px);
        overflow-y: auto;
        border-radius: 8px;
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
        padding: 22px 24px;
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }
    #lms-user-preview-box h3 {
        margin: 0 0 12px;
        font-size: 18px;
        font-weight: 700;
        color: #000435;
    }
    #lms-user-preview-box .lms-up-row {
        display: flex;
        gap: 10px;
        margin: 0 0 8px;
        font-size: 13px;
        line-height: 1.4;
    }
    #lms-user-preview-box .lms-up-label {
        flex: 0 0 110px;
        color: #6b7280;
        font-weight: 600;
    }
    #lms-user-preview-box .lms-up-value { flex: 1; word-break: break-word; }
    #lms-user-preview-box .lms-up-actions {
        margin-top: 16px;
        text-align: right;
    }
    #lms-user-preview-box .lms-up-close {
        border: none;
        border-radius: 4px;
        padding: 8px 16px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        background: #e5e7eb;
        color: #111827;
    }
    #lms-user-preview-box .lms-up-loading { color: #6b7280; font-size: 13px; }
    #lms-user-preview-box .lms-up-error { color: #b91c1c; font-size: 13px; }
    a.lms-user-preview { cursor: pointer; }
</style>
<div id="lms-user-preview-overlay" role="dialog" aria-modal="true" aria-labelledby="lms-user-preview-title">
    <div id="lms-user-preview-box">
        <h3 id="lms-user-preview-title">Profile</h3>
        <div id="lms-user-preview-body" class="lms-up-loading">Loading…</div>
        <div class="lms-up-actions">
            <button type="button" class="lms-up-close" id="lms-user-preview-close">Close</button>
        </div>
    </div>
</div>
<script>
(function () {
    var overlay = document.getElementById('lms-user-preview-overlay');
    var body = document.getElementById('lms-user-preview-body');
    var closeBtn = document.getElementById('lms-user-preview-close');
    var urlTpl = @json(url('/admin/assessments/users/__ID__/preview'));
    if (!overlay || !body) return;

    function close() {
        overlay.classList.remove('is-open');
    }

    function openPreview(userId) {
        body.className = 'lms-up-loading';
        body.textContent = 'Loading…';
        overlay.classList.add('is-open');
        var url = urlTpl.replace('__ID__', String(userId));
        fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (!res.ok) throw new Error('Could not load profile');
            return res.json();
        }).then(function (data) {
            body.className = '';
            var fields = [
                ['Name', data.name],
                ['Email', data.email],
                ['Mobile', data.mobile],
                ['Role', data.roles],
                ['Gender', data.gender],
                ['Nationality', data.nationality],
                ['City', data.city],
                ['Country', data.country],
                ['Address', data.address],
                ['Post code', data.post_code]
            ];
            body.innerHTML = fields.map(function (row) {
                return '<div class="lms-up-row"><span class="lms-up-label">' + row[0] + '</span><span class="lms-up-value"></span></div>';
            }).join('');
            var vals = body.querySelectorAll('.lms-up-value');
            fields.forEach(function (row, i) {
                vals[i].textContent = row[1] || '—';
            });
            document.getElementById('lms-user-preview-title').textContent = data.name || 'Profile';
        }).catch(function () {
            body.className = 'lms-up-error';
            body.textContent = 'Profile is not available.';
        });
    }

    document.addEventListener('click', function (e) {
        var a = e.target.closest('a.lms-user-preview');
        if (!a) return;
        e.preventDefault();
        var id = a.getAttribute('data-user-id');
        if (id) openPreview(id);
    });
    if (closeBtn) closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) close();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') close();
    });
})();
</script>
