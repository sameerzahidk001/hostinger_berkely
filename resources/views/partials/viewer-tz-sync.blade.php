<script>
(function () {
    var tz = '';
    try {
        tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    } catch (e) {}
    if (!tz) return;
    var cookie = '';
    document.cookie.split(';').forEach(function (part) {
        var s = part.trim();
        if (s.indexOf('viewer_tz=') === 0) {
            cookie = decodeURIComponent(s.slice('viewer_tz='.length));
        }
    });
    if (cookie === tz) return;
    document.cookie = 'viewer_tz=' + encodeURIComponent(tz) + ';path=/;max-age=31536000;SameSite=Lax';
    var serverTz = document.body ? (document.body.getAttribute('data-viewer-tz') || '') : '';
    if (serverTz && serverTz !== tz) {
        window.location.reload();
    }
})();
</script>
