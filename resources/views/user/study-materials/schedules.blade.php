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
        <div class="ibox-title"><h5>Your batches</h5></div>
        <div class="ibox-content">
            <p class="help-block" style="margin-top:0;">Open a batch to see its full session schedule (Scheduled, Completed, Cancelled).</p>
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
