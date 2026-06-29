@php
    $value = isset($score) && $score !== null && $score !== '' ? (int) $score : null;
    $pillClass = $value === null ? '' : ($value >= 80 ? 'excellent' : ($value >= 50 ? 'good' : 'poor'));
@endphp
@if($value === null)
    <span class="text-muted" title="{{ $emptyTitle ?? 'Open SEO Edit to scan the live public page' }}">—</span>
@else
    <span class="seo-score-pill {{ $pillClass }}" @if(!empty($title)) title="{{ $title }}" @endif>{{ $value }}/100</span>
@endif
