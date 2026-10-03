@php
    $courseModel = $course ?? null;
    $courseTitle = trim((string) ($courseModel->title ?? ''));
    $courseUrl = course_details_url($courseModel);
    $courseFallback = $fallback ?? '—';
@endphp
@if($courseTitle !== '' && $courseUrl)
    <a href="{{ $courseUrl }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;font-weight:700;">{{ $courseTitle }}</a>
@elseif($courseTitle !== '')
    {{ $courseTitle }}
@else
    {{ $courseFallback }}
@endif
