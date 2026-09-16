@extends('user.layout.app')
@section('title', 'Class Schedule')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>My Class Schedule</h2>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('user.class-schedules.ics') }}" class="btn btn-primary" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Add to Zoho Calendar</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox">
        <div class="ibox-title"><h5>Calendar</h5></div>
        <div class="ibox-content">
            <p class="help-block">Download the calendar file and import it into Zoho Calendar (Settings → Import / Subscribe).</p>
            @include('admin.study-materials.schedules._calendar', ['calendarEvents' => $calendarEvents])
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Batch list</h5></div>
        <div class="ibox-content">
            @forelse($batches as $batch)
                <div style="padding:16px 0;border-bottom:1px solid #eee;">
                    <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
                        <div>
                            <strong style="font-size:16px;">{{ $batch['batch_name'] }}</strong>
                            <div class="text-muted" style="margin-top:4px;">
                                {{ $batch['course']->title ?? '' }}
                                · Instructor: <strong>{{ $batch['instructor']->name ?? '—' }}</strong>
                            </div>
                        </div>
                    </div>
                    <ul class="list-unstyled" style="margin:12px 0 0;">
                        @foreach($batch['sessions'] as $row)
                            <li style="display:flex;justify-content:space-between;gap:16px;align-items:center;padding:10px 0;border-top:1px solid #f5f5f5;flex-wrap:wrap;">
                                <div>
                                    <strong>{{ $row->scheduled_at?->format('d M Y H:i') }}</strong>
                                    · {{ $row->durationMinutes() }} min
                                    @if($row->title && $row->title !== $batch['batch_name'])
                                        · {{ $row->title }}
                                    @endif
                                </div>
                                <div>
                                    <a class="btn btn-default btn-sm" href="{{ route('user.class-schedules.item-ics', $row->id) }}">.ics</a>
                                    @if($row->zoho_link)
                                        <a class="btn btn-primary btn-sm" href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Join Now</a>
                                    @else
                                        <span class="label label-default">Link soon</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-center text-muted">No batches assigned yet.</p>
            @endforelse
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Upcoming classes</h5></div>
        <div class="ibox-content">
            @forelse($schedules as $row)
                <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;padding:14px 0;border-bottom:1px solid #eee;">
                    <div>
                        <strong>{{ $row->title ?: $row->batch_name }}</strong>
                        <div class="text-muted">
                            {{ $row->scheduled_at?->format('d M Y H:i') }}
                            · {{ $row->durationMinutes() }} min
                            · {{ $row->course->title ?? '' }}
                            · Instructor: {{ $row->instructor->name ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <a class="btn btn-default btn-sm" href="{{ route('user.class-schedules.item-ics', $row->id) }}">.ics</a>
                        @if($row->zoho_link)
                            <a class="btn btn-primary btn-sm" href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Join Now</a>
                        @else
                            <span class="label label-default">Link soon</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center text-muted">No upcoming classes assigned.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
