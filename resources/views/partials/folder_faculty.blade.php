@php
    $hof = $folder?->displayHeadOfFaculty();
    $trainers = $folder ? $folder->displayTrainerInstructors() : collect();
    $linkStyle = $linkStyle ?? 'color:#1c84c6;text-decoration:underline;';
    $labelTag = $labelTag ?? 'p';
    $labelClass = $labelClass ?? '';
    $labelStyle = $labelStyle ?? 'margin-bottom:6px;';
@endphp
<{{ $labelTag }} @if($labelClass !== '') class="{{ $labelClass }}" @endif style="{{ $labelStyle }}">
    Instructor:
    @forelse($trainers as $ins)
        <a href="{{ url('/instructor/' . $ins->id) }}" target="_blank" rel="noopener" style="{{ $linkStyle }}"><strong>{{ $ins->name }}</strong></a>@if(! $loop->last), @endif
    @empty
        —
    @endforelse
</{{ $labelTag }}>
<{{ $labelTag }} @if($labelClass !== '') class="{{ $labelClass }}" @endif style="{{ $labelStyle }}">
    Head of Faculty:
    @if($hof)
        <a href="{{ url('/instructor/' . $hof->id) }}" target="_blank" rel="noopener" style="{{ $linkStyle }}"><strong>{{ $hof->name }}</strong></a>
    @else
        —
    @endif
</{{ $labelTag }}>
