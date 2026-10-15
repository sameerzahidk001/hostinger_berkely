@extends(lms_portal_layout())
@section('title', ($assessment->exists ? 'Edit' : 'Create') . ' Assessment')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $assessment->exists ? 'Edit' : 'Create' }} assessment</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">Assessments</a></li>
            <li class="active"><strong>{{ $assessment->exists ? 'Edit' : 'Create' }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox"><div class="ibox-content">
        <form method="POST" action="{{ $assessment->exists ? route('admin.assessments.tests.update', $assessment->id) : route('admin.assessments.tests.store') }}">
            @csrf
            @if($assessment->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $assessment->title) }}" required>
                </div>
                <div class="col-md-4 form-group">
                    <label>Kind *</label>
                    <select name="kind" class="form-control" required>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected(old('kind', $assessment->kind) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $assessment->description) }}</textarea>
                </div>
                <div class="col-md-4 form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control">
                        <option value="">—</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('course_id', $assessment->course_id) === (string) $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Duration (min)</label>
                    <input type="number" min="1" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $assessment->duration_minutes) }}" placeholder="Timed">
                </div>
                <div class="col-md-2 form-group">
                    <label>Pass %</label>
                    <input type="number" step="0.1" min="0" max="100" name="pass_percent" class="form-control" value="{{ old('pass_percent', $assessment->pass_percent) }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>Max attempts</label>
                    <input type="number" min="1" name="max_attempts" class="form-control" value="{{ old('max_attempts', $assessment->max_attempts) }}">
                </div>
                <div class="col-md-2 form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" required>
                        <option value="draft" @selected(old('status', $assessment->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $assessment->status) === 'published')>Published</option>
                        <option value="disabled" @selected(old('status', $assessment->status) === 'disabled')>Disabled</option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label>Results mode *</label>
                    <select name="results_mode" class="form-control" required>
                        <option value="after_publish" @selected(old('results_mode', $assessment->results_mode) === 'after_publish')>Publish after marking</option>
                        <option value="instant_mcq" @selected(old('results_mode', $assessment->results_mode) === 'instant_mcq')>Instant (MCQ-only)</option>
                    </select>
                </div>
                <div class="col-md-4 form-group" style="padding-top:28px;">
                    <label><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $assessment->shuffle_questions))> Shuffle questions</label>
                </div>
            </div>

            <h4>Questions from QBANK</h4>
            <select name="question_ids[]" class="form-control" multiple size="8">
                @foreach($questions as $q)
                    <option value="{{ $q->id }}" @selected(collect($selectedQuestionIds)->contains($q->id))>
                        [{{ strtoupper($q->type) }}] {{ \Illuminate\Support\Str::limit(strip_tags($q->stem), 80) }} ({{ $q->max_marks }} marks)
                    </option>
                @endforeach
            </select>
            <p class="help-block">Hold Ctrl/Cmd to multi-select. Add questions in Question Bank first.</p>

            <h4>Assign to batch and/or student</h4>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Batches</label>
                    <select name="batch_ids[]" class="form-control" multiple size="6">
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" @selected(collect($selectedBatchIds)->contains($batch->id))>{{ $batch->code }} — {{ $batch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>Individual students</label>
                    <select name="student_ids[]" class="form-control" multiple size="6">
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" @selected(collect($selectedStudentIds)->contains($student->id))>{{ $student->name }} ({{ $student->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Available from</label>
                    <input type="datetime-local" name="available_from" class="form-control" value="{{ old('available_from', optional($assessment->assignments->first()?->available_from)->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Available until</label>
                    <input type="datetime-local" name="available_until" class="form-control" value="{{ old('available_until', optional($assessment->assignments->first()?->available_until)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">Back</a>
            @if($assessment->exists)
                <a href="{{ route('admin.assessments.tests.attempts', $assessment->id) }}" class="btn btn-default">View attempts</a>
            @endif
        </form>
    </div></div>
</div>
@endsection
