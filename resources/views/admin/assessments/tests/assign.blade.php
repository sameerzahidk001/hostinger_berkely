@extends(lms_portal_layout())
@section('title', 'Assign test')
@section('content')
@include('admin.assessments.partials.searchable-select-assets')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Assign: {{ $assessment->title }} <small class="text-muted">ID {{ $assessment->id }}</small></h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">ALL TESTS</a></li>
            <li class="active"><strong>Assign</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox"><div class="ibox-content">
        <p class="help-block">
            Each save creates a <strong>new sitting</strong> of this <strong>same test</strong> (new appointment).
            That is how a <strong>retake</strong> is given — assign again; the student may start anytime inside the new window.
            Older sittings stay on Assigned Tests. Optional assessor: if set, that instructor (or Admin) can mark; otherwise Admin / course instructors can mark.
        </p>
        <form method="POST" action="{{ route('admin.assessments.tests.assign.save', $assessment->id) }}">
            @csrf
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Batches</label>
                    <select name="batch_ids[]" class="form-control lms-searchable" data-placeholder="Type to find batch" multiple>
                        @foreach($batches as $batch)
                            <option value="{{ $batch->id }}" @selected(collect($selectedBatchIds ?? [])->contains($batch->id))>{{ $batch->code }} — {{ $batch->name }}</option>
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
                            <option value="{{ $student->id }}" @selected(collect($selectedStudentIds ?? [])->contains($student->id))>
                                {{ $student->name }} ({{ $student->email }}){{ $roleLabel ? ' — '.$roleLabel : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>Assessor <span class="text-muted">(who marks the exam, optional)</span></label>
                    <select name="marker_user_id" class="form-control lms-searchable" data-placeholder="Type to find assessor">
                        <option value="">— Admin / course instructors —</option>
                        @foreach($instructors as $ins)
                            <option value="{{ $ins->id }}" @selected((string) ($selectedMarkerId ?? '') === (string) $ins->id)>{{ $ins->name }} ({{ $ins->email }})</option>
                        @endforeach
                    </select>
                </div>
                @php
                    $tzForInput = old('timezone', $selectedTimezone ?? config('app.timezone', 'Asia/Dubai'));
                    $fromForInput = old('available_from');
                @endphp
                <div class="col-md-3 form-group">
                    <label>Exam Appointment Start *</label>
                    <input type="datetime-local" name="available_from" id="available_from" class="form-control" value="{{ $fromForInput }}" required>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">
                        Type the start time using the <strong>Timezone</strong> on the right (compare with “Now” below).
                        Window = start + exam duration
                        ({{ $assessment->duration_minutes ? $assessment->duration_minutes . ' min' : 'set duration on the test' }}).
                        Student may start anytime inside that window.
                    </p>
                </div>
                <div class="col-md-3 form-group">
                    <label>Timezone *</label>
                    <select name="timezone" id="assign_timezone" class="form-control lms-searchable" data-placeholder="Type to find timezone" data-allow-clear="0" required>
                        @foreach(($timezoneOptions ?? \App\Models\LmsAssessmentAssignment::timezoneOptions()) as $tzValue => $tzLabel)
                            <option value="{{ $tzValue }}" @selected((string) $tzForInput === (string) $tzValue)>{{ $tzLabel }}</option>
                        @endforeach
                    </select>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">
                        <strong>Now in this timezone:</strong> <span id="tz_now_clock">—</span><br>
                        Appointment and Timing columns use this timezone — not UTC and not your laptop clock unless they match.
                    </p>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save assignments</button>
            <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">Back to tests</a>
            <a href="{{ route('admin.assessments.tests.assigned-users', ['assessment_id' => $assessment->id]) }}" class="btn btn-default">Assigned users list</a>
        </form>
    </div></div>
</div>
@endsection
@push('script')
<script>
(function () {
    var sel = document.getElementById('assign_timezone');
    var out = document.getElementById('tz_now_clock');
    var start = document.getElementById('available_from');
    if (!sel || !out) return;

    function partsInTz(tz) {
        var parts = new Intl.DateTimeFormat('en-CA', {
            timeZone: tz,
            year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit',
            hour12: false
        }).formatToParts(new Date());
        var map = {};
        parts.forEach(function (p) { if (p.type !== 'literal') map[p.type] = p.value; });
        return map;
    }

    function tick() {
        var tz = sel.value || 'Asia/Dubai';
        try {
            var m = partsInTz(tz);
            out.textContent = m.year + '-' + m.month + '-' + m.day + ' ' + m.hour + ':' + m.minute + ':' + m.second + ' (' + tz + ')';
        } catch (e) {
            out.textContent = new Date().toISOString() + ' (UTC fallback)';
        }
    }

    // Default appointment start = now (in selected TZ) so it is never “mystery UTC”.
    function fillDefaultStart() {
        if (!start || start.value) return;
        var tz = sel.value || 'Asia/Dubai';
        try {
            var m = partsInTz(tz);
            start.value = m.year + '-' + m.month + '-' + m.day + 'T' + m.hour + ':' + m.minute;
        } catch (e) { /* leave empty */ }
    }

    tick();
    fillDefaultStart();
    setInterval(tick, 1000);
    $(sel).on('change select2:select', function () {
        tick();
        if (start && !start.dataset.touched) {
            start.value = '';
            fillDefaultStart();
        }
    });
    if (start) {
        start.addEventListener('input', function () { start.dataset.touched = '1'; });
    }
})();
</script>
@endpush
