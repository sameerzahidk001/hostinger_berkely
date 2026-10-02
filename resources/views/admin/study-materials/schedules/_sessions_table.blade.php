@php
    $isAdminView = !empty($isAdminView);
    $batchName = (string) ($batch['batch_name'] ?? '');
    $sessions = $batch['sessions'] ?? collect();
    $lecturePlanId = 'lecture-plan-' . uniqid();
    $lecturePlanPdfUrl = $lecturePlanPdfUrl ?? null;
@endphp
<div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
    <button type="button" class="btn btn-default btn-sm js-download-lecture-plan" data-table="{{ $lecturePlanId }}" data-batch="{{ e($batchName) }}">
        Download Lecture Plan (Excel)
    </button>
    @if($lecturePlanPdfUrl)
        <a href="{{ $lecturePlanPdfUrl }}" class="btn btn-primary btn-sm">Download Lecture Plan (PDF)</a>
    @endif
</div>
<div class="table-responsive">
    <table id="{{ $lecturePlanId }}" class="table table-striped table-bordered" style="margin-bottom:0;">
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Day</th>
                <th>Time</th>
                <th>Timezone</th>
                <th>Duration</th>
                <th>Title</th>
                <th>Description</th>
                <th>Status</th>
                <th style="min-width:140px;">Join</th>
                @if($isAdminView)
                    <th style="min-width:140px;">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse($sessions as $row)
                @php
                    $status = strtolower((string) ($row->status ?? 'scheduled'));
                    $duration = method_exists($row, 'durationMinutes')
                        ? $row->durationMinutes()
                        : (int) ($row->duration_minutes ?? 120);
                    $joinDisabled = method_exists($row, 'isJoinWindowOpen')
                        ? ! $row->isJoinWindowOpen()
                        : (in_array($status, ['cancelled', 'completed'], true));
                    $statusLabel = match ($status) {
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        default => 'Scheduled',
                    };
                    $statusClass = match ($status) {
                        'completed' => 'label-primary',
                        'cancelled' => 'label-danger',
                        default => 'label-success',
                    };
                    $title = trim((string) ($row->title ?? ''));
                    if ($title !== '' && strcasecmp($title, $batchName) === 0) {
                        $title = '';
                    }
                    $notes = trim((string) ($row->notes ?? ''));
                    $tzLabel = method_exists($row, 'timezoneLabel')
                        ? $row->timezoneLabel()
                        : (string) ($row->timezone_label ?? '—');
                    $icsRoute = $isAdminView
                        ? route('admin.class-schedules.ics', $row->id)
                        : route('user.class-schedules.item-ics', $row->id);
                @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $row->scheduled_at?->format('d M Y') ?? '—' }}</strong></td>
                    <td>{{ $row->scheduled_at?->format('l') ?? '—' }}</td>
                    <td>{{ $row->scheduled_at?->format('H:i') ?? '—' }}</td>
                    <td><small>{{ $tzLabel }}</small></td>
                    <td>{{ $duration }} min</td>
                    <td>{{ $title !== '' ? $title : '—' }}</td>
                    <td>{{ $notes !== '' ? $notes : '—' }}</td>
                    <td><span class="label {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td>
                        @if($row->zoho_link && ! $joinDisabled)
                            <a class="btn btn-primary btn-sm" href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;font-weight:700;">Join Now</a>
                        @elseif($row->zoho_link)
                            <button type="button" class="btn btn-default btn-sm" disabled>Join Now</button>
                        @else
                            <span class="label label-default">Link soon</span>
                        @endif
                        @unless($isAdminView)
                            <a class="btn btn-default btn-xs" href="{{ $icsRoute }}" style="margin-left:4px;">.ics</a>
                        @endunless
                    </td>
                    @if($isAdminView)
                        <td>
                            <a class="btn btn-xs btn-primary" href="{{ route('admin.class-schedules.edit', $row->id) }}">Edit</a>
                            <a class="btn btn-xs btn-default" href="{{ $icsRoute }}">.ics</a>
                            <form action="{{ route('admin.class-schedules.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this scheduled day?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $isAdminView ? 11 : 10 }}" class="text-center text-muted">No sessions in this batch yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>
(function () {
    if (window.__lecturePlanDownloadBound) return;
    window.__lecturePlanDownloadBound = true;
    function csvCell(value) {
        var text = (value || '').replace(/\r?\n/g, ' ').trim();
        if (/[",]/.test(text)) {
            return '"' + text.replace(/"/g, '""') + '"';
        }
        return text;
    }
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-download-lecture-plan');
        if (!btn) return;
        var table = document.getElementById(btn.getAttribute('data-table'));
        if (!table) return;
        var batch = btn.getAttribute('data-batch') || 'Lecture Plan';
        var rows = [];
        rows.push(['Batch name', batch]);
        rows.push(['#', 'Date', 'Day', 'Time', 'Timezone', 'Duration', 'Title', 'Description']);
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            var cells = tr.querySelectorAll('td');
            if (cells.length < 8) return;
            rows.push([
                cells[0].innerText,
                cells[1].innerText,
                cells[2].innerText,
                cells[3].innerText,
                cells[4].innerText,
                cells[5].innerText,
                cells[6].innerText,
                cells[7].innerText
            ]);
        });
        var csv = rows.map(function (row) {
            return row.map(csvCell).join(',');
        }).join('\r\n');
        var blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        var safe = batch.replace(/[\\/:*?"<>|]+/g, ' ').replace(/\s+/g, ' ').trim() || 'Lecture Plan';
        link.href = URL.createObjectURL(blob);
        link.download = safe + ' Lecture Plan.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
    });
})();
</script>
