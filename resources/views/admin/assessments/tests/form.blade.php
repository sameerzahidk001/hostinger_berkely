@extends(lms_portal_layout())
@section('title', ($assessment->exists ? 'Edit' : 'Create') . ' Assessment')
@section('content')
@include('admin.assessments.partials.limited-editor-assets')
@include('admin.assessments.partials.searchable-select-assets')
@php
    $descriptionMax = 20000;
    $canCreate = $canCreate ?? true;
    $canAssign = $canAssign ?? true;
    $isSkill = old('kind', $assessment->kind) === \App\Models\LmsAssessment::KIND_SKILL;
    $targetRoles = $targetRoles ?? \App\Models\LmsAssessment::targetRoles();
@endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>
            {{ $assessment->exists ? 'Edit' : 'Create' }} test
            @if($assessment->exists)
                <small class="text-muted">ID {{ $assessment->id }}</small>
            @endif
        </h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">ALL TESTS</a></li>
            <li class="active"><strong>{{ $assessment->exists ? 'Edit' : 'Create' }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox"><div class="ibox-content">
        <form method="POST" action="{{ $assessment->exists ? route('admin.assessments.tests.update', $assessment->id) : route('admin.assessments.tests.store') }}" enctype="multipart/form-data">
            @csrf
            @if($assessment->exists) @method('PUT') @endif

            <fieldset @disabled(! $canCreate)>
            <div class="row">
                <div class="col-md-8 form-group">
                    <label>Test Name *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $assessment->title) }}" @required($canCreate) @readonly(! $canCreate)>
                </div>
                <div class="col-md-4 form-group">
                    <label>Test Type *</label>
                    <select name="kind" id="assessment_kind" class="form-control lms-searchable" data-placeholder="Type to find kind" data-allow-clear="0" @required($canCreate) @disabled(! $canCreate)>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected(old('kind', $assessment->kind) === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if(! $canCreate)
                        <input type="hidden" name="kind" value="{{ old('kind', $assessment->kind) }}">
                    @endif
                </div>
                <div class="col-md-12 form-group">
                    <label>Description / test instructions <span class="lms-char-hint">(max {{ number_format($descriptionMax) }} characters)</span></label>
                    <textarea name="description" class="form-control lms-text-editor" rows="6" data-max="{{ $descriptionMax }}" data-height="220" data-rich="1" @readonly(! $canCreate)>{{ old('description', $assessment->description) }}</textarea>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">Shown to the student as instructions before Question 1 (Continue to start). Also on View Details after submit.</p>
                </div>
                <div class="col-md-4 form-group js-course-field" style="{{ $isSkill ? 'display:none;' : '' }}">
                    <label>Course{{ !empty($courseRequired) ? ' *' : '' }}</label>
                    <select name="course_id" class="form-control lms-searchable" data-placeholder="Type to find course" @if(!empty($courseRequired) && $canCreate && ! $isSkill) required @endif @disabled(! $canCreate || $isSkill)>
                        <option value="">—</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected((string) old('course_id', $assessment->course_id) === (string) $course->id)>{{ $course->title }}</option>
                        @endforeach
                    </select>
                    @if(! $canCreate && ! $isSkill)
                        <input type="hidden" name="course_id" value="{{ old('course_id', $assessment->course_id) }}">
                    @endif
                    @if(!empty($courseRequired))
                        <p class="help-block m-b-none">Instructors may only create Practice/Mock for courses Admin assigned them.</p>
                    @endif
                </div>
                <div class="col-md-4 form-group js-target-role-field" style="{{ $isSkill ? '' : 'display:none;' }}">
                    <label>Test for * <span class="text-muted">(no course — choose user type)</span></label>
                    <select name="target_role" class="form-control lms-searchable" data-placeholder="Type to find user type" data-allow-clear="0" @disabled(! $canCreate)>
                        @foreach($targetRoles as $rk => $rl)
                            <option value="{{ $rk }}" @selected(old('target_role', $assessment->target_role ?? 'all_staff') === $rk)>{{ $rl }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Duration (min)</label>
                    <input type="number" min="1" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $assessment->duration_minutes) }}" placeholder="Optional" @readonly(! $canCreate)>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">Optional if questions have timers.</p>
                </div>
                <div class="col-md-2 form-group">
                    <label>Max attempts *</label>
                    <input type="number" min="1" max="50" name="max_attempts" class="form-control" value="{{ old('max_attempts', $assessment->max_attempts ?? 1) }}" @required($canCreate) @readonly(! $canCreate)>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">Exam retake: assign a new sitting. Self test: student can Start/Retake up to this number without a new schedule.</p>
                </div>
                <div class="col-md-2 form-group">
                    <label>Status *</label>
                    <select name="status" class="form-control lms-searchable" data-placeholder="Type to find status" data-allow-clear="0" @required($canCreate) @disabled(! $canCreate)>
                        <option value="draft" @selected(old('status', $assessment->status) === 'draft')>Draft</option>
                        <option value="published" @selected(old('status', $assessment->status) === 'published')>Published</option>
                        <option value="disabled" @selected(old('status', $assessment->status) === 'disabled')>Disabled</option>
                    </select>
                    @if(! $canCreate)
                        <input type="hidden" name="status" value="{{ old('status', $assessment->status) }}">
                    @endif
                </div>
                <div class="col-md-2 form-group">
                    <label>Results mode *</label>
                    <select name="results_mode" class="form-control lms-searchable" data-placeholder="Type to find results mode" data-allow-clear="0" @required($canCreate) @disabled(! $canCreate)>
                        <option value="after_publish" @selected(old('results_mode', $assessment->results_mode) === 'after_publish')>Publish after marking</option>
                        <option value="instant_mcq" @selected(in_array(old('results_mode', $assessment->results_mode), ['instant_mcq', 'instant'], true))>Instant</option>
                    </select>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">Instant: student sees questions, their answers and explanations right after submit, for every question type.</p>
                    @if(! $canCreate)
                        <input type="hidden" name="results_mode" value="{{ old('results_mode', $assessment->results_mode) }}">
                    @endif
                </div>
                <div class="col-md-12 form-group">
                    <label class="m-b-none"><input type="checkbox" name="shuffle_questions" value="1" @checked(old('shuffle_questions', $assessment->shuffle_questions)) @disabled(! $canCreate)> Shuffle questions</label>
                    @if($canCreate)
                        <input type="hidden" name="allow_pause" value="0">
                    @endif
                    <label class="m-b-none" style="margin-left:18px;"><input type="checkbox" name="allow_pause" value="1" @checked(old('allow_pause', $assessment->allowsPause())) @disabled(! $canCreate)> Enable Pause button</label>
                    @if(! $canCreate)
                        <input type="hidden" name="allow_pause" value="{{ old('allow_pause', $assessment->allowsPause()) ? 1 : 0 }}">
                    @endif
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">If checked, the student can pause. Timers stop and the end time is extended by the pause duration.</p>
                </div>
            </div>

            <h4 style="margin-top:8px;">Exam files <span class="text-muted" style="font-size:13px;font-weight:normal;">(optional — 1 file per button)</span></h4>
            <p class="help-block">
                Files upload to <strong>Zoho WorkDrive</strong> (the folder set in Zoho LMS settings). They are not stored on this server.
                @if(! ($zohoWorkDriveReady ?? false))
                    <span class="text-danger">WorkDrive is not connected yet — connect OAuth before uploading.</span>
                @endif
                Navy buttons appear in the test only after files are saved.
            </p>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Reference material</label>
                    <input type="file" name="reference_files[]" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.txt" @disabled(! $canCreate)>
                    @foreach($assessment->resourceFiles('reference') as $ri => $rf)
                        <label class="m-t-xs" style="display:block;font-weight:normal;">
                            <input type="checkbox" name="remove_reference[]" value="{{ $ri }}" @disabled(! $canCreate)> Remove {{ $rf['name'] }}
                            @if(! empty($rf['url']) && str_contains((string) $rf['url'], 'workdrive'))
                                <a href="{{ $rf['url'] }}" target="_blank" rel="noopener">Zoho Drive</a>
                            @endif
                        </label>
                    @endforeach
                </div>
                <div class="col-md-6 form-group">
                    <label>Pre-seen</label>
                    <input type="file" name="preseen_files[]" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.txt" @disabled(! $canCreate)>
                    @foreach($assessment->resourceFiles('preseen') as $pi => $pf)
                        <label class="m-t-xs" style="display:block;font-weight:normal;">
                            <input type="checkbox" name="remove_preseen[]" value="{{ $pi }}" @disabled(! $canCreate)> Remove {{ $pf['name'] }}
                            @if(! empty($pf['url']) && str_contains((string) $pf['url'], 'workdrive'))
                                <a href="{{ $pf['url'] }}" target="_blank" rel="noopener">Zoho Drive</a>
                            @endif
                        </label>
                    @endforeach
                </div>
            </div>

            <h4>Questions from QBANK</h4>
            @php
                $qById = $questions->keyBy('id');
                if ($assessment->relationLoaded('questions')) {
                    foreach ($assessment->questions as $aq) {
                        if (! $qById->has($aq->id)) {
                            $qById->put($aq->id, $aq);
                        }
                    }
                }
                $orderedQuestionIds = collect(old('question_ids', $selectedQuestionIds ?? []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
            @endphp
            <select id="lms-q-picker" class="form-control lms-searchable" data-placeholder="Type to find questions" multiple @disabled(! $canCreate)>
                @foreach($questions as $q)
                    <option value="{{ $q->id }}" @selected($orderedQuestionIds->contains($q->id))>
                        #{{ $q->id }} [{{ strtoupper($q->type) }}]@if(trim((string) ($q->topic ?? '')) !== '') {{ trim($q->topic) }} ·@endif {{ \Illuminate\Support\Str::limit(strip_tags($q->stem), 80) }} ({{ lms_format_number($q->max_marks) }} marks)
                    </option>
                @endforeach
            </select>
            <p class="help-block">Add questions here, then arrange the sequence below. That order is how they appear in the test (unless Shuffle questions is checked). Assign users on the separate Assign page.</p>
            <label>Question sequence (Q1, Q2, Q3…)</label>
            <ol id="lms-q-order" class="list-group" style="margin-bottom:8px;">
                @forelse($orderedQuestionIds as $oi => $oid)
                    @php $oq = $qById->get($oid); @endphp
                    @if($oq)
                        <li class="list-group-item" data-id="{{ $oq->id }}" style="display:flex;align-items:center;gap:8px;">
                            <input type="hidden" name="question_ids[]" value="{{ $oq->id }}">
                            <strong class="js-q-num" style="min-width:36px;">Q{{ $oi + 1 }}</strong>
                            <span class="js-q-label" style="flex:1;">#{{ $oq->id }} [{{ strtoupper($oq->type) }}]@if(trim((string) ($oq->topic ?? '')) !== '') {{ trim($oq->topic) }} ·@endif {{ \Illuminate\Support\Str::limit(strip_tags($oq->stem), 80) }}</span>
                            <button type="button" class="btn btn-xs btn-default js-q-up" title="Move up" @disabled(! $canCreate)>&uarr;</button>
                            <button type="button" class="btn btn-xs btn-default js-q-down" title="Move down" @disabled(! $canCreate)>&darr;</button>
                        </li>
                    @endif
                @empty
                @endforelse
            </ol>
            <p class="help-block m-b-none">Use ↑ / ↓ so the first row is Q1, second is Q2, third is Q3. Save the test after arranging. Leave Shuffle questions unchecked to keep this order.</p>
            </fieldset>

            @if($canCreate)
                <button type="submit" class="btn btn-primary">Save test</button>
            @endif
            <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">Back</a>
            @if($assessment->exists)
                @if($canAssign)
                    <a href="{{ route('admin.assessments.tests.assign', $assessment->id) }}" class="btn btn-success">Assign Test (Exam)</a>
                    <a href="{{ route('admin.assessments.tests.assign', ['id' => $assessment->id, 'mode' => 'self']) }}" class="btn btn-success">Assign Self test</a>
                @endif
                <a href="{{ route('admin.assessments.tests.attempts', $assessment->id) }}" class="btn btn-default">View attempts</a>
            @endif
        </form>
    </div></div>
</div>
@endsection
@push('script')
<script>
(function () {
    function syncKindUi() {
        var kind = $('#assessment_kind').val() || $('input[name="kind"]').val();
        var skill = kind === 'skill';
        $('.js-course-field').toggle(!skill);
        $('.js-target-role-field').toggle(skill);
    }
    $('#assessment_kind').on('change select2:select', syncKindUi);
    syncKindUi();

    var $picker = $('#lms-q-picker');
    var $order = $('#lms-q-order');
    function qLabelFromOption(id) {
        var opt = $picker.find('option[value="' + id + '"]');
        return opt.length ? $.trim(opt.text()) : ('#' + id);
    }
    function refreshQNumbers() {
        $order.find('li').each(function (i) {
            var $n = $(this).find('.js-q-num');
            if (!$n.length) {
                $n = $('<strong class="js-q-num" style="min-width:36px;"></strong>');
                $(this).prepend($n);
            }
            $n.text('Q' + (i + 1));
            var id = $(this).attr('data-id');
            var opt = $picker.find('option[value="' + id + '"]');
            var $lab = $(this).find('.js-q-label');
            if ($lab.length && opt.length) {
                $lab.text($.trim(opt.text()));
            }
        });
    }
    function addOrderRow(id) {
        if (!id || $order.find('li[data-id="' + id + '"]').length) return;
        var li = $('<li class="list-group-item" data-id="' + id + '" style="display:flex;align-items:center;gap:8px;"></li>');
        li.append($('<strong class="js-q-num" style="min-width:36px;"></strong>'));
        li.append($('<input type="hidden" name="question_ids[]">').val(id));
        li.append($('<span class="js-q-label" style="flex:1;"></span>').text(qLabelFromOption(id)));
        li.append('<button type="button" class="btn btn-xs btn-default js-q-up" title="Move up">&uarr;</button>');
        li.append('<button type="button" class="btn btn-xs btn-default js-q-down" title="Move down">&darr;</button>');
        $order.append(li);
        refreshQNumbers();
    }
    $picker.on('select2:select', function (e) {
        addOrderRow(e.params && e.params.data ? e.params.data.id : null);
    });
    $picker.on('select2:unselect', function (e) {
        var id = e.params && e.params.data ? e.params.data.id : null;
        if (id) $order.find('li[data-id="' + id + '"]').remove();
        refreshQNumbers();
    });
    $order.on('click', '.js-q-up', function () {
        if ($picker.prop('disabled')) return;
        var li = $(this).closest('li');
        li.prev('li').before(li);
        refreshQNumbers();
    });
    $order.on('click', '.js-q-down', function () {
        if ($picker.prop('disabled')) return;
        var li = $(this).closest('li');
        li.next('li').after(li);
        refreshQNumbers();
    });
    refreshQNumbers();
})();
</script>
@endpush
