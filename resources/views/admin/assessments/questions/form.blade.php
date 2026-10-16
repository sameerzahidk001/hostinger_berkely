@extends(lms_portal_layout())
@section('title', ($question->exists ? 'Edit' : 'Add') . ' Question')
@section('content')
@include('admin.assessments.partials.limited-editor-assets')
@include('admin.assessments.partials.searchable-select-assets')
@php
    $stemMax = 10000;
    $explanationMax = 10000;
    $topicMax = 255;
    $optionMax = 500;
@endphp
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
                {{-- Single edit — compact top row: Status first --}}
                <div class="row">
                    <div class="col-md-2 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control lms-searchable" data-placeholder="Type to find status" data-allow-clear="0">
                            <option value="active" @selected(old('status', $question->status ?: 'active') === 'active')>Active</option>
                            <option value="disabled" @selected(old('status', $question->status) === 'disabled')>Disabled</option>
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Type *</label>
                        <select name="type" id="q_type" class="form-control lms-searchable" data-placeholder="Type to find type" data-allow-clear="0" required>
                            @foreach($types as $k => $label)
                                <option value="{{ $k }}" @selected(old('type', $question->type) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Topic <span class="lms-char-hint">(max {{ $topicMax }})</span></label>
                        <input type="text" name="topic" class="form-control" data-max="{{ $topicMax }}" maxlength="{{ $topicMax }}" value="{{ old('topic', $question->topic) }}">
                    </div>
                    <div class="col-md-2 form-group js-q-target-role">
                        <label>Question for</label>
                        <select name="target_role" class="form-control lms-searchable" data-placeholder="Type to find user type">
                            <option value="">—</option>
                            @foreach(($targetRoles ?? []) as $rk => $rl)
                                <option value="{{ $rk }}" @selected(old('target_role', $question->target_role) === $rk)>{{ $rl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Course</label>
                        <select name="course_id" id="q_course_id" class="form-control lms-searchable" data-placeholder="Type to find course">
                            <option value="">—</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) old('course_id', $question->course_id) === (string) $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Max marks *</label>
                        <input type="number" step="0.1" min="0.1" name="max_marks" class="form-control" value="{{ old('max_marks', $question->max_marks ?: 1) }}" required>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Question stem * <span class="lms-char-hint">(max {{ number_format($stemMax) }} characters)</span></label>
                        <textarea name="stem" class="form-control lms-text-editor" rows="4" data-max="{{ $stemMax }}" data-height="180" data-rich="1" required>{{ old('stem', $question->stem) }}</textarea>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Explanation / model answer notes <span class="lms-char-hint">(max {{ number_format($explanationMax) }} characters)</span></label>
                        <textarea name="explanation" class="form-control lms-text-editor" rows="3" data-max="{{ $explanationMax }}" data-height="140" data-rich="1">{{ old('explanation', $question->explanation) }}</textarea>
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
                            <input type="text" name="options[{{ $i }}][text]" class="form-control" data-max="{{ $optionMax }}" maxlength="{{ $optionMax }}" value="{{ $opt['text'] ?? '' }}" placeholder="Option {{ $i + 1 }} (max {{ $optionMax }})">
                        </div>
                    @endforeach
                </div>
            @else
                {{-- Multi create: shared defaults + question blocks --}}
                <div class="row m-b-md">
                    <div class="col-md-3 form-group js-shared-target-role">
                        <label>Question for (all)</label>
                        <select name="target_role" class="form-control lms-searchable" data-placeholder="Type to find user type">
                            <option value="">—</option>
                            @foreach(($targetRoles ?? []) as $rk => $rl)
                                <option value="{{ $rk }}" @selected(old('target_role') === $rk)>{{ $rl }}</option>
                            @endforeach
                        </select>
                        <p class="help-block m-b-none">Who these questions are for (optional with a course).</p>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Course (applies to all)</label>
                        <select name="course_id" class="form-control lms-searchable" id="shared_course" data-placeholder="Type to find course">
                            <option value="">— (no course)</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((string) old('course_id') === (string) $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Topic (default for all) <span class="lms-char-hint">(max {{ $topicMax }})</span></label>
                        <input type="text" name="topic" id="shared_topic" class="form-control" data-max="{{ $topicMax }}" maxlength="{{ $topicMax }}" value="{{ old('topic') }}" placeholder="Optional shared topic">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control lms-searchable" data-placeholder="Type to find status" data-allow-clear="0">
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
<script>
(function () {
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
            $block.find('textarea.lms-text-editor, textarea.text-editor, textarea[data-rich="1"]').each(function () {
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
        if (window.LmsLimitedEditor) {
            window.LmsLimitedEditor.init($block);
        }
        if (window.LmsSearchableSelect) {
            window.LmsSearchableSelect.init($block);
        }
        bindBlock($block);
        var topic = $('#shared_topic').val();
        if (topic) {
            $block.find('.js-topic').val(topic);
        }
        updateTotalMarks();
    });
})();
</script>
@endpush
