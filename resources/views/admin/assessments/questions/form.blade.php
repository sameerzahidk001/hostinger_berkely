@extends(lms_portal_layout())
@section('title', ($question->exists ? 'Edit' : 'Add') . ' Question')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $question->exists ? 'Edit' : 'Add' }} question</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.questions.index') }}">Question Bank</a></li>
            <li class="active"><strong>{{ $question->exists ? 'Edit' : 'Add' }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox"><div class="ibox-content">
        <form method="POST" action="{{ $question->exists ? route('admin.assessments.questions.update', $question->id) : route('admin.assessments.questions.store') }}">
            @csrf
            @if($question->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Type *</label>
                    <select name="type" id="q_type" class="form-control" required>
                        @foreach($types as $k => $label)
                            <option value="{{ $k }}" @selected(old('type', $question->type) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Topic</label>
                    <input type="text" name="topic" class="form-control" value="{{ old('topic', $question->topic) }}">
                </div>
                <div class="col-md-3 form-group">
                    <label>Course</label>
                    <select name="course_id" class="form-control">
                        <option value="">—</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('course_id', $question->course_id) === (string) $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label>Max marks *</label>
                    <input type="number" step="0.1" min="0.1" name="max_marks" class="form-control" value="{{ old('max_marks', $question->max_marks ?: 1) }}" required>
                </div>
                <div class="col-md-12 form-group">
                    <label>Question stem *</label>
                    <textarea name="stem" class="form-control" rows="4" required>{{ old('stem', $question->stem) }}</textarea>
                </div>
                <div class="col-md-12 form-group">
                    <label>Explanation / model answer notes</label>
                    <textarea name="explanation" class="form-control" rows="3">{{ old('explanation', $question->explanation) }}</textarea>
                </div>
                <div class="col-md-3 form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="active" @selected(old('status', $question->status ?: 'active') === 'active')>Active</option>
                        <option value="disabled" @selected(old('status', $question->status) === 'disabled')>Disabled</option>
                    </select>
                </div>
            </div>

            <div id="mcq_options" style="{{ old('type', $question->type) === 'mcq' ? '' : 'display:none;' }}">
                <h4>MCQ options</h4>
                @php
                    $opts = old('options', $question->options() ?: [['text'=>''],['text'=>''],['text'=>''],['text'=>'']]);
                    $correct = (int) old('correct_option', collect($question->options())->search(fn ($o) => !empty($o['is_correct'])) ?: 0);
                @endphp
                @foreach($opts as $i => $opt)
                    <div class="form-group">
                        <label>
                            <input type="radio" name="correct_option" value="{{ $i }}" @checked($correct === (int)$i)> Correct
                        </label>
                        <input type="text" name="options[{{ $i }}][text]" class="form-control" value="{{ $opt['text'] ?? '' }}" placeholder="Option {{ $i + 1 }}">
                    </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.assessments.questions.index') }}" class="btn btn-default">Cancel</a>
        </form>
    </div></div>
</div>
@endsection
@push('script')
<script>
$('#q_type').on('change', function () {
    $('#mcq_options').toggle($(this).val() === 'mcq');
});
</script>
@endpush
