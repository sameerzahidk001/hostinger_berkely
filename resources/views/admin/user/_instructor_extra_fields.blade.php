@php
    \App\Models\User::ensureInstructorExtraColumns();
    $expertiseRows = old('expertise', $user->expertiseList());
    if ($expertiseRows === []) {
        $expertiseRows = [''];
    }
    $methodOptions = \App\Models\User::teachingMethodologyOptions();
    $selectedMethods = old('teaching_methodology', $user->teachingMethodologyList());
    $availability = old('availability', $user->availabilityData());
    $availFrequency = $availability['frequency'] ?? 'particular';
    $availDays = $availability['days'] ?? [];
    $availStart = $availability['start_time'] ?? '';
    $availEnd = $availability['end_time'] ?? '';
    $availTz = $availability['timezone'] ?? 'Asia/Dubai';
    $availFlexible = old('availability.flexible', $availability['flexible'] ?? 'no');
    $dayCodes = ['MO' => 'Mon', 'TU' => 'Tue', 'WE' => 'Wed', 'TH' => 'Thu', 'FR' => 'Fri', 'SA' => 'Sat', 'SU' => 'Sun'];
    $tzOptions = \App\Models\ClassSchedule::timezoneOptions();
@endphp

<div class="form-group mt-3">
    <label for="expertise[]">Expertise</label>
    <div id="expertise-wrapper">
        @foreach($expertiseRows as $i => $item)
            <div class="form-group mb-2 expertise-group">
                <input type="text" name="expertise[]" class="form-control" placeholder="Enter expertise" value="{{ $item }}">
                @if($i > 0)
                    <button type="button" class="btn btn-danger btn-sm remove-expertise" style="margin-left:10px;float:right;margin-top:10px;">Remove</button>
                @endif
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-expertise-btn">Add More</button>
</div>

<div class="form-group mt-4">
    <label>Instructor’s availability</label>
    <div class="radio" style="margin-bottom:8px;">
        <label style="margin-right:16px;">
            <input type="radio" name="availability[frequency]" value="daily" @checked($availFrequency === 'daily')> Daily
        </label>
        <label>
            <input type="radio" name="availability[frequency]" value="particular" @checked($availFrequency !== 'daily')> Particular days
        </label>
    </div>
    <div id="availability-days-wrap" style="{{ $availFrequency === 'daily' ? 'display:none;' : '' }};margin-bottom:10px;">
        @foreach($dayCodes as $code => $label)
            <label class="checkbox-inline" style="margin-right:10px;">
                <input type="checkbox" name="availability[days][]" value="{{ $code }}" @checked(in_array($code, (array) $availDays, true))> {{ $label }}
            </label>
        @endforeach
    </div>
    <div class="row">
        <div class="col-sm-3 form-group">
            <label>From</label>
            <input type="time" name="availability[start_time]" class="form-control" value="{{ $availStart }}">
        </div>
        <div class="col-sm-3 form-group">
            <label>To</label>
            <input type="time" name="availability[end_time]" class="form-control" value="{{ $availEnd }}">
        </div>
        <div class="col-sm-3 form-group">
            <label>Timezone</label>
            <select name="availability[timezone]" class="form-control">
                @foreach($tzOptions as $tzValue => $tzLabel)
                    <option value="{{ $tzValue }}" @selected($availTz === $tzValue)>{{ $tzLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3 form-group">
            <label>Flexible schedule?</label>
            <select name="availability[flexible]" class="form-control">
                <option value="no" @selected($availFlexible === 'no' || $availFlexible === false || $availFlexible === '0')>No</option>
                <option value="yes" @selected($availFlexible === 'yes' || $availFlexible === true || $availFlexible === '1')>Yes</option>
            </select>
        </div>
    </div>
</div>

<div class="form-group mt-3">
    <label>Teaching Methodology</label>
    <select name="teaching_methodology[]" class="form-control" multiple size="3">
        @foreach($methodOptions as $value => $label)
            <option value="{{ $value }}" @selected(in_array($value, (array) $selectedMethods, true))>{{ $label }}</option>
        @endforeach
    </select>
    <span class="help-block">Hold Ctrl/Cmd to select multiple.</span>
</div>
