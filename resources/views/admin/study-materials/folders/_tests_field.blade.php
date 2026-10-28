<div class="col-md-12 form-group">
    <label>Self tests on this folder</label>
    <input type="hidden" name="assessment_ids_present" value="1">
    <select name="assessment_ids[]" id="assessment_ids" class="form-control" multiple>
        @foreach($tests ?? [] as $test)
            @php
                $kind = method_exists($test, 'kindLabel') ? $test->kindLabel() : ($test->kind ?? 'test');
                $code = method_exists($test, 'displayCode') ? $test->displayCode() : ('BST' . (int) $test->id);
                $courseTitle = $test->course->title ?? '';
            @endphp
            <option value="{{ $test->id }}" @selected(in_array((int) $test->id, array_map('intval', $selectedAssessmentIds ?? []), true))>
                {{ $test->title }} ({{ $code }} · {{ $kind }}){{ $courseTitle ? ' — '.$courseTitle : '' }}
            </option>
        @endforeach
    </select>
    <span class="help-block">
        Type to find tests from ALL TESTS. These are <strong>self tests</strong>: no Test Appointment Start, no extra email.
        Students and instructors who can open this folder can Start anytime here and also under Assessment.
    </span>
</div>
