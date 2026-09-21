@extends('admin.layout.app')
@section('title', 'Create Class Schedule')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Create Class Schedule</h2>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.class-schedules.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Batch *</label>
                        <select name="batch_id" id="batch_id" class="form-control" required>
                            <option value="">Select batch</option>
                            @foreach(($classBatches ?? []) as $b)
                                <option value="{{ $b->id }}" @selected((string) old('batch_id', optional($selectedBatch)->id) === (string) $b->id)>
                                    {{ $b->displayLabel() }} — {{ $b->course->title ?? '' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">
                            @if($isAdmin ?? false)
                                Create batches under <a href="{{ route('admin.class-batches.index') }}">Batches</a>, then schedule sessions here.
                            @else
                                Only batches assigned to you appear here. Instructors cannot create batches.
                            @endif
                        </span>
                        @if(($classBatches ?? collect())->isEmpty())
                            <div class="alert alert-warning" style="margin-top:8px;margin-bottom:0;">No active batches available.</div>
                        @endif
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course</label>
                        <input type="text" id="course_title" class="form-control" value="{{ optional($selectedBatch?->course)->title }}" readonly>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Head of the Faculty</label>
                        <select name="head_of_faculty_id" id="head_of_faculty_id" class="form-control">
                            <option value="">—</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(old('head_of_faculty_id', optional($selectedBatch)->head_of_faculty_id) == $ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Instructor</label>
                        <select name="instructor_id" id="instructor_id" class="form-control">
                            <option value="">—</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(old('instructor_id', optional($selectedBatch)->primaryInstructorId()) == $ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Meeting account *</label>
                        <select name="meeting_account_id" id="meeting_account_id" class="form-control" required>
                            <option value="">Select Zoho or Zoom account</option>
                            @foreach(($meetingAccounts ?? []) as $account)
                                <option value="{{ $account->id }}"
                                    data-provider="{{ $account->provider }}"
                                    data-timezone="{{ $account->timezone ?: 'Asia/Dubai' }}"
                                    @selected((string) old('meeting_account_id', $defaultMeetingAccountId) === (string) $account->id)>
                                    {{ $account->dropdownLabel() }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block" id="meeting-account-help">
                            Zoho = Join link auto-created. Zoom = paste Join link manually.
                        </span>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Start date &amp; time *</label>
                        <input type="datetime-local" name="scheduled_at" id="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" required>
                        <span class="help-block">Enter the local time for the timezone you select below.</span>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Timezone *</label>
                        @php
                            $defaultTz = old('timezone');
                            if (! $defaultTz) {
                                $defAcct = ($meetingAccounts ?? collect())->firstWhere('id', $defaultMeetingAccountId);
                                $defaultTz = $defAcct->timezone ?? config('app.timezone', 'Asia/Dubai');
                            }
                        @endphp
                        <select name="timezone" id="timezone" class="form-control" required>
                            @foreach(($timezoneOptions ?? \App\Models\ClassSchedule::timezoneOptions()) as $tzValue => $tzLabel)
                                <option value="{{ $tzValue }}" @selected($defaultTz === $tzValue)>{{ $tzLabel }}</option>
                            @endforeach
                        </select>
                        <span class="help-block" id="scheduled-timezone-help">Shown with the time on all schedule tables.</span>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control" min="15" max="480" step="15" value="{{ old('duration_minutes', 60) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label id="meeting-link-label">Meeting link</label>
                        <input type="url" name="zoho_link" id="zoho_link" class="form-control" value="{{ old('zoho_link') }}" placeholder="https://...">
                        <span class="help-block" id="meeting-link-help">Leave blank for Zoho auto-create.</span>
                    </div>

                    @include('admin.study-materials.schedules._recurrence_fields', ['schedule' => new \App\Models\ClassSchedule()])

                    <div class="col-md-12 form-group">
                        <label>Students</label>
                        <select name="student_ids[]" id="student_ids" class="form-control" multiple>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(collect(old('student_ids', optional($selectedBatch)?->students?->pluck('id')->all() ?? []))->contains($student->id))>
                                    {{ $student->name }} ({{ $student->email }})
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">Select students for this session. Defaults to batch students when you pick a batch.</span>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Description</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Shown to students on the schedule (topic, homework, etc.)">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.class-schedules.index') }}" class="btn btn-default">Cancel</a>
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
@include('admin.study-materials.schedules._recurrence_script')
<script>
$(function () {
    var $batch = $('#batch_id');
    var $students = $('#student_ids');
    var isAdmin = @json($isAdmin ?? false);
    $batch.select2({ placeholder: 'Type to find batch', allowClear: true, width: '100%' });
    $students.select2({ placeholder: 'Type to find students', width: '100%', closeOnSelect: false });
    $('#head_of_faculty_id').select2({ placeholder: 'Type to find head of faculty', allowClear: true, width: '100%' });
    $('#instructor_id').select2({ placeholder: 'Type to find instructor', allowClear: true, width: '100%' });
    $('#meeting_account_id').select2({ placeholder: 'Type to find meeting account', allowClear: true, width: '100%' });

    function loadBatch() {
        var id = $batch.val();
        if (!id) return;
        $.getJSON(@json(route('admin.class-schedules.batch-meta')), { batch_id: id })
            .done(function (res) {
                $('#course_title').val(res.course_title || '');
                if (res.head_of_faculty_id) $('#head_of_faculty_id').val(String(res.head_of_faculty_id)).trigger('change');
                if (res.primary_instructor_id) $('#instructor_id').val(String(res.primary_instructor_id)).trigger('change');
                $students.empty();
                (res.students || []).forEach(function (row) {
                    var selected = row.selected !== false;
                    $students.append(new Option(row.text, row.id, selected, selected));
                });
                $students.trigger('change');
            });
    }
    $batch.on('change', loadBatch);

    function syncMeetingLinkUi() {
        var $opt = $('#meeting_account_id option:selected');
        var provider = ($opt.data('provider') || '').toString().toLowerCase();
        var tz = ($opt.data('timezone') || '').toString();
        var isZoom = provider === 'zoom';
        $('#zoho_link').prop('required', isZoom);
        $('#meeting-link-label').text(isZoom ? 'Zoom meeting link *' : 'Meeting link');
        $('#meeting-link-help').text(isZoom
            ? 'Paste the Zoom Join URL manually (required for Zoom).'
            : 'Leave blank — Zoho Join link is created automatically on Save.');
        $('#meeting-account-help').text(isZoom
            ? 'Zoom selected: paste the Join link below.'
            : 'Zoho selected: Join link will be created automatically.');
        // Suggest meeting-account timezone only when user hasn't picked one yet / on account change.
        if (tz && $('#timezone option[value="' + tz + '"]').length) {
            $('#timezone').val(tz);
        }
    }
    $('#meeting_account_id').on('change', syncMeetingLinkUi);
    syncMeetingLinkUi();

    $('form').on('submit', function () {
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).text('Saving…');
    });
});
</script>
@endpush
