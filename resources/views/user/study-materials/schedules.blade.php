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
                            · {{ $row->instructor->name ?? '' }}
                        </div>
                    </div>
                    <div>
                        <a class="btn btn-default btn-sm" href="{{ route('user.class-schedules.item-ics', $row->id) }}">.ics</a>
                        @if($row->zoho_link)
                            <a class="btn btn-primary btn-sm" href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Join Zoho</a>
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
