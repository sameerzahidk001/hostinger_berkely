@extends('admin.layout.app')
@section('title', ($batch->exists ? ($readOnly ? 'View' : 'Edit') : 'Create') . ' Batch')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $batch->exists ? ($readOnly ? 'View Batch' : 'Edit Batch') : 'Create Batch' }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.class-batches.index') }}">Batches</a></li>
            <li class="active"><strong>{{ $batch->exists ? ($readOnly ? 'View' : 'Edit') : 'Create' }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            @if($readOnly)
                <div class="alert alert-info">Instructors can view batch details and create schedules. Only Admin can edit batch / students.</div>
            @endif
            <form method="POST" action="{{ $batch->exists ? route('admin.class-batches.update', $batch->id) : route('admin.class-batches.store') }}">
                @csrf
                @if($batch->exists) @method('PUT') @endif
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Batch code</label>
                        <input type="text" class="form-control" value="{{ $batch->code ?: 'Auto (BAT-0001…)' }}" readonly disabled>
                    </div>
                    <div class="col-md-8 form-group">
                        <label>Batch name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $batch->name) }}" required @disabled($readOnly)>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course *</label>
                        <select name="course_id" id="course_id" class="form-control" required @disabled($readOnly)>
                            <option value="">Select course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $batch->course_id) == $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Head of the Faculty</label>
                        <select name="head_of_faculty_id" class="form-control" @disabled($readOnly)>
                            <option value="">—</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(old('head_of_faculty_id', $batch->head_of_faculty_id) == $ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Instructors (multi-select)</label>
                        <select name="instructor_ids[]" class="form-control" multiple size="8" @disabled($readOnly)>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(collect($selectedInstructorIds)->contains($ins->id))>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                        <span class="help-block">Hold Ctrl/Cmd to select multiple.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Students {{ $isAdmin ? '(Admin assigns)' : '(view only)' }}</label>
                        <select name="student_ids[]" id="student_ids" class="form-control" multiple size="8" @disabled($readOnly)>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(collect($selectedStudentIds)->contains($student->id))>
                                    {{ $student->name }} ({{ $student->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if($isAdmin)
                    <div class="col-md-4 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" @selected(old('status', $batch->status ?: 'active') === 'active')>Active</option>
                            <option value="disabled" @selected(old('status', $batch->status) === 'disabled')>Disabled</option>
                        </select>
                    </div>
                    @endif
                </div>
                @unless($readOnly)
                    <button type="submit" class="btn btn-primary">Save Batch</button>
                @endunless
                <a href="{{ route('admin.class-batches.index') }}" class="btn btn-default">Back</a>
                @if($batch->exists)
                    <a href="{{ route('admin.class-schedules.create', ['batch_id' => $batch->id]) }}" class="btn btn-primary">Create schedule for this batch</a>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>.select2-container { width: 100% !important; }</style>
@endpush
@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('#course_id').select2({ placeholder: 'Type to find the course', allowClear: true, width: '100%' });
    $('#student_ids').select2({ placeholder: 'Type to find students', width: '100%', closeOnSelect: false });
});
</script>
@endpush
