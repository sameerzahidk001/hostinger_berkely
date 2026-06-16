@php
    $seoTitle = old('title', $page_seo->title ?? '');
    $seoDescription = old('meta_description', $page_seo->meta_description ?? '');
    $seoKeywords = old('keywords', $page_seo->keywords ?? '');
@endphp

<div class="ibox" id="seo-score-panel">
    <div class="ibox-title">
        <h5>SEO Analysis</h5>
    </div>
    <div class="ibox-content">
        <div class="row">
            <div class="col-md-4 text-center">
                <div style="font-size:42px;font-weight:bold;" id="seo-score-value">0</div>
                <div class="text-muted">SEO Rate</div>
                <div id="seo-score-label" class="label label-default" style="margin-top:8px;display:inline-block;">Needs work</div>
            </div>
            <div class="col-md-8">
                <ul class="list-unstyled" id="seo-checklist" style="margin:0;"></ul>
            </div>
        </div>
        <hr>
        <div class="well" style="margin-bottom:0;">
            <div id="seo-preview-title" style="color:#1a0dab;font-size:18px;">{{ $seoTitle ?: 'Page title preview' }}</div>
            <div id="seo-preview-url" style="color:#006621;font-size:13px;">{{ url('/') }}/example-page</div>
            <div id="seo-preview-description" style="color:#545454;font-size:13px;margin-top:4px;">{{ $seoDescription ?: 'Meta description preview will appear here.' }}</div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.querySelector('[name="title"]');
    const descInput = document.querySelector('[name="meta_description"]');
    const keywordsInput = document.querySelector('[name="keywords"]');

    function scoreSeo() {
        const title = (titleInput?.value || '').trim();
        const description = (descInput?.value || '').trim();
        const keywords = (keywordsInput?.value || '').trim();
        const checks = [];
        let score = 0;

        if (title.length >= 30 && title.length <= 60) {
            score += 30;
            checks.push({ ok: true, text: 'Title length is good (30–60 characters).' });
        } else if (title.length > 0) {
            score += 10;
            checks.push({ ok: false, text: 'Title should be between 30 and 60 characters.' });
        } else {
            checks.push({ ok: false, text: 'Add a page title.' });
        }

        if (description.length >= 120 && description.length <= 160) {
            score += 30;
            checks.push({ ok: true, text: 'Meta description length is good (120–160 characters).' });
        } else if (description.length > 0) {
            score += 10;
            checks.push({ ok: false, text: 'Meta description should be between 120 and 160 characters.' });
        } else {
            checks.push({ ok: false, text: 'Add a meta description.' });
        }

        if (keywords.length > 0) {
            score += 20;
            checks.push({ ok: true, text: 'Priority keywords are set.' });
        } else {
            checks.push({ ok: false, text: 'Add priority keywords.' });
        }

        if (title && keywords && keywords.toLowerCase().includes(title.toLowerCase().split(' ')[0])) {
            score += 10;
            checks.push({ ok: true, text: 'Primary keyword appears in the title.' });
        } else if (title && keywords) {
            checks.push({ ok: false, text: 'Consider using your main keyword in the title.' });
        }

        if (description && title && description.toLowerCase().includes(title.toLowerCase().split(' ')[0])) {
            score += 10;
            checks.push({ ok: true, text: 'Title keyword appears in the description.' });
        }

        score = Math.min(score, 100);

        document.getElementById('seo-score-value').textContent = score;
        const label = document.getElementById('seo-score-label');
        if (score >= 80) {
            label.className = 'label label-primary';
            label.textContent = 'Excellent';
        } else if (score >= 50) {
            label.className = 'label label-warning';
            label.textContent = 'Good';
        } else {
            label.className = 'label label-danger';
            label.textContent = 'Needs work';
        }

        document.getElementById('seo-checklist').innerHTML = checks.map(c =>
            '<li style="margin-bottom:6px;"><i class="fa fa-' + (c.ok ? 'check text-success' : 'times text-danger') + '"></i> ' + c.text + '</li>'
        ).join('');

        document.getElementById('seo-preview-title').textContent = title || 'Page title preview';
        document.getElementById('seo-preview-description').textContent = description || 'Meta description preview will appear here.';
    }

    [titleInput, descInput, keywordsInput].forEach(el => el?.addEventListener('input', scoreSeo));
    scoreSeo();
});
</script>
