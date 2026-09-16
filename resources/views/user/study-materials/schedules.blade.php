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
                    <a href="#schedule-batches" aria-controls="schedule-batches" role="tab" data-toggle="tab">Batch &amp; Schedule</a>
                </li>
                <li role="presentation">
                    <a href="#schedule-calendar-tab" aria-controls="schedule-calendar-tab" role="tab" data-toggle="tab">Calendar</a>
                </li>
            </ul>

            <div class="tab-content">
                <div role="tabpanel" class="tab-pane active" id="schedule-batches">
                    <p class="help-block" style="margin-top:0;">All your classes by batch — full dates and Join in one table.</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Batch</th>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Course</th>
                                    <th>Head of Faculty</th>
                                    <th>Instructor</th>
                                    <th>Duration</th>
                                    <th style="min-width:120px;">Join</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $hasRows = false; @endphp
                                @foreach($batches as $batch)
                                    @foreach($batch['sessions'] as $row)
                                        @php $hasRows = true; @endphp
                                        <tr>
                                            <td><strong>{{ $batch['batch_name'] }}</strong></td>
                                            <td>{{ $row->scheduled_at?->format('d M Y') ?? '—' }}</td>
                                            <td>{{ $row->scheduled_at?->format('l') ?? '—' }}</td>
                                            <td>{{ $row->scheduled_at?->format('H:i') ?? '—' }}</td>
                                            <td>{{ $batch['course']->title ?? ($row->course->title ?? '—') }}</td>
                                            <td>{{ $row->headOfFaculty->name ?? ($batch['head_of_faculty']->name ?? '—') }}</td>
                                            <td>{{ $row->instructor->name ?? ($batch['instructor']->name ?? '—') }}</td>
                                            <td>{{ $row->durationMinutes() }} min</td>
                                            <td>
                                                @if($row->zoho_link)
                                                    <a class="btn btn-primary btn-sm" href="{{ $row->zoho_link }}" target="_blank" rel="noopener" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;font-weight:700;">Join Now</a>
                                                @else
                                                    <span class="label label-default">Link soon</span>
                                                @endif
                                                <a class="btn btn-default btn-xs" href="{{ route('user.class-schedules.item-ics', $row->id) }}" style="margin-left:4px;">.ics</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                @unless($hasRows)
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No batch schedules assigned yet.</td>
                                    </tr>
                                @endunless
                            </tbody>
                        </table>
                    </div>
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
