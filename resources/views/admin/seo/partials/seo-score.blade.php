@php
    $analysis = $seo_analysis ?? null;
    $score = (int) ($analysis['score'] ?? 0);
    $scoreClass = $score >= 80 ? 'excellent' : ($score >= 50 ? 'good' : 'poor');
    $label = $analysis['label'] ?? 'Needs work';
    $previewUrl = $analysis['preview_url'] ?? url('/');
    $fieldNames = array_merge([
        'title' => 'title',
        'meta_description' => 'meta_description',
        'focus_keyword' => 'focus_keyword',
        'keywords' => 'keywords',
        'thumbnail_alt' => 'thumbnail_alt',
    ], $fieldNames ?? []);
    $seoMeta = $page_seo ?? $seoMeta ?? null;
    $seoTitle = old($fieldNames['title'], $seoMeta->title ?? '');
    $seoDescription = old($fieldNames['meta_description'], $seoMeta->meta_description ?? '');
    $contentScore = (int) ($analysis['content_score'] ?? 0);
    $liveScore = isset($analysis['live_score']) && $analysis['live_score'] !== null
        ? (int) $analysis['live_score']
        : null;
    $basicChecks = $analysis['basic'] ?? [];
    $additionalChecks = $analysis['additional'] ?? [];
    $technicalChecks = $analysis['technical'] ?? [];
@endphp

<style>
    #seo-score-panel .seo-score-pill {
        display: inline-block;
        min-width: 72px;
        text-align: center;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 4px;
        color: #fff;
        font-size: 22px;
    }
    #seo-score-panel .seo-score-pill.excellent { background: #1ab394; }
    #seo-score-panel .seo-score-pill.good { background: #f8ac59; }
    #seo-score-panel .seo-score-pill.poor { background: #ed5565; }
    #seo-score-panel .seo-subscore {
        font-size: 12px;
        color: #676a6c;
        margin-top: 4px;
    }
    #seo-score-panel .seo-section-title {
        font-size: 13px;
        font-weight: 600;
        margin: 12px 0 6px;
        color: #2f4050;
    }
</style>

