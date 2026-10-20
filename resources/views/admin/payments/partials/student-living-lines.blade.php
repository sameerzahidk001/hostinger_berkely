@php
    $studentName = $studentName ?? 'N/A';
    $nationality = $nationality ?? '—';
    $country = $country ?? '—';
    $city = trim((string) ($city ?? ''));
    $city = $city !== '' ? $city : '—';
@endphp
{{ $studentName }}
<br><small>Nationality: {{ $nationality }}</small>
<br><small>Country, City: {{ $country }}, {{ $city }}</small>
