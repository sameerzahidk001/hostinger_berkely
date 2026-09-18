@extends('admin.layout.app')
@section('title', ($batch['batch_code'] ?? '') . ' ' . ($batch['batch_name'] ?? 'Batch schedule'))
@section('content')
@php
    $course = $batch['course'] ?? null;
    $courseHref = $course ? url('/course/' . ($course->slug ?: $course->id)) : null;
    $hof = $batch['head_of_faculty'] ?? null;
    $ins = $batch['instructor'] ?? null;
    $studentCount = ($batch['students'] ?? collect())->count();
@endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>
            @if(!empty($batch['batch_code']))
                <span class="label label-primary">{{ $batch['batch_code'] }}</span>
            @endif
            {{ $batch['batch_name'] }}
        </h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.class-schedules.index') }}">Schedules</a></li>
            <li class="active"><strong>{{ $batch['batch_name'] }}</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.class-schedules.index') }}" class="btn btn-default">All batches</a>
        <a href="{{ route('admin.class-schedules.create', ['batch_id' => $batch['batch_id'] ?? $batchModel->id]) }}" class="btn btn-primary">Add session</a>
        <form action="{{ route('admin.class-schedules.batch.clear', $batchModel->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete ALL sessions in this batch? This cannot be undone.');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">Clear all sessions</button>
        </form>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Sessions</h5></div>
        <div class="ibox-content">
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
                ·
                <a href="{{ route('admin.class-batches.edit', $batchModel->id) }}#student_ids" style="color:#1c84c6;text-decoration:underline;">
                    <strong>{{ $studentCount }} student{{ $studentCount === 1 ? '' : 's' }}</strong>
                </a>
            </p>
            <p class="help-block">All sessions stay visible (Scheduled, Completed, Cancelled). Only deleted sessions are removed.</p>
            @include('admin.study-materials.schedules._sessions_table', ['batch' => $batch, 'isAdminView' => true])
        </div>
    </div>
</div>
@endsection
