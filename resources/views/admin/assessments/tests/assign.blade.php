@extends(lms_portal_layout())
@section('title', 'Assign test')
@section('content')
@include('admin.assessments.partials.searchable-select-assets')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Assign: {{ $assessment->title }} <small class="text-muted">ID {{ $assessment->id }}</small></h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">Assessments</a></li>
            <li class="active"><strong>Assign</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox"><div class="ibox-content">
        <p class="help-block">
            Choose a batch and/or individual users. Optional marking instructor: if set, that instructor (or Admin) can mark attempts; otherwise Admin / course instructors can mark.
        </p>
        <form method="POST" action="{{ route('admin.assessments.tests.assign.save', $assessment->id) }}">
            @csrf
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Batches</label>
                    <select name="batch_ids[]" class="form-control lms-searchable" data-placeholder="Type to find batch" multiple>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" @selected(collect($selectedBatchIds)->contains($batch->id))>{{ $batch->code }} — {{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>Individual users (students &amp; staff)</label>
                    <select name="student_ids[]" class="form-control lms-searchable" data-placeholder="Type to find user" multiple>
                        @foreach($students as $student)
                            @php
                                $roleLabel = $student->roles->pluck('name')->map(fn ($n) => ucwords(str_replace(['_', '-'], ' ', $n)))->implode(', ');
                            @endphp
                            <option value="{{ $student->id }}" @selected(collect($selectedStudentIds)->contains($student->id))>
                                {{ $student->name }} ({{ $student->email }}){{ $roleLabel ? ' — '.$roleLabel : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>Assessor <span class="text-muted">(who marks the exam, optional)</span></label>
                    <select name="marker_user_id" class="form-control lms-searchable" data-placeholder="Type to find assessor">
                        <option value="">— Admin / course instructors —</option>
                        @foreach($instructors as $ins)
                            <option value="{{ $ins->id }}" @selected((string) $selectedMarkerId === (string) $ins->id)>{{ $ins->name }} ({{ $ins->email }})</option>
                        @endforeach
                    </select>
                </div>
                @php
                    $firstAsg = $assessment->assignments->first();
                    $tzForInput = old('timezone', $selectedTimezone ?? config('app.timezone', 'Asia/Dubai'));
                    $fromForInput = old('available_from');
                    if ($fromForInput === null && $firstAsg?->available_from) {
                        $fromForInput = filled($firstAsg->timezone)
                            ? $firstAsg->available_from->copy()->timezone($firstAsg->timezoneName())->format('Y-m-d\TH:i')
                            : $firstAsg->available_from->format('Y-m-d\TH:i');
                    }
                @endphp
                <div class="col-md-3 form-group">
                    <label>Exam Appointment Start</label>
                    <input type="datetime-local" name="available_from" class="form-control" value="{{ $fromForInput }}">
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">
                        Local time for the timezone below. Window ends at start + exam duration
                        ({{ $assessment->duration_minutes ? $assessment->duration_minutes . ' min' : 'set duration on the test' }}).
                        Student may start anytime inside that window.
                    </p>
                </div>
                <div class="col-md-3 form-group">
                    <label>Timezone *</label>
                    <select name="timezone" class="form-control lms-searchable" data-placeholder="Type to find timezone" data-allow-clear="0" required>
                        @foreach(($timezoneOptions ?? \App\Models\LmsAssessmentAssignment::timezoneOptions()) as $tzValue => $tzLabel)
                            <option value="{{ $tzValue }}" @selected((string) $tzForInput === (string) $tzValue)>{{ $tzLabel }}</option>
                        @endforeach
                    </select>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">Shown with appointment and attempt times on all lists and PDFs.</p>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save assignments</button>
            <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">Back to tests</a>
            <a href="{{ route('admin.assessments.tests.assigned-users') }}" class="btn btn-default">Assigned users list</a>
        </form>
    </div></div>
</div>
@endsection
