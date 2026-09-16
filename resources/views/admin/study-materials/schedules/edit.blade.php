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
                        <label>Batch name *</label>
                        <input type="text" name="batch_name" class="form-control" value="{{ old('batch_name', $schedule->batch_name) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $schedule->title) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course *</label>
                        <select name="course_id" id="course_id" class="form-control" required>
                            <option value="">Type to find the course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $schedule->course_id) == $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Instructor</label>
                        <select name="instructor_id" class="form-control">
                            <option value="">—</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(old('instructor_id', $schedule->instructor_id) == $ins->id)>{{ $ins->name }}</option>
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
                        @if(!empty($schedule->zoho_link))
                            <input type="url" name="zoho_link" class="form-control" value="{{ old('zoho_link', $schedule->zoho_link) }}">
                            <span class="help-block">Existing auto-created link. Leave as-is unless you need to replace it.</span>
                        @elseif($zohoMeetingReady ?? false)
                            <div class="alert alert-info" style="margin-bottom:0;">
                                <strong>Auto-create on Update</strong><br>
                                Zoho Meeting will be created under
                                <strong>{{ $zohoHostEmail ?? 'bdm@berkeleyme.com' }}</strong>
                                and added to Zoho Calendar. No paste needed.
                            </div>
                        @else
                            <input type="url" name="zoho_link" class="form-control" value="{{ old('zoho_link', $schedule->zoho_link) }}" placeholder="https://meeting.zoho.com/...">
                            <div class="alert alert-warning" style="margin-top:8px; margin-bottom:0;">
                                <strong>Auto meeting link is off.</strong>
                                Connect Zoho OAuth as <strong>{{ $zohoHostEmail ?? 'bdm@berkeleyme.com' }}</strong>
                                (Admin → Zoho / LMS settings), then save again — or paste a Meeting link here.
                            </div>
                        @endif
                    </div>

                    @include('admin.study-materials.schedules._recurrence_fields', ['schedule' => $schedule])

                    <div class="col-md-12 form-group">
                        <label>Assign students</label>
                        @php $selected = collect(old('student_ids', $schedule->students->pluck('id')->all()))->map(fn ($id) => (int) $id)->all(); @endphp
                        <select name="student_ids[]" id="student_ids" class="form-control" multiple data-keep="{{ implode(',', $selected) }}">
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(in_array((int) $student->id, $selected, true))>{{ $student->name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                        <span class="help-block">Type to search. You can select multiple students.</span>
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
    var $course = $('#course_id');
    var $students = $('#student_ids');

    $course.select2({ placeholder: 'Type to find the course', allowClear: true, width: '100%' });
    $students.select2({ placeholder: 'Type to find students', width: '100%', closeOnSelect: false });

    function reloadStudents() {
        var courseId = $course.val() || '';
        var keep = $students.val() || ($students.data('keep') ? String($students.data('keep')).split(',') : []);
        $.getJSON(@json(route('admin.class-schedules.students')), { course_id: courseId, keep: (keep || []).join(',') })
            .done(function (res) {
                var selected = keep.map(String);
                $students.empty();
                (res.students || []).forEach(function (row) {
                    var opt = new Option(row.text, row.id, false, selected.indexOf(String(row.id)) !== -1);
                    $students.append(opt);
                });
                $students.trigger('change');
            });
    }

    $course.on('change', reloadStudents);
});
</script>
@endpush
