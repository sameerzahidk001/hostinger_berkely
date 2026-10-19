@php
    $choices = \App\Models\Email::templateChoices();
    $current = old('name', $currentName ?? '');
@endphp
<select name="name" id="name" class="form-control" @required(empty($currentName ?? null))>
    <option value="">Select Email</option>
    @foreach($choices as $value => $label)
        <option value="{{ $value }}" @selected((string) $current === (string) $value)>{{ $label }}</option>
    @endforeach
    @if($current !== '' && ! array_key_exists($current, $choices))
        <option value="{{ $current }}" selected>{{ $current }} (custom — pick a system name above to connect this template)</option>
    @endif
</select>
<p class="help-block" style="margin-bottom:0;font-size:11px;">
    For tests pick <strong>LMS Exam Assigned</strong> or <strong>LMS Self Test Assigned</strong>. The Name value is the system key — do not leave it on Select Email.
</p>
