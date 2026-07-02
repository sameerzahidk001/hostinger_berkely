@php
    $analysis = $seo_analysis ?? null;
    $score = (int) ($analysis['score'] ?? 0);
    $scoreClass = $score >= 80 ? 'excellent' : ($score >= 50 ? 'good' : 'poor');
    $label = $analysis['label'] ?? 'Needs work';
    $previewUrl = $analysis['preview_url'] ?? url('/');
    $sections = $analysis['sections'] ?? [];
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

    if ($sections === [] && $analysis) {
        $sections = [
            ['title' => 'Basic SEO', 'score' => null, 'checks' => $analysis['basic'] ?? []],
            ['title' => 'Content & Links', 'score' => null, 'checks' => $analysis['additional'] ?? []],
            ['title' => 'Technical & Live', 'score' => null, 'checks' => $analysis['technical'] ?? []],
        ];
    }
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
    #seo-score-panel .seo-section-block {
        margin-bottom: 14px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e7eaec;
    }
    #seo-score-panel .seo-section-block:last-child {
        border-bottom: 0;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    #seo-score-panel .seo-section-title {
        font-size: 13px;
        font-weight: 600;
        margin: 0 0 6px;
        color: #2f4050;
    }
    #seo-score-panel .seo-section-score {
        font-size: 11px;
        color: #676a6c;
        font-weight: normal;
    }
    #seo-score-panel .seo-stats {
        font-size: 12px;
        color: #676a6c;
        margin-top: 10px;
        line-height: 1.6;
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
                <div id="seo-stats" class="seo-stats">
                    @if($analysis)
                        <div><strong>Words:</strong> <span id="seo-word-count">{{ number_format($analysis['word_count'] ?? 0) }}</span></div>
                        <div><strong>Keyword density:</strong> <span id="seo-keyword-density">{{ $analysis['keyword_density'] ?? 0 }}</span>%</div>
                        <div><strong>Readability:</strong> <span id="seo-readability">{{ $analysis['readability_score'] ?? '—' }}</span></div>
                    @endif
                </div>
                <p class="text-muted" style="font-size:11px;margin-top:10px;">
                    Same score as Courses, Pages, and SEO lists. Full Yoast-style checks run here on save and as you type.
                </p>
            </div>
            <div class="col-md-8" id="seo-sections-container">
                @foreach($sections as $section)
                    <div class="seo-section-block" data-section-key="{{ $section['key'] ?? '' }}">
                        <div class="seo-section-title">
                            {{ $section['title'] ?? 'Checks' }}
                            @if(isset($section['score']))
                                <span class="seo-section-score">({{ (int) $section['score'] }}/100)</span>
                            @endif
                        </div>
                        <ul class="list-unstyled seo-checklist" style="margin:0;">
                            @foreach($section['checks'] ?? [] as $check)
                                <li style="margin-bottom:6px;">
                                    <i class="fa fa-{{ !empty($check['ok']) ? 'check text-success' : 'times text-danger' }}"></i>
                                    {{ $check['text'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
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

    function renderSections(sections) {
        const container = document.getElementById('seo-sections-container');
        if (!container) return;

        container.innerHTML = (sections || []).map(function (section) {
            const checks = (section.checks || []).map(function (c) {
                return '<li style="margin-bottom:6px;"><i class="fa fa-' +
                    (c.ok ? 'check text-success' : 'times text-danger') +
                    '"></i> ' + c.text + '</li>';
            }).join('');

            const sectionScore = section.score !== undefined && section.score !== null
                ? '<span class="seo-section-score">(' + section.score + '/100)</span>'
                : '';

            return '<div class="seo-section-block" data-section-key="' + (section.key || '') + '">' +
                '<div class="seo-section-title">' + (section.title || 'Checks') + sectionScore + '</div>' +
                '<ul class="list-unstyled seo-checklist" style="margin:0;">' + checks + '</ul>' +
                '</div>';
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

        if (data.word_count !== undefined) {
            const wc = document.getElementById('seo-word-count');
            if (wc) wc.textContent = Number(data.word_count || 0).toLocaleString();
        }
        if (data.keyword_density !== undefined) {
            const kd = document.getElementById('seo-keyword-density');
            if (kd) kd.textContent = data.keyword_density;
        }
        if (data.readability_score !== undefined) {
            const rs = document.getElementById('seo-readability');
            if (rs) rs.textContent = data.readability_score !== null ? data.readability_score : '—';
        }

        if (data.sections && data.sections.length) {
            renderSections(data.sections);
        } else {
            renderSections([
                { title: 'Basic SEO', checks: data.basic || [] },
                { title: 'Content & Links', checks: data.additional || [] },
                { title: 'Technical & Live', checks: data.technical || [] },
            ]);
        }

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
