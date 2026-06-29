@php
    $seoRecord = $seo ?? null;
    $analysis = $analysis ?? null;
    $seoScore = isset($analysis['score']) ? (int) $analysis['score'] : null;
    $liveScore = array_key_exists('live_score', $analysis ?? []) && $analysis['live_score'] !== null
        ? (int) $analysis['live_score']
        : null;
    $scoreExport = $analysis
        ? trim('SEO: ' . ($seoScore !== null ? $seoScore . '/100' : '—') . ' | Live: ' . ($liveScore !== null ? $liveScore . '/100' : '—'))
        : '—';
@endphp
@once
@push('style')
<style>
    .seo-dual-scores { display: flex; flex-direction: column; gap: 4px; min-width: 88px; }
    .seo-dual-scores__row { display: flex; align-items: center; gap: 6px; }
    .seo-dual-scores__label {
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: #676a6c;
        width: 28px;
        flex-shrink: 0;
    }
    .seo-dual-scores .seo-score-pill {
        min-width: 52px;
        font-size: 12px;
        padding: 3px 6px;
    }
</style>
@endpush
@endonce
<td style="vertical-align: middle;" data-export="{{ $scoreExport }}">
    @if($analysis)
        @include('admin.seo.partials.combined-score-cell', [
            'seoScore' => $seoScore,
            'liveScore' => $liveScore,
        ])
    @else
        <span class="text-muted">—</span>
    @endif
</td>
<td class="seo-details" style="vertical-align: middle;" data-export="{{ $analysis ? ('Keyword: ' . ($analysis['focus_keyword'] ?: '—') . ' | Schema: ' . ($analysis['schema'] ?? 'Article') . ' | Links: ' . ($analysis['external_links'] ?? 0) . ' ext / ' . ($analysis['internal_links'] ?? 0) . ' int | Words: ' . number_format($analysis['word_count'] ?? 0)) : 'SEO not added' }}">
    @if($analysis)
        <small><strong>Keyword:</strong> {{ $analysis['focus_keyword'] ?: '—' }}</small>
        <small><strong>Schema:</strong> {{ $analysis['schema'] ?? 'Article' }}</small>
        <small><strong>Links:</strong> {{ $analysis['external_links'] ?? 0 }} ext / {{ $analysis['internal_links'] ?? 0 }} int</small>
        <small><strong>Words:</strong> {{ number_format($analysis['word_count'] ?? 0) }}</small>
    @else
        <span class="text-muted">SEO not added</span>
    @endif
</td>
<td style="vertical-align: middle;" data-export="{{ $seoRecord && $seoRecord->meta_description ? \Illuminate\Support\Str::limit($seoRecord->meta_description, 120) : '—' }}">
    @if($seoRecord && $seoRecord->meta_description)
        {{ \Illuminate\Support\Str::limit($seoRecord->meta_description, 120) }}
    @else
        <span class="text-muted">—</span>
    @endif
</td>
