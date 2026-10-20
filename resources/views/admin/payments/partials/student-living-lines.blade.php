@php
    $studentName = $studentName ?? 'N/A';
    $nationality = $nationality ?? '—';
    $country = $country ?? '—';
    $city = trim((string) ($city ?? ''));
    $city = $city !== '' ? $city : '—';
@endphp
<div class="student-living-cell">
    <strong>{{ $studentName }}</strong>
    <div>Nationality: {{ $nationality }}</div>
    <div>Country, City: {{ $country }}, {{ $city }}</div>
</div>
