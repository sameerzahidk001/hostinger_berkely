@extends(lms_portal_layout())
@section('title', ($question->exists ? 'Edit' : 'Add') . ' Question')
@section('content')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $question->exists ? 'Edit' : 'Add' }} question{{ $question->exists ? '' : 's' }}</h2>
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
        <form method="POST" action="{{ $question->exists ? route('admin.assessments.questions.update', $question->id) : route('admin.assessments.questions.store') }}" id="questionForm">
            @csrf
            @if($question->exists) @method('PUT') @endif

            @if($question->exists)
                {{-- Single edit --}}
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
                        <textarea name="stem" class="form-control text-editor" rows="4" required>{{ old('stem', $question->stem) }}</textarea>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Explanation / model answer notes</label>
                        <textarea name="explanation" class="form-control text-editor" rows="3">{{ old('explanation', $question->explanation) }}</textarea>
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
            @else
                {{-- Multi create: shared defaults + question blocks --}}
                <div class="row m-b-md">
                    <div class="col-md-4 form-group">
                        <label>Course (applies to all)</label>
                        <select name="course_id" class="form-control" id="shared_course">
                            <option value="">—</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Topic (default for all)</label>
                        <input type="text" name="topic" id="shared_topic" class="form-control" value="{{ old('topic') }}" placeholder="Optional shared topic">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="disabled" @selected(old('status') === 'disabled')>Disabled</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Total marks</label>
                        <div class="form-control" style="background:#f9f9f9;font-weight:700;" id="totalMarksDisplay">0</div>
                    </div>
                </div>

                <div id="questionBlocks">
                    @php
                        $oldQuestions = old('questions');
                        if (! is_array($oldQuestions) || $oldQuestions === []) {
                            $oldQuestions = [[
                                'type' => 'mcq',
                                'max_marks' => 1,
                                'stem' => '',
                                'explanation' => '',
                                'topic' => '',
                                'options' => [['text'=>''],['text'=>''],['text'=>''],['text'=>'']],
                                'correct_option' => 0,
                            ]];
                        }
                    @endphp
                    @foreach($oldQuestions as $qi => $row)
                        @include('admin.assessments.questions.partials.question-block', [
                            'index' => $qi,
                            'row' => $row,
                            'types' => $types,
                        ])
                    @endforeach
                </div>

                <p class="m-b-md">
                    <button type="button" class="btn btn-success btn-sm" id="addQuestionBlock">
                        <i class="fa fa-plus"></i> Add another question
                    </button>
                    <span class="text-muted m-l-sm">Each block is saved as its own question. Total marks updates live.</span>
                </p>
            @endif

            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.assessments.questions.index') }}" class="btn btn-default">Cancel</a>
        </form>
    </div></div>
</div>

@unless($question->exists)
<template id="questionBlockTemplate">
    @include('admin.assessments.questions.partials.question-block', [
        'index' => '__INDEX__',
        'row' => [
            'type' => 'mcq',
            'max_marks' => 1,
            'stem' => '',
            'explanation' => '',
            'topic' => '',
            'options' => [['text'=>''],['text'=>''],['text'=>''],['text'=>'']],
            'correct_option' => 0,
        ],
        'types' => $types,
    ])
</template>
@endunless
@endsection
@push('script')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script>
(function () {
    function initEditors(scope) {
        $(scope).find('textarea.text-editor').each(function () {
            if (this.classList.contains('summernote-initialized') || $(this).next('.note-editor').length) {
                return;
            }
            $(this).summernote({
                height: 160,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'italic', 'underline', 'clear']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link', 'picture']],
                    ['view', ['codeview']]
                ]
            });
            this.classList.add('summernote-initialized');
        });
    }

    function syncEditors(scope) {
        $(scope).find('textarea.text-editor').each(function () {
            if ($(this).next('.note-editor').length) {
                $(this).val($(this).summernote('code'));
            }
        });
    }

    function updateTotalMarks() {
        var total = 0;
        $('#questionBlocks').find('input.js-max-marks').each(function () {
            var n = parseFloat($(this).val());
            if (!isNaN(n)) total += n;
        });
        $('#totalMarksDisplay').text(total.toFixed(1));
    }

    function bindBlock($block) {
        $block.find('.js-q-type').on('change', function () {
            $block.find('.js-mcq-options').toggle($(this).val() === 'mcq');
        });
        $block.find('input.js-max-marks').on('input change', updateTotalMarks);
        $block.find('.js-remove-block').on('click', function () {
            if ($('#questionBlocks .question-block').length <= 1) {
                alert('Keep at least one question.');
                return;
            }
            $block.find('textarea.text-editor').each(function () {
                if ($(this).next('.note-editor').length) {
                    try { $(this).summernote('destroy'); } catch (e) {}
                }
            });
            $block.remove();
            reindexBlocks();
            updateTotalMarks();
        });
    }

    function reindexBlocks() {
        $('#questionBlocks .question-block').each(function (i) {
            $(this).attr('data-index', i);
            $(this).find('.js-block-label').text('Question ' + (i + 1));
            $(this).find('[name]').each(function () {
                var name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/questions\[(?:\d+|__INDEX__)\]/, 'questions[' + i + ']'));
            });
        });
    }

    initEditors(document);
    $('#q_type').on('change', function () {
        $('#mcq_options').toggle($(this).val() === 'mcq');
    });

    $('#questionBlocks .question-block').each(function () {
        bindBlock($(this));
    });
    updateTotalMarks();

    $('#addQuestionBlock').on('click', function () {
        var tpl = document.getElementById('questionBlockTemplate');
        if (!tpl) return;
        var html = tpl.innerHTML.replace(/__INDEX__/g, String(Date.now()));
        var $block = $(html);
        $('#questionBlocks').append($block);
        reindexBlocks();
        initEditors($block);
        bindBlock($block);
        var topic = $('#shared_topic').val();
        if (topic) {
            $block.find('.js-topic').val(topic);
        }
        updateTotalMarks();
    });

    $('#questionForm').on('submit', function () {
        syncEditors(this);
    });
})();
</script>
@endpush
