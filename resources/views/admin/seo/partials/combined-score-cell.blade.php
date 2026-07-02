@php
    $seoScore = isset($seoScore) ? (int) $seoScore : null;
    $liveScore = isset($liveScore) && $liveScore !== null && $liveScore !== '' ? (int) $liveScore : null;
@endphp
@if($seoScore === null && $liveScore === null)
    <span class="text-muted">—</span>
@else
    <div class="seo-dual-scores" title="SEO = content &amp; metadata. Live = public page scan.">
        <div class="seo-dual-scores__row">
            <span class="seo-dual-scores__label">SEO</span>
            @include('admin.seo.partials.score-pill', [
                'score' => $seoScore,
                'title' => 'SEO score (content + saved metadata)',
            ])
        </div>
        <div class="seo-dual-scores__row">
            <span class="seo-dual-scores__label">Live</span>
            @include('admin.seo.partials.score-pill', [
                'score' => $liveScore,
                'emptyTitle' => 'Not scanned yet — open SEO Edit',
                'title' => $liveScore !== null ? 'Live public page score' : null,
            ])
        </div>
    </div>
@endif
