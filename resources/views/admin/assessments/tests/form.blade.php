@extends(lms_portal_layout())
@section('title', ($assessment->exists ? 'Edit' : 'Create') . ' Assessment')
@section('content')
@include('admin.assessments.partials.limited-editor-assets')
@include('admin.assessments.partials.searchable-select-assets')
@php
    $descriptionMax = 5000;
    $canCreate = $canCreate ?? true;
    $canAssign = $canAssign ?? true;
@endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>
            {{ $assessment->exists ? 'Edit' : 'Create' }} assessment
            @if($assessment->exists)
                <small class="text-muted">ID {{ $assessment->id }}</small>
            @endif
        </h2>
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

            <fieldset @disabled(! $canCreate)>
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $assessment->title) }}" @required($canCreate) @readonly(! $canCreate)>
                </div>
                <div class="col-md-4 form-group">
                    <label>Kind *</label>
                    <select name="kind" class="form-control lms-searchable" data-placeholder="Type to find kind" data-allow-clear="0" @required($canCreate) @disabled(! $canCreate)>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected(old('kind', $assessment->kind) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(! $canCreate)
                        <input type="hidden" name="kind" value="{{ old('kind', $assessment->kind) }}">
                    @endif
                </div>
                <div class="col-md-12 form-group">
                    <label>Description <span class="lms-char-hint">(max {{ number_format($descriptionMax) }} characters)</span></label>
                    <textarea name="description" class="form-control" rows="3" data-max="{{ $descriptionMax }}" maxlength="{{ $descriptionMax }}" @readonly(! $canCreate)>{{ old('description', $assessment->description) }}</textarea>
                </div>
                <div class="col-md-4 form-group">
                    <label>Course{{ !empty($courseRequired) ? ' *' : '' }}</label>
                    <select name="course_id" class="form-control lms-searchable" data-placeholder="Type to find course" @if(!empty($courseRequired) && $canCreate) required @endif @disabled(! $canCreate)>
                        <option value="">—</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('course_id', $assessment->course_id) === (string) $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @if(! $canCreate)
                        <input type="hidden" name="course_id" value="{{ old('course_id', $assessment->course_id) }}">
                    @endif
                    @if(!empty($courseRequired))
                        <p class="help-block m-b-none">Instructors may only create Practice/Mock for courses Admin assigned them.</p>
                    @endif
                </div>
                <div class="col-md-2 form-group">
                    <label>Duration (min)</label>
                    <input type="number" min="1" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $assessment->duration_minutes) }}" placeholder="Timed" @readonly(! $canCreate)>
                </div>
                <div class="col-md-2 form-group">
                    <label>Pass %</label>
                    <input type="number" step="0.1" min="0" max="100" name="pass_percent" class="form-control" value="{{ old('pass_percent', $assessment->pass_percent) }}" @readonly(! $canCreate)>
                </div>
                <div class="col-md-2 form-group">
                    <label>Max attempts</label>
                    <input type="number" min="1" name="max_attempts" class="form-control" value="{{ old('max_attempts', $assessment->max_attempts) }}" @readonly(! $canCreate)>
                </div>
                <div class="col-md-2 form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control" @required($canCreate) @disabled(! $canCreate)>
                        <option value="draft" @selected(old('status', $assessment->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $assessment->status) === 'published')>Published</option>
                        <option value="disabled" @selected(old('status', $assessment->status) === 'disabled')>Disabled</option>
                    </select>
                    @if(! $canCreate)
                        <input type="hidden" name="status" value="{{ old('status', $assessment->status) }}">
                    @endif
                </div>
                <div class="col-md-4 form-group">
                    <label>Results mode *</label>
                    <select name="results_mode" class="form-control" @required($canCreate) @disabled(! $canCreate)>
                        <option value="after_publish" @selected(old('results_mode', $assessment->results_mode) === 'after_publish')>Publish after marking</option>
                        <option value="instant_mcq" @selected(old('results_mode', $assessment->results_mode) === 'instant_mcq')>Instant (MCQ-only)</option>
                    </select>
                    @if(! $canCreate)
                        <input type="hidden" name="results_mode" value="{{ old('results_mode', $assessment->results_mode) }}">
                    @endif
                </div>
                <div class="col-md-4 form-group" style="padding-top:28px;">
                    <label><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $assessment->shuffle_questions)) @disabled(! $canCreate)> Shuffle questions</label>
                </div>
            </div>

            <h4>Questions from QBANK</h4>
            <select name="question_ids[]" class="form-control lms-searchable" data-placeholder="Type to find questions" multiple @disabled(! $canCreate)>
                @foreach($questions as $q)
                    <option value="{{ $q->id }}" @selected(collect($selectedQuestionIds)->contains($q->id))>
                        #{{ $q->id }} [{{ strtoupper($q->type) }}] {{ \Illuminate\Support\Str::limit(strip_tags($q->stem), 80) }} ({{ $q->max_marks }} marks)
                    </option>
                @endforeach
            </select>
            <p class="help-block">Type to find and multi-select. Add questions in Question Bank first.</p>
            </fieldset>

            <h4>Assign to batch and/or users</h4>
            @if(! $canAssign)
                <p class="text-muted">You do not have Assign permission. Current assignments are shown below (read-only).</p>
            @else
                <p class="help-block">Students and staff (Content Writers, Accountants, Instructors) can be assigned.</p>
            @endif
            <fieldset @disabled(! $canAssign)>
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
                <div class="col-md-3 form-group">
                    <label>Available from</label>
                    <input type="datetime-local" name="available_from" class="form-control" value="{{ old('available_from', optional($assessment->assignments->first()?->available_from)->format('Y-m-d\TH:i')) }}">
                    <p class="help-block m-b-none">Leave blank to open immediately after assign.</p>
                </div>
                <div class="col-md-3 form-group">
                    <label>Available until</label>
                    <input type="datetime-local" name="available_until" class="form-control" value="{{ old('available_until', optional($assessment->assignments->first()?->available_until)->format('Y-m-d\TH:i')) }}">
                    <p class="help-block m-b-none">Must be after “from”. Leave blank for no end time.</p>
                </div>
            </div>
            </fieldset>

            @if($canCreate || $canAssign)
                <button type="submit" class="btn btn-primary">Save</button>
            @endif
            <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">Back</a>
            @if($assessment->exists)
                <a href="{{ route('admin.assessments.tests.attempts', $assessment->id) }}" class="btn btn-default">View attempts</a>
            @endif
        </form>
    </div></div>
</div>
@endsection