<div class="ibox" id="seo-score-panel">
    <div class="ibox-title">
        <h5>SEO Analysis</h5>
    </div>
    <div class="ibox-content">
        <div class="row">
            <div class="col-md-4 text-center">
                <div id="seo-score-value">
                    <span class="seo-score-pill {{ $scoreClass }}">{{ $score }}/100</span>
                </div>
                <div class="text-muted" style="margin-top:8px;">Overall SEO score</div>
                <div id="seo-score-label" class="label label-default" style="margin-top:8px;display:inline-block;">{{ $label }}</div>
                <div id="seo-subscores" class="seo-subscore">
                    SEO: <span id="seo-content-score">{{ $score }}</span>/100
                    &middot;
                    Live page: <span id="seo-live-score">{{ $liveScore !== null ? $liveScore . '/100' : '—' }}</span>
                </div>
                <p class="text-muted" style="font-size:11px;margin-top:10px;">
                    Overall score matches Courses, Pages, and SEO lists. Live page checks below are for verification only.
                </p>
            </div>
            <div class="col-md-8">
                <div class="seo-section-title">Content checks (admin fields &amp; copy)</div>
                <ul class="list-unstyled" id="seo-checklist-basic" style="margin:0 0 8px;">
                    @foreach($basicChecks as $check)
                        <li style="margin-bottom:6px;">
                            <i class="fa fa-{{ !empty($check['ok']) ? 'check text-success' : 'times text-danger' }}"></i>
                            {{ $check['text'] }}
                        </li>
                    @endforeach
                </ul>
                <ul class="list-unstyled" id="seo-checklist-additional" style="margin:0;">
                    @foreach($additionalChecks as $check)
                        <li style="margin-bottom:6px;">
                            <i class="fa fa-{{ !empty($check['ok']) ? 'check text-success' : 'times text-danger' }}"></i>
                            {{ $check['text'] }}
                        </li>
                    @endforeach
                </ul>

                <div class="seo-section-title">Live page checks (public URL)</div>
                <ul class="list-unstyled" id="seo-checklist-technical" style="margin:0;">
                    @foreach($technicalChecks as $check)
                        <li style="margin-bottom:6px;">
                            <i class="fa fa-{{ !empty($check['ok']) ? 'check text-success' : 'times text-danger' }}"></i>
                            {{ $check['text'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <hr>
        <div class="well" style="margin-bottom:0;">
            <div id="seo-preview-title" style="color:#1a0dab;font-size:18px;">{{ $seoTitle ?: 'Page title preview' }}</div>
            <div id="seo-preview-url" style="color:#006621;font-size:13px;">{{ $previewUrl }}</div>
            <div id="seo-preview-description" style="color:#545454;font-size:13px;margin-top:4px;">{{ $seoDescription ?: 'Meta description preview will appear here.' }}</div>
        </div>
    </div>
</div>

@if(isset($page_seo) && $page_seo->id)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const titleInput = document.querySelector('[name="{{ $fieldNames['title'] }}"]');
    const descInput = document.querySelector('[name="{{ $fieldNames['meta_description'] }}"]');
    const focusKeywordInput = document.querySelector('[name="{{ $fieldNames['focus_keyword'] }}"]');
    const keywordsInput = document.querySelector('[name="{{ $fieldNames['keywords'] }}"]');
    const thumbnailAltInput = document.querySelector('[name="{{ $fieldNames['thumbnail_alt'] }}"]');
    const analyzeUrl = @json(route('courses-pages-seo.analyze', $page_seo->id));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let timer = null;

    function scoreClass(score) {
        if (score >= 80) return 'excellent';
        if (score >= 50) return 'good';
        return 'poor';
    }

    function labelClass(score) {
        if (score >= 80) return 'label label-primary';
        if (score >= 50) return 'label label-warning';
        return 'label label-danger';
    }

    function renderChecklist(id, checks) {
        const el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = (checks || []).map(function (c) {
            return '<li style="margin-bottom:6px;"><i class="fa fa-' +
                (c.ok ? 'check text-success' : 'times text-danger') +
                '"></i> ' + c.text + '</li>';
        }).join('');
    }

    function renderAnalysis(data) {
        const score = parseInt(data.score || 0, 10);
        const cls = scoreClass(score);

        document.getElementById('seo-score-value').innerHTML =
            '<span class="seo-score-pill ' + cls + '">' + score + '/100</span>';

        const label = document.getElementById('seo-score-label');
        label.className = labelClass(score);
        label.textContent = data.label || 'Needs work';

        const liveScore = data.live_score;
        document.getElementById('seo-content-score').textContent = data.score || 0;
        document.getElementById('seo-live-score').textContent =
            liveScore !== null && liveScore !== undefined && liveScore !== '' ? liveScore + '/100' : '—';

        renderChecklist('seo-checklist-basic', data.basic || []);
        renderChecklist('seo-checklist-additional', data.additional || []);
        renderChecklist('seo-checklist-technical', data.technical || []);

        if (data.preview_url) {
            document.getElementById('seo-preview-url').textContent = data.preview_url;
        }
    }

    function refreshAnalysis() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            const title = (titleInput?.value || '').trim();
            const description = (descInput?.value || '').trim();
            const focusKeyword = (focusKeywordInput?.value || '').trim();
            const keywords = (keywordsInput?.value || '').trim();
            const thumbnailAlt = (thumbnailAltInput?.value || '').trim();

            document.getElementById('seo-preview-title').textContent = title || 'Page title preview';
            document.getElementById('seo-preview-description').textContent =
                description || 'Meta description preview will appear here.';

            fetch(analyzeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    title: title,
                    meta_description: description,
                    focus_keyword: focusKeyword,
                    keywords: keywords,
                    thumbnail_alt: thumbnailAlt,
                }),
            })
                .then(function (r) { return r.json(); })
                .then(renderAnalysis)
                .catch(console.error);
        }, 400);
    }

    [titleInput, descInput, focusKeywordInput, thumbnailAltInput].forEach(function (el) {
        el?.addEventListener('input', refreshAnalysis);
    });

    if (keywordsInput && window.jQuery) {
        jQuery(keywordsInput).on('addTag removeTag', refreshAnalysis);
    }
    keywordsInput?.addEventListener('change', refreshAnalysis);
});
</script>
@endif
