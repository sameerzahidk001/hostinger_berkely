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
                    <a href="#schedule-upcoming" aria-controls="schedule-upcoming" role="tab" data-toggle="tab">Upcoming</a>
                </li>
                <li role="presentation">
                    <a href="#schedule-batches" aria-controls="schedule-batches" role="tab" data-toggle="tab">Your Batch</a>
                </li>
                <li role="presentation">
                    <a href="#schedule-calendar-tab" aria-controls="schedule-calendar-tab" role="tab" data-toggle="tab">Calendar</a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="schedule-upcoming">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Batch / Title</th>
                                    <th>Course</th>
                                    <th>Head of Faculty</th>
                                    <th>Instructor</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schedules as $row)
                                    <tr>
                                        <td>{{ $row->scheduled_at?->format('d M Y H:i') }}</td>
                                        <td>{{ $row->title ?: $row->batch_name }}</td>
                                        <td>{{ $row->course->title ?? '—' }}</td>
                                        <td>{{ $row->headOfFaculty->name ?? '—' }}</td>
                                        <td>{{ $row->instructor->name ?? '—' }}</td>
                                        <td>{{ $row->durationMinutes() }} min</td>
                                        <td>
                                            <a class="btn btn-default btn-sm" href="{{ route('user.class-schedules.item-ics', $row->id) }}">.ics</a>
                                            @if($row->zoho_link)
                                                <a class="btn btn-primary btn-sm" href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Join Now</a>
                                            @else
                                                <span class="label label-default">Link soon</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">No upcoming classes assigned.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div role="tabpanel" class="tab-pane" id="schedule-batches">
                    @forelse($batches as $batch)
                        <div style="margin-bottom:22px;">
                            <div style="margin-bottom:10px;">
                                <strong style="font-size:16px;">{{ $batch['batch_name'] }}</strong>
                                <div class="text-muted" style="margin-top:4px;">
                                    {{ $batch['course']->title ?? '' }}
                                    · Head of Faculty: <strong>{{ $batch['head_of_faculty']->name ?? '—' }}</strong>
                                    · Instructor: <strong>{{ $batch['instructor']->name ?? '—' }}</strong>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>When</th>
                                            <th>Title</th>
                                            <th>Duration</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($batch['sessions'] as $row)
                                            <tr>
                                                <td>{{ $row->scheduled_at?->format('d M Y H:i') }}</td>
                                                <td>{{ $row->title && $row->title !== $batch['batch_name'] ? $row->title : '—' }}</td>
                                                <td>{{ $row->durationMinutes() }} min</td>
                                                <td>
                                                    <a class="btn btn-default btn-sm" href="{{ route('user.class-schedules.item-ics', $row->id) }}">.ics</a>
                                                    @if($row->zoho_link)
                                                        <a class="btn btn-primary btn-sm" href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Join Now</a>
                                                    @else
                                                        <span class="label label-default">Link soon</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">No batches assigned yet.</p>
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
