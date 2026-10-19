@extends(lms_portal_layout())
@section('title', 'Edit sitting')
@section('content')
@include('admin.assessments.partials.searchable-select-assets')
@php
    $tzForInput = old('timezone', $selectedTimezone ?? config('app.timezone', 'Asia/Dubai'));
    $fromDefault = '';
    if (old('available_from')) {
        $fromDefault = old('available_from');
    } elseif ($assignment->available_from) {
        try {
            $fromDefault = $assignment->available_from->copy()
                ->timezone($assignment->timezoneName())
                ->format('Y-m-d\TH:i');
        } catch (\Throwable $e) {
            $fromDefault = optional($assignment->available_from)->format('Y-m-d\TH:i') ?? '';
        }
    }
    $takerLabel = $assignment->student?->name
        ?? ($assignment->batch?->code ? ('Batch ' . $assignment->batch->code) : 'Sitting');
@endphp
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Edit sitting {{ $assignment->displayCode() }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">ALL TESTS</a></li>
            <li><a href="{{ route('admin.assessments.tests.attempts', $assessment->id) }}">Results</a></li>
            <li class="active"><strong>Edit</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox"><div class="ibox-content">
        <p class="help-block">
            @if($assignment->isSelfSitting())
                Self test sitting for <strong>{{ $assessment->title }}</strong>
                · {{ $takerLabel }}
                @if($assignment->student?->email)
                    <span class="text-muted">({{ $assignment->student->email }})</span>
                @endif
                — no appointment. The student starts anytime, up to Max attempts on the test.
            @else
                Update appointment timing for <strong>{{ $assessment->title }}</strong>
                · {{ $takerLabel }}
                @if($assignment->student?->email)
                    <span class="text-muted">({{ $assignment->student->email }})</span>
                @endif
                . Does not create a new sitting — use <strong>Assign again</strong> for a retake.
            @endif
        </p>
        <form method="POST" action="{{ route('admin.assessments.tests.update-assignment', $assignment->id) }}">
            @csrf
            @method('PUT')
            @if($assignment->isSelfSitting())
                <input type="hidden" name="sitting_kind" value="self">
            @endif
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Assessor <span class="text-muted">(who marks the exam, optional)</span></label>
                    <select name="marker_user_id" class="form-control lms-searchable" data-placeholder="Type to find assessor">
                        <option value="">— Admin / course instructors —</option>
                        @foreach($instructors as $ins)
                            <option value="{{ $ins->id }}" @selected((string) ($selectedMarkerId ?? '') === (string) $ins->id)>{{ $ins->name }} ({{ $ins->email }})</option>
                        @endforeach
                    </select>
                </div>
                @unless($assignment->isSelfSitting())
                <div class="col-md-3 form-group">
                    <label>Exam Appointment Start *</label>
                    <input type="datetime-local" name="available_from" id="available_from" class="form-control" value="{{ $fromDefault }}" required>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">
                        Window = start + exam duration
                        ({{ $assessment->duration_minutes ? $assessment->duration_minutes . ' min' : 'set duration on the test' }}).
                    </p>
                </div>
                @endunless
                <div class="col-md-3 form-group">
                    <label>Timezone *</label>
                    <select name="timezone" id="assign_timezone" class="form-control lms-searchable" data-placeholder="Type to find timezone" data-allow-clear="0" required>
                        @foreach(($timezoneOptions ?? \App\Models\LmsAssessmentAssignment::timezoneOptions()) as $tzValue => $tzLabel)
                            <option value="{{ $tzValue }}" @selected((string) $tzForInput === (string) $tzValue)>{{ $tzLabel }}</option>
                        @endforeach
                    </select>
                    <p class="help-block" style="margin-bottom:0;font-size:11px;">
                        <strong>Now in this timezone:</strong> <span id="tz_now_clock">—</span>
                    </p>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save sitting</button>
            <a href="{{ route('admin.assessments.tests.attempts', $assessment->id) }}" class="btn btn-default">Back to Results</a>
            <a href="{{ route('admin.assessments.tests.assigned-users', ['assessment_id' => $assessment->id]) }}" class="btn btn-default">Assigned Tests</a>
        </form>
    </div></div>
</div>
@endsection
@push('script')
<script>
(function () {
    var sel = document.getElementById('assign_timezone');
    var out = document.getElementById('tz_now_clock');
    if (!sel || !out) return;
    function tick() {
        try {
            out.textContent = new Date().toLocaleString('en-GB', {
                timeZone: sel.value || 'UTC',
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
        } catch (e) {
            out.textContent = '—';
        }
    }
    sel.addEventListener('change', tick);
    tick();
    setInterval(tick, 1000);
})();
</script>
@endpush
