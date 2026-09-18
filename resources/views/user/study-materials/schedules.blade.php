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
                    <p class="help-block" style="margin-top:0;">
                        All sessions stay visible (Scheduled, Completed, Cancelled). Only deleted sessions are removed. Past / cancelled / completed sessions have Join disabled.
                    </p>

                    @forelse($batches as $batch)
                        @php
                            $course = $batch['course'] ?? null;
                            $hof = $batch['head_of_faculty'] ?? null;
                            $ins = $batch['instructor'] ?? null;
                            $courseHref = $course
                                ? url('/course/' . ($course->slug ?: $course->id))
                                : null;
                        @endphp
                        <div class="panel panel-default" style="margin-bottom:20px;">
                            <div class="panel-heading">
                                <strong style="font-size:16px;">
                                    @if(!empty($batch['batch_code']))
                                        <span class="label label-primary" style="font-size:12px;vertical-align:middle;">{{ $batch['batch_code'] }}</span>
                                    @endif
                                    {{ $batch['batch_name'] }}
                                </strong>
                                <div class="text-muted" style="margin-top:4px;">
                                    @if($courseHref)
                                        <a href="{{ $courseHref }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $course->title }}</strong></a>
                                    @else
                                        —
                                    @endif
                                    · Head of Faculty:
                                    @if($hof && !empty($hof->id))
                                        <a href="{{ url('/instructor/' . $hof->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $hof->name }}</strong></a>
                                    @else
                                        <strong>—</strong>
                                    @endif
                                    · Instructor:
                                    @if($ins && !empty($ins->id))
                                        <a href="{{ url('/instructor/' . $ins->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $ins->name }}</strong></a>
                                    @else
                                        <strong>—</strong>
                                    @endif
                                    · {{ $batch['sessions']->count() }} session{{ $batch['sessions']->count() === 1 ? '' : 's' }}
                                </div>
                            </div>
                            <div class="panel-body" style="padding:0;">
                                @include('admin.study-materials.schedules._sessions_table', ['batch' => $batch, 'isAdminView' => false])
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
