@extends('admin.layout.app')
@section('title', 'Edit Class Schedule')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10"><h2>Edit Class Schedule</h2></div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.class-schedules.update', $schedule->id) }}">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Batch *</label>
                        <select name="batch_id" id="batch_id" class="form-control" required>
                            <option value="">Select batch</option>
                            @foreach(($classBatches ?? []) as $b)
                                <option value="{{ $b->id }}" @selected((string) old('batch_id', $schedule->batch_id) === (string) $b->id)>
                                    {{ $b->displayLabel() }} — {{ $b->course->title ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $schedule->title) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course</label>
                        <input type="text" id="course_title" class="form-control" value="{{ $schedule->course->title ?? optional($selectedBatch?->course)->title }}" readonly>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Head of the Faculty</label>
                        <select name="head_of_faculty_id" id="head_of_faculty_id" class="form-control">
                            <option value="">—</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(old('head_of_faculty_id', $schedule->head_of_faculty_id) == $ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Instructor</label>
                        <select name="instructor_id" id="instructor_id" class="form-control">
                            <option value="">—</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(old('instructor_id', $schedule->instructor_id) == $ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Meeting account *</label>
                        <select name="meeting_account_id" class="form-control" required>
                            <option value="">Select Zoho or Zoom account</option>
                            @foreach(($meetingAccounts ?? []) as $account)
                                <option value="{{ $account->id }}" @selected((string) old('meeting_account_id', $schedule->meeting_account_id ?: $defaultMeetingAccountId) === (string) $account->id)>
                                    {{ $account->dropdownLabel() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Start date &amp; time *</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', optional($schedule->scheduled_at)->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Duration (minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control" min="15" max="480" step="15" value="{{ old('duration_minutes', $schedule->duration_minutes ?: 60) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            @foreach(['scheduled','completed','cancelled'] as $st)
                                <option value="{{ $st }}" @selected(old('status', $schedule->status) === $st)>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Meeting link</label>
                        <input type="url" name="zoho_link" class="form-control" value="{{ old('zoho_link', $schedule->zoho_link) }}" placeholder="Leave blank to auto-create">
                    </div>

                    @include('admin.study-materials.schedules._recurrence_fields', ['schedule' => $schedule])

                    <div class="col-md-12 form-group">
                        <label>Students {{ ($isAdmin ?? false) ? '' : '(from batch — view only)' }}</label>
                        @php $selected = collect(old('student_ids', $schedule->students->pluck('id')->all()))->map(fn ($id) => (int) $id)->all(); @endphp
                        <select name="student_ids[]" id="student_ids" class="form-control" multiple @disabled(!($isAdmin ?? false))>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(in_array((int) $student->id, $selected, true))>{{ $student->name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                        @unless($isAdmin ?? false)
                            @foreach($schedule->students as $student)
                                <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
                            @endforeach
                        @endunless
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $schedule->notes) }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.class-schedules.ics', $schedule->id) }}" class="btn btn-default">Add to Zoho Calendar (.ics)</a>
                <a href="{{ route('admin.class-schedules.index') }}" class="btn btn-white">Cancel</a>
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
    $batch.select2({ placeholder: 'Select batch', allowClear: true, width: '100%' });
    $students.select2({ placeholder: 'Students', width: '100%', closeOnSelect: false });
    $batch.on('change', function () {
        var id = $batch.val();
        if (!id) return;
        $.getJSON(@json(route('admin.class-schedules.batch-meta')), { batch_id: id })
            .done(function (res) {
                $('#course_title').val(res.course_title || '');
                if (res.head_of_faculty_id) $('#head_of_faculty_id').val(String(res.head_of_faculty_id));
                if (res.primary_instructor_id) $('#instructor_id').val(String(res.primary_instructor_id));
                $students.empty();
                (res.students || []).forEach(function (row) {
                    $students.append(new Option(row.text, row.id, true, true));
                });
                $students.trigger('change');
            });
    });
});
</script>
@endpush
