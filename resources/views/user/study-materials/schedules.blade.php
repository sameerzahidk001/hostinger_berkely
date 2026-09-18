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
        <div class="ibox-title"><h5>Schedule</h5></div>
        <div class="ibox-content">
            <ul class="nav nav-tabs" role="tablist" style="margin-bottom:20px;">
                <li class="active" role="presentation">
                    <a href="#schedule-batches" aria-controls="schedule-batches" role="tab" data-toggle="tab">Batch-wise Schedule</a>
                </li>
                <li role="presentation">
                    <a href="#schedule-calendar-tab" aria-controls="schedule-calendar-tab" role="tab" data-toggle="tab">Calendar</a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="schedule-batches">
                    <p class="help-block" style="margin-top:0;">Each batch shows its full list of scheduled sessions with dates and Join. Past sessions have Join disabled.</p>

                    @forelse($batches as $batch)
                        <div class="panel panel-default" style="margin-bottom:20px;">
                            <div class="panel-heading">
                                <strong style="font-size:16px;">
                                    @if(!empty($batch['batch_code']))
                                        <span class="label label-primary" style="font-size:12px;vertical-align:middle;">{{ $batch['batch_code'] }}</span>
                                    @endif
                                    {{ $batch['batch_name'] }}
                                </strong>
                                <div class="text-muted" style="margin-top:4px;">
                                    @if(!empty($batch['course']->id))
                                        <a href="{{ route('course.details', ['course' => $batch['course']->slug ?? $batch['course']->id]) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $batch['course']->title }}</strong></a>
                                    @else
                                        —
                                    @endif
                                    · Head of Faculty:
                                    @if(!empty($batch['head_of_faculty']->id))
                                        <a href="{{ url('/instructor/' . $batch['head_of_faculty']->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $batch['head_of_faculty']->name }}</strong></a>
                                    @else
                                        <strong>—</strong>
                                    @endif
                                    · Instructor:
                                    @if(!empty($batch['instructor']->id))
                                        <a href="{{ url('/instructor/' . $batch['instructor']->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $batch['instructor']->name }}</strong></a>
                                    @else
                                        <strong>—</strong>
                                    @endif
                                    · {{ $batch['sessions']->count() }} session{{ $batch['sessions']->count() === 1 ? '' : 's' }}
                                </div>
                            </div>
                            <div class="panel-body" style="padding:0;">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered" style="margin-bottom:0;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Date</th>
                                                <th>Day</th>
                                                <th>Time</th>
                                                <th>Duration</th>
                                                <th>Description</th>
                                                <th style="min-width:120px;">Join</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($batch['sessions'] as $row)
                                                @php
                                                    $endsAt = $row->scheduled_at
                                                        ? $row->scheduled_at->copy()->addMinutes((int) ($row->duration_minutes ?? 60))
                                                        : null;
                                                    $isPast = $endsAt && $endsAt->isPast();
                                                @endphp
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td><strong>{{ $row->scheduled_at?->format('d M Y') ?? '—' }}</strong></td>
                                                    <td>{{ $row->scheduled_at?->format('l') ?? '—' }}</td>
                                                    <td>{{ $row->scheduled_at?->format('H:i') ?? '—' }}</td>
                                                    <td>{{ $row->duration_minutes ?? 60 }} min</td>
                                                    <td>{{ $row->notes ?: '—' }}</td>
                                                    <td>
                                                        @if($row->zoho_link && ! $isPast)
                                                            <a class="btn btn-primary btn-sm" href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;font-weight:700;">Join Now</a>
                                                        @elseif($row->zoho_link && $isPast)
                                                            <button type="button" class="btn btn-default btn-sm" disabled title="This session date has passed">Join Now</button>
                                                            <span class="label label-default" style="margin-left:4px;">Ended</span>
                                                        @else
                                                            <span class="label label-default">Link soon</span>
                                                        @endif
                                                        <a class="btn btn-default btn-xs" href="{{ route('user.class-schedules.item-ics', $row->id) }}" style="margin-left:4px;">.ics</a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No sessions in this batch yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted" style="margin-bottom:0;">No batch schedules assigned yet.</p>
                    @endforelse
                </div>

                <div role="tabpanel" class="tab-pane" id="schedule-calendar-tab">
                    <p class="help-block">Download the calendar file and import it into Zoho Calendar (Settings → Import / Subscribe).</p>
                    @include('admin.study-materials.schedules._calendar', ['calendarEvents' => $calendarEvents])
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
