@extends('user.layout.app')
@section('title', ($batch['batch_name'] ?? 'Batch schedule'))
@section('content')
@php
    $course = $batch['course'] ?? null;
    $hof = $batch['head_of_faculty'] ?? null;
    $ins = $batch['instructor'] ?? null;
    $courseHref = $course ? url('/course/' . ($course->slug ?: $course->id)) : null;
    $canManage = !empty($canManageSessions);
    $batchId = $batch['batch_id'] ?? null;
@endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>
            @if(!empty($batch['batch_code']))
                <span class="label label-primary">{{ $batch['batch_code'] }}</span>
            @endif
            {{ $batch['batch_name'] }}
        </h2>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
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
    <div class="ibox">
        <div class="ibox-content">
            <p class="help-block" style="margin-top:0;">
                @if($courseHref)
                    <a href="{{ $courseHref }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $course->title }}</strong></a>
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
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Calendar</h5></div>
        <div class="ibox-content">
            @include('admin.study-materials.schedules._calendar', ['calendarEvents' => $calendarEvents])
        </div>
    </div>
</div>
@endsection
