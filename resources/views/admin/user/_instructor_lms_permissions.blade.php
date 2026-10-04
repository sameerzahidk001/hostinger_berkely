@php
    \App\Models\User::ensureInstructorExtraColumns();
    $lmsOptions = \App\Models\User::lmsPermissionOptions();
    $selectedLms = old('lms_permissions', method_exists($user, 'lmsPermissionList') ? $user->lmsPermissionList() : []);
@endphp
<h3 class="profile-section-heading">LMS permissions</h3>
<input type="hidden" name="lms_permissions_present" value="1">
<p class="help-block" style="margin-top:0;">Admin must grant each permission. The instructor can take the action only when it is ticked.</p>
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
