@php
    $seoRecord = $seo ?? null;
    $analysis = $analysis ?? null;
    $seoScore = isset($analysis['score']) ? (int) $analysis['score'] : null;
    $scoreExport = $analysis && $seoScore !== null ? ($seoScore . '/100') : '—';
@endphp
<td style="vertical-align: middle;" data-order="{{ $seoScore ?? -1 }}" data-export="{{ $scoreExport }}">
    @if($analysis && $seoScore !== null)
        @include('admin.seo.partials.score-pill', [
            'score' => $seoScore,
            'title' => 'SEO score (content + saved metadata)',
        ])
    @else
        <span class="text-muted">—</span>
    @endif
</td>
<td class="seo-details" style="vertical-align: middle;"
    data-order="{{ $analysis ? (($analysis['focus_keyword'] ?? '') . ' ' . ($analysis['word_count'] ?? 0)) : '' }}"
    data-export="{{ $analysis ? ('Keyword: ' . ($analysis['focus_keyword'] ?: '—') . ' | Schema: ' . ($analysis['schema'] ?? 'Article') . ' | Links: ' . ($analysis['external_links'] ?? 0) . ' ext / ' . ($analysis['internal_links'] ?? 0) . ' int | Words: ' . number_format($analysis['word_count'] ?? 0)) : 'SEO not added' }}">
    @if($analysis)
        <small><strong>Keyword:</strong> {{ $analysis['focus_keyword'] ?: '—' }}</small>
        <small><strong>Schema:</strong> {{ $analysis['schema'] ?? 'Article' }}</small>
        <small><strong>Links:</strong> {{ $analysis['external_links'] ?? 0 }} ext / {{ $analysis['internal_links'] ?? 0 }} int</small>
        <small><strong>Words:</strong> {{ number_format($analysis['word_count'] ?? 0) }}</small>
    @else
        <span class="text-muted">SEO not added</span>
    @endif
</td>
<td style="vertical-align: middle;"
    data-order="{{ $seoRecord && $seoRecord->meta_description ? $seoRecord->meta_description : '' }}"
    data-export="{{ $seoRecord && $seoRecord->meta_description ? \Illuminate\Support\Str::limit($seoRecord->meta_description, 120) : '—' }}">
    @if($seoRecord && $seoRecord->meta_description)
        {{ \Illuminate\Support\Str::limit($seoRecord->meta_description, 120) }}
    @else
        <span class="text-muted">—</span>
    @endif
</td>
