@php
    \App\Models\User::ensureInstructorExtraColumns();
    $lmsOptions = \App\Models\User::lmsPermissionFormOptions();
    $selectedLms = old('lms_permissions', method_exists($user, 'lmsPermissionList') ? $user->lmsPermissionList() : []);
    // Expand legacy combined tick into the two separate checkboxes in the UI.
    if (in_array('manage_student_assessments', (array) $selectedLms, true)) {
        $selectedLms[] = 'create_student_assessments';
        $selectedLms[] = 'assign_student_assessments';
        $selectedLms = array_values(array_unique($selectedLms));
    }
@endphp
<input type="hidden" name="lms_permissions_present" value="1">
<p class="help-block" style="margin-top:0;">Admin must grant each permission separately. Create and Assign for Practice/Mock are independent. Question Bank and Practice/Mock are limited to courses assigned to that instructor.</p>
<div class="form-group">
    @foreach($lmsOptions as $key => $label)
        <div class="checkbox">
            <label>
                <input type="checkbox" name="lms_permissions[]" value="{{ $key }}" @checked(in_array($key, (array) $selectedLms, true))>
                {{ $label }}
            </label>
        </div>
    @endforeach
</div>
