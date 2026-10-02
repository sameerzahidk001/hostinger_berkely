<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $batchName }} Lecture Plan</title>
<style>
    @page { margin: 22mm 14mm 24mm; }
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
        margin-top: 16px;
        border-top: 2px solid #000435;
        padding-top: 8px;
        font-size: 9px;
        color: #4b5563;
    }
    .footer strong { color: #000435; }
</style>
</head>
<body>
    @php
        $logoPath = public_path('frontend/images/pngs/logo-color.png');
        if (! empty($settings?->logo)) {
            $fromSettings = public_path('images/' . ltrim((string) $settings->logo, '/'));
            if (is_file($fromSettings)) {
                $logoPath = $fromSettings;
            }
        }
        $batchName = (string) ($batch['batch_name'] ?? 'Lecture Plan');
        $course = $batch['course'] ?? null;
        $hof = $batch['head_of_faculty'] ?? null;
        $ins = $batch['instructor'] ?? null;
        $sessions = $batch['sessions'] ?? collect();
    @endphp

    <table class="top">
        <tr>
            <td style="width:28%;">
                @if(is_file($logoPath))
                    <img class="logo" src="{{ $logoPath }}" alt="Logo">
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
            <td><strong>Batch name:</strong> {{ $batchName }}</td>
            <td><strong>Course:</strong> {{ $course->title ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Head of Faculty:</strong> {{ $hof->name ?? '—' }}</td>
            <td><strong>Instructor:</strong> {{ $ins->name ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Sessions:</strong> {{ is_countable($sessions) ? count($sessions) : 0 }}</td>
            <td><strong>Printed:</strong> {{ now()->timezone(config('app.timezone'))->format('d M Y H:i') }}</td>
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
                    $duration = method_exists($row, 'durationMinutes')
                        ? $row->durationMinutes()
                        : (int) ($row->duration_minutes ?? 120);
                    $title = trim((string) ($row->title ?? ''));
                    if ($title !== '' && strcasecmp($title, $batchName) === 0) {
                        $title = '';
                    }
                    $notes = trim((string) ($row->notes ?? ''));
                    $tzLabel = method_exists($row, 'timezoneLabel')
                        ? $row->timezoneLabel()
                        : (string) ($row->timezone_label ?? '—');
                    $start = $row->scheduled_at ?? null;
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $start?->format('d M Y') ?? '—' }}</td>
                    <td>{{ $start?->format('l') ?? '—' }}</td>
                    <td>{{ $start?->format('H:i') ?? '—' }}</td>
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

    <div class="footer">
        <strong>{{ $batchName }}</strong><br>
        Course: {{ $course->title ?? '—' }} · Head of Faculty: {{ $hof->name ?? '—' }} · Instructor: {{ $ins->name ?? '—' }}<br>
        {{ $settings->copyright_message ?? 'Berkeley School of Business, Arts & Sciences' }}
    </div>
</body>
</html>
