@extends('user.layout.app')
@section('title', ($batch['batch_name'] ?? 'Batch schedule'))
@push('style')
<style>
.page-heading.class-schedule-heading {
    background: #7a1212 !important;
    border-bottom-color: #5c0e0e !important;
    padding: 18px 20px;
    margin: 0;
}
.page-heading.class-schedule-heading h2 {
    color: #fff !important;
    font-size: 26px;
    font-weight: 700;
    margin: 8px 0;
}
.page-heading.class-schedule-heading .label-primary {
    background: #f8961f;
    color: #1e1e1e;
}
.page-heading.class-schedule-heading .btn-default {
    background: rgba(255,255,255,.15);
    border-color: rgba(255,255,255,.35);
    color: #fff;
}
.page-heading.class-schedule-heading .btn-default:hover {
    background: rgba(255,255,255,.28);
    color: #fff;
}
</style>
@endpush
@section('content')
@php
    $course = $batch['course'] ?? null;
    $hof = $batch['head_of_faculty'] ?? null;
    $ins = $batch['instructor'] ?? null;
    $courseHref = $course ? url('/course/' . ($course->slug ?: $course->id)) : null;
    $canManage = !empty($canManageSessions);
    $batchId = $batch['batch_id'] ?? null;
@endphp
<div class="row wrapper border-bottom page-heading class-schedule-heading">
    <div class="col-lg-8">
        <h2>
            @if(!empty($batch['batch_code']))
                <span class="label label-primary">{{ $batch['batch_code'] }}</span>
            @endif
            {{ $batch['batch_name'] }}
        </h2>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:12px;">
        <a href="{{ route('user.class-schedules.index') }}" class="btn btn-default">All batches</a>
        @if($canManage && $batchId)
            <a href="{{ route('admin.class-schedules.create', ['batch_id' => $batchId]) }}" class="btn btn-primary">Add session</a>
        @endif
        <a href="{{ route('user.class-schedules.ics') }}" class="btn btn-primary" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Add to Zoho Calendar</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <ul class="nav nav-tabs" style="margin-bottom:0;">
        <li class="active"><a data-toggle="tab" href="#batch-sessions">Sessions</a></li>
        <li><a data-toggle="tab" href="#batch-calendar">Calendar</a></li>
    </ul>

    <div class="tab-content" style="background:#fff;border:1px solid #ddd;border-top:0;padding:16px;">
        <div id="batch-sessions" class="tab-pane active">
            <p class="help-block" style="margin-top:0;">
                Course Name:
                @if($courseHref)
                    <a href="{{ $courseHref }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $course->title }}</strong></a>
                @else
                    <strong>—</strong>
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
                · {{ $batch['session_count'] }} session{{ $batch['session_count'] === 1 ? '' : 's' }}
            </p>
            <p class="help-block">All sessions stay visible (Scheduled, Completed, Cancelled). Only deleted sessions are removed.</p>
            @include('admin.study-materials.schedules._sessions_table', ['batch' => $batch, 'isAdminView' => $canManage])
        </div>
        <div id="batch-calendar" class="tab-pane">
            @include('admin.study-materials.schedules._calendar', ['calendarEvents' => $calendarEvents])
        </div>
    </div>
</div>
@endsection
