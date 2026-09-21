@php
    $isAdminView = !empty($isAdminView);
    $batchName = (string) ($batch['batch_name'] ?? '');
    $sessions = $batch['sessions'] ?? collect();
@endphp
<div class="table-responsive">
    <table class="table table-striped table-bordered" style="margin-bottom:0;">
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
                        : (int) ($row->duration_minutes ?? 60);
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
                        @elseif($row->zoho_link && $joinDisabled)
                            <button type="button" class="btn btn-default btn-sm" disabled>Join Now</button>
                            @if($status === 'cancelled')
                                <span class="label label-danger" style="margin-left:4px;">Cancelled</span>
                            @elseif($status === 'completed')
                                <span class="label label-primary" style="margin-left:4px;">Completed</span>
                            @else
                                <span class="label label-default" style="margin-left:4px;">Ended</span>
                            @endif
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
