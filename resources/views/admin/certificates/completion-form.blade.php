@extends('admin.layout.app')
@section('title', 'Issue Certificate')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Issue Certificate</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.certificates.completions.index') }}">Completions</a></li>
            <li class="active"><strong>Issue Certificate</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @if($templates->isEmpty())
        <div class="alert alert-warning">
            No active certificate templates.
            <a href="{{ route('admin.certificates.templates.create') }}">Create a template</a> first.
        </div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.certificates.completions.store') }}" id="markCompleteForm">
                @csrf
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Issue for *</label>
                        <div>
                            <label class="radio-inline" style="margin-right:18px;">
                                <input type="radio" name="target_type" value="student" @checked(old('target_type', 'student') === 'student')> Single student
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="target_type" value="batch" @checked(old('target_type') === 'batch')> Whole batch
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6 form-group js-target-student">
                        <label>Student *</label>
                        <select name="user_id" class="form-control">
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                @php
                                    $label = $student->name ?: trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
                                    if ($label === '') { $label = 'User #'.$student->id; }
                                    $sid = method_exists($student, 'studentIdValue') ? $student->studentIdValue() : ($student->student_id ?? $student->student_number ?? '');
                                    if ($sid) { $label .= ' — '.$sid; }
                                @endphp
                                <option value="{{ $student->id }}" @selected((string)old('user_id') === (string)$student->id)>
                                    {{ $label }} @if($student->email) ({{ $student->email }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 form-group js-target-student">
                        <label>Course *</label>
                        <select name="course_id" class="form-control" id="courseSelect">
                            <option value="">Select course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((string)old('course_id') === (string)$course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-12 form-group js-target-batch" style="display:none;">
                        <label>Batch *</label>
                        <select name="batch_id" class="form-control" id="batchSelect">
                            <option value="">Select batch</option>
                            @foreach($batches as $batch)
                                @php
                                    $bLabel = trim(($batch->code ? $batch->code.' — ' : '').($batch->name ?: 'Batch #'.$batch->id));
                                    if ($batch->course) { $bLabel .= ' ('.$batch->course->title.')'; }
                                @endphp
                                <option value="{{ $batch->id }}"
                                    data-course-id="{{ $batch->course_id }}"
                                    @selected((string)old('batch_id') === (string)$batch->id)>
                                    {{ $bLabel }}
                                </option>
                            @endforeach
                        </select>
                        <p class="help-block text-muted m-b-none">Every student currently on this batch roster will be marked complete for the batch course.</p>
                    </div>

                    <div class="col-md-6 form-group">
                        <label>Awarded / completion date</label>
                        <input type="date" name="completed_at" class="form-control" value="{{ old('completed_at', now()->toDateString()) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course subtitle (optional)</label>
                        <input type="text" name="course_subtitle" class="form-control" value="{{ old('course_subtitle') }}" placeholder="e.g. (CIA Challenge Exam Training)">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Programme duration</label>
                        <input type="text" name="programme_duration" class="form-control" value="{{ old('programme_duration') }}" placeholder="e.g. 6 Months">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>CPD hours</label>
                        <input type="text" name="cpd_hours" class="form-control" value="{{ old('cpd_hours') }}" placeholder="e.g. 30">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Certificate template *</label>
                        <select name="template_id" class="form-control" required @disabled($templates->isEmpty())>
                            <option value="">Select template</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" @selected((string)old('template_id', optional($templates->firstWhere('is_default', true))->id) === (string)$tpl->id)>
                                    {{ $tpl->name }}@if($tpl->is_default) (default)@endif
                                </option>
                            @endforeach
                        </select>
                        <p class="help-block m-b-none">
                            <a href="{{ route('admin.certificates.templates.index') }}" target="_blank" rel="noopener">Manage templates</a>
                        </p>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Notes (internal)</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" maxlength="2000">
                    </div>

                    <div class="col-md-12 form-group">
                        <label class="checkbox-inline" style="display:block;margin-bottom:8px;">
                            <input type="checkbox" name="issue_now" value="1" @checked(old('issue_now', true))>
                            Issue certificate now (PDF + QR) using the selected template
                        </label>
                        <label class="checkbox-inline" style="display:block;">
                            <input type="checkbox" name="send_email" value="1" @checked(old('send_email', true))>
                            Email PDF to the student(s)
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" @disabled($templates->isEmpty())>Issue certificate</button>
                <a href="{{ route('admin.certificates.completions.index') }}" class="btn btn-white">Cancel</a>
            </form>
        </div>
    </div>
</div>
<script>
(function () {
    function syncTarget() {
        var type = (document.querySelector('input[name="target_type"]:checked') || {}).value || 'student';
        document.querySelectorAll('.js-target-student').forEach(function (el) {
            el.style.display = type === 'student' ? '' : 'none';
        });
        document.querySelectorAll('.js-target-batch').forEach(function (el) {
            el.style.display = type === 'batch' ? '' : 'none';
        });
    }
    document.querySelectorAll('input[name="target_type"]').forEach(function (el) {
        el.addEventListener('change', syncTarget);
    });
    syncTarget();
})();
</script>
@endsection
