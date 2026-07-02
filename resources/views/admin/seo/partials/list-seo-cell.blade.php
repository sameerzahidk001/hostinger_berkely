@php
    $seoRecord = $seo ?? null;
    $analysis = $analysis ?? null;
    $seoScore = $analysis && isset($analysis['score']) ? (int) $analysis['score'] : null;
    $scoreExport = $seoScore !== null ? ($seoScore . '/100') : '—';
    $export = $seoRecord ? $scoreExport : 'Not Added';
@endphp
<td style="vertical-align: middle;" data-order="{{ $seoScore ?? ($seoRecord ? 0 : -1) }}" data-export="{{ $export }}">
    @if($seoRecord)
        <div style="margin-bottom:4px;">
            @include('admin.seo.partials.score-pill', [
                'score' => $seoScore,
                'title' => 'SEO score',
            ])
        </div>
        <a href="{{ $editUrl }}" class="label label-primary" target="_blank">View</a>
    @else
        <span class="label label-danger">Not Added</span>
        <a href="{{ $createUrl }}" class="label label-danger" target="_blank">Add</a>
    @endif
</td>
