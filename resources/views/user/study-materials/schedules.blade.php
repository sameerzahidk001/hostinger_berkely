@extends('user.layout.app')
@section('title', 'Class Schedule')
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
    font-size: 28px;
    font-weight: 700;
    margin: 8px 0;
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
@php $isInstructor = !empty($isInstructor); @endphp
<div class="row wrapper border-bottom page-heading class-schedule-heading">
    <div class="col-lg-8">
        <h2>{{ $isInstructor ? 'Class Schedule' : 'My Class Schedule' }}</h2>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:12px;">
        @if($isInstructor)
            <a href="{{ route('admin.class-schedules.index') }}" class="btn btn-default">Manage schedules</a>
            <a href="{{ route('admin.class-schedules.create') }}" class="btn btn-primary">Add session</a>
        @endif
        <a href="{{ route('user.class-schedules.ics') }}" class="btn btn-primary" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Add to Zoho Calendar</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox">
        <div class="ibox-title"><h5>Your batches</h5></div>
        <div class="ibox-content">
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
                                <td>{{ optional($batch['next_at'])->format('d M Y H:i') ?: '—' }}</td>
                                <td>
                                    <a class="btn btn-primary btn-sm" href="{{ route('user.class-schedules.batch', $openKey) }}" style="background:#f8961f;border-color:#f8961f;color:#1e1e1e;">Open schedule</a>
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
@endsection
