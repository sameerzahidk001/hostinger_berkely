@php
    $seoRecord = $seo ?? null;
    $analysis = $analysis ?? null;
    $seoScore = $analysis && isset($analysis['score']) ? (int) $analysis['score'] : null;
    $scoreExport = $seoScore !== null ? ($seoScore . '/100') : '—';
    $export = $seoRecord ? $scoreExport : 'Not Added';
@endphp
<td style="vertical-align: top;" data-order="{{ $seoScore ?? ($seoRecord ? 0 : -1) }}" data-export="{{ $export }}">
    <div class="lms-cell-stack" style="display:flex;flex-direction:column;align-items:flex-start;gap:4px;">
    @if($seoRecord)
        <div>
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
    </div>
</td>
