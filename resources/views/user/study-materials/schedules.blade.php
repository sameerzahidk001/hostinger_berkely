@extends('user.layout.app')
@section('title', 'Class Schedule')
@section('content')
@php $isInstructor = !empty($isInstructor); @endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>{{ $isInstructor ? 'Class Schedule' : 'My Class Schedule' }}</h2>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        @if($isInstructor)
            <a href="{{ route('admin.class-schedules.index') }}" class="btn btn-default">Manage schedules</a>
            <a href="{{ route('admin.class-schedules.create') }}" class="btn btn-primary">Add session</a>
        @endif
        <a href="{{ route('user.class-schedules.ics') }}" class="btn btn-primary" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Add to Zoho Calendar</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    <ul class="nav nav-tabs" style="margin-bottom:0;">
        <li class="active"><a data-toggle="tab" href="#schedules-batches">Batches</a></li>
        <li><a data-toggle="tab" href="#schedules-calendar">Calendar</a></li>
    </ul>

    <div class="tab-content" style="background:#fff;border:1px solid #ddd;border-top:0;padding:16px;">
        <div id="schedules-batches" class="tab-pane active">
            <div class="ibox" style="margin-bottom:0;box-shadow:none;border:0;">
                <div class="ibox-content" style="border:0;padding:0;">
                    <p class="help-block" style="margin-top:0;">
                        @if($isInstructor)
                            Open a batch to edit sessions, or use <strong>Add session</strong> to create a new class.
                        @else
                            Open a batch to see its full session schedule (Scheduled, Completed, Cancelled).
                        @endif
                    </p>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Batch</th>
                                    <th>Course</th>
                                    <th>Head of Faculty</th>
                                    <th>Instructor</th>
                                    <th>Sessions</th>
                                    <th>Next class</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batches as $batch)
                                    @php
                                        $course = $batch['course'] ?? null;
                                        $hof = $batch['head_of_faculty'] ?? null;
                                        $ins = $batch['instructor'] ?? null;
                                        $courseHref = $course ? url('/course/' . ($course->slug ?: $course->id)) : null;
                                        $openKey = $batch['batch_id'] ?: ($batch['key'] ?? '');
                                    @endphp
                                    <tr>
                                        <td>
                                            @if(!empty($batch['batch_code']))
                                                <span class="label label-primary">{{ $batch['batch_code'] }}</span>
                                            @endif
                                            <strong>{{ $batch['batch_name'] }}</strong>
                                        </td>
                                        <td>
                                            @if($courseHref)
                                                <a href="{{ $courseHref }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $course->title }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($hof && !empty($hof->id))
                                                <a href="{{ url('/instructor/' . $hof->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $hof->name }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($ins && !empty($ins->id))
                                                <a href="{{ url('/instructor/' . $ins->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $ins->name }}</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $batch['session_count'] }}</td>
                                        <td>{{ optional($batch['next_at'])->format('d M Y H:i') ?: 'No upcoming' }}</td>
                                        <td>
                                            <a class="btn btn-primary btn-sm" href="{{ route('user.class-schedules.batch', $openKey) }}" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;font-weight:700;">Open schedule</a>
                                            @if($isInstructor && !empty($batch['batch_id']))
                                                <a class="btn btn-default btn-sm" href="{{ route('admin.class-schedules.create', ['batch_id' => $batch['batch_id']]) }}">Add session</a>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No batch schedules assigned yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="schedules-calendar" class="tab-pane">
            @include('admin.study-materials.schedules._calendar', ['calendarEvents' => $calendarEvents])
        </div>
    </div>
</div>
@endsection
