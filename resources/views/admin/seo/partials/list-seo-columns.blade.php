@php
    $seoRecord = $seo ?? null;
    $analysis = $analysis ?? null;
@endphp
<td style="vertical-align: middle;" data-export="{{ $analysis ? (($analysis['score'] ?? 0) . '/100') : '—' }}">
    @if($analysis)
        <span class="seo-score-pill {{ ($analysis['score'] ?? 0) >= 80 ? 'excellent' : (($analysis['score'] ?? 0) >= 50 ? 'good' : 'poor') }}">
            {{ $analysis['score'] ?? 0 }}/100
        </span>
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
