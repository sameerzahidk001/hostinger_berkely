@php
    $batch = is_array($batch ?? null) ? $batch : [];
    $docTitle = trim((string) ($batch['batch_name'] ?? 'Lecture Plan')) ?: 'Lecture Plan';
@endphp
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $docTitle }} Lecture Plan</title>
<style>
    @page { margin: 22mm 14mm 28mm; }
    body { font-family: DejaVu Sans, sans-serif; color: #1f2937; font-size: 11px; }
    .top { width: 100%; margin-bottom: 12px; }
    .top td { vertical-align: middle; }
    .logo { height: 42px; }
    .org { color: #000435; font-size: 16px; font-weight: bold; }
    .doc-title { color: #f8961f; font-size: 13px; font-weight: bold; text-transform: uppercase; }
    .meta { width: 100%; margin: 8px 0 12px; }
    .meta td { padding: 3px 0; }
    table.plan { width: 100%; border-collapse: collapse; }
    table.plan th {
        background: #000435;
        color: #fff;
        text-align: left;
        padding: 6px 5px;
        font-size: 9.5px;
    }
    table.plan td {
        border: 1px solid #d1d5db;
        padding: 5px;
        font-size: 9.5px;
        vertical-align: top;
    }
    table.plan tr:nth-child(even) td { background: #f8fafc; }
    .footer {
        border-top: 1px solid #eee;
        padding-top: 8px;
        margin-top: 16px;
        font-size: 9px;
        color: #777;
        text-align: center;
        line-height: 1.45;
    }
</style>
</head>
<body>
    @php
        $batch = is_array($batch ?? null) ? $batch : [];
        $docTitle = trim((string) ($batch['batch_name'] ?? 'Lecture Plan')) ?: 'Lecture Plan';
        $courseName = data_get($batch, 'course.title') ?: '—';
        $batchCode = trim((string) ($batch['batch_code'] ?? '')) ?: '—';
        $hofName = data_get($batch, 'head_of_faculty.name') ?: '—';
        $insName = data_get($batch, 'instructor.name') ?: '—';
        $sessions = collect($batch['sessions'] ?? []);
        $copyright = data_get($settings ?? null, 'copyright_message')
            ?: ('Copyright © ' . now()->format('Y') . ' Berkeley School of Business, Arts & Sciences | UKPRN: 10101119');

        $logoSrc = null;
        $logoCandidates = [
            public_path('frontend/images/pngs/logo-color.png'),
            public_path('images/' . ltrim((string) data_get($settings ?? null, 'logo'), '/')),
            public_path('images/logo.png'),
        ];
        foreach ($logoCandidates as $candidate) {
            $size = is_file($candidate) ? (int) filesize($candidate) : 0;
            if ($size > 0 && $size < 1500000) {
                $mime = @mime_content_type($candidate) ?: 'image/png';
                $logoSrc = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($candidate));
                break;
            }
        }
        if (! $logoSrc) {
            foreach ($logoCandidates as $candidate) {
                if (is_file($candidate)) {
                    $logoSrc = $candidate;
                    break;
                }
            }
        }
    @endphp

    <table class="top">
        <tr>
            <td style="width:28%;">
                @if($logoSrc)
                    <img class="logo" src="{{ $logoSrc }}" alt="Logo">
                @endif
            </td>
            <td style="text-align:right;">
                <div class="org">Berkeley School of Business, Arts &amp; Sciences</div>
                <div class="doc-title">Lecture Plan</div>
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr>
            <td><strong>Batch name:</strong> {{ $docTitle }}</td>
            <td><strong>Batch code:</strong> {{ $batchCode }}</td>
        </tr>
        <tr>
            <td><strong>Course:</strong> {{ $courseName }}</td>
            <td><strong>Sessions:</strong> {{ $sessions->count() }}</td>
        </tr>
        <tr>
            <td><strong>Instructor:</strong> {{ $insName }}</td>
            <td><strong>Head of Faculty:</strong> {{ $hofName }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Printed:</strong> {{ now()->format('d M Y H:i') }}</td>
        </tr>
    </table>

    <table class="plan">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:12%;">Date</th>
                <th style="width:12%;">Day</th>
                <th style="width:8%;">Time</th>
                <th style="width:12%;">Timezone</th>
                <th style="width:10%;">Duration</th>
                <th style="width:16%;">Title</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $row)
                @php
                    $start = data_get($row, 'scheduled_at');
                    if (is_string($start) && $start !== '') {
                        try { $start = \Carbon\Carbon::parse($start); } catch (\Throwable $e) { $start = null; }
                    }
                    $hasDate = $start instanceof \DateTimeInterface;
                    $duration = (int) (data_get($row, 'duration_minutes') ?: 120);
                    if (is_object($row) && method_exists($row, 'durationMinutes')) {
                        $duration = (int) $row->durationMinutes();
                    }
                    $title = trim((string) data_get($row, 'title', ''));
                    if ($title !== '' && strcasecmp($title, $docTitle) === 0) {
                        $title = '';
                    }
                    $notes = trim((string) data_get($row, 'notes', ''));
                    $tzLabel = trim((string) data_get($row, 'timezone_label', ''));
                    if ($tzLabel === '' && is_object($row) && method_exists($row, 'timezoneLabel')) {
                        $tzLabel = (string) $row->timezoneLabel();
                    }
                    if ($tzLabel === '') {
                        $tzLabel = '—';
                    }
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $hasDate ? $start->format('d M Y') : '—' }}</td>
                    <td>{{ $hasDate ? $start->format('l') : '—' }}</td>
                    <td>{{ $hasDate ? $start->format('H:i') : '—' }}</td>
                    <td>{{ $tzLabel }}</td>
                    <td>{{ $duration }} min</td>
                    <td>{{ $title !== '' ? $title : '—' }}</td>
                    <td>{{ $notes !== '' ? $notes : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;">No sessions in this batch.</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('partials.invoice-document-footer-pdf')
    <div class="footer" style="border-top:0;margin-top:6px;padding-top:0;">{{ $copyright }}</div>
</body>
</html>
