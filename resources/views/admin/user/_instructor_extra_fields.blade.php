@php
    \App\Models\User::ensureInstructorExtraColumns();
    $methodOptions = \App\Models\User::teachingMethodologyOptions();
    $selectedMethods = old('teaching_methodology', $user->teachingMethodologyList());
    $availability = old('availability', $user->availabilityData());
    $availFrequency = $availability['frequency'] ?? 'particular';
    $availDays = $availability['days'] ?? [];
    if (! is_array($availDays)) {
        $availDays = [];
    }
    if ($availDays !== [] && array_keys($availDays) !== range(0, count($availDays) - 1)) {
        $availDays = array_keys($availDays);
    }
    $daySlots = $availability['day_slots'] ?? [];
    if (! is_array($daySlots)) {
        $daySlots = [];
    }
    $availStart = $availability['start_time'] ?? '';
    $availEnd = $availability['end_time'] ?? '';
    $availTz = $availability['timezone'] ?? 'Asia/Dubai';
    $availFlexible = old('availability.flexible', $availability['flexible'] ?? 'no');
    $dayCodes = ['MO' => 'Mon', 'TU' => 'Tue', 'WE' => 'Wed', 'TH' => 'Thu', 'FR' => 'Fri', 'SA' => 'Sat', 'SU' => 'Sun'];
    $tzOptions = \App\Models\ClassSchedule::timezoneOptions();
@endphp

<div class="form-group mt-4">
    <label>Instructor’s availability</label>
    <div class="radio" style="margin-bottom:8px;">
        <label style="margin-right:16px;">
            <input type="radio" name="availability[frequency]" value="daily" class="js-avail-freq" @checked($availFrequency === 'daily')> Daily
        </label>
        <label>
            <input type="radio" name="availability[frequency]" value="particular" class="js-avail-freq" @checked($availFrequency !== 'daily')> Particular days
        </label>
    </div>

    <div id="availability-daily-times" style="{{ $availFrequency === 'daily' ? '' : 'display:none;' }}">
        <div class="row">
            <div class="col-sm-4 form-group">
                <label>From</label>
                <input type="time" name="availability[start_time]" class="form-control" value="{{ $availStart }}">
            </div>
            <div class="col-sm-4 form-group">
                <label>To</label>
                <input type="time" name="availability[end_time]" class="form-control" value="{{ $availEnd }}">
            </div>
            <div class="col-sm-4 form-group">
                <label>Timezone</label>
                <select name="availability[timezone]" class="form-control">
                    @foreach($tzOptions as $tzValue => $tzLabel)
                        <option value="{{ $tzValue }}" @selected($availTz === $tzValue)>{{ $tzLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="availability-days-wrap" style="{{ $availFrequency === 'daily' ? 'display:none;' : '' }};margin-bottom:10px;">
        <p class="help-block" style="margin-bottom:8px;">Fill From / To / Timezone for each day separately.</p>
        @foreach($dayCodes as $code => $label)
            @php
                $checked = in_array($code, (array) $availDays, true);
                $slot = $daySlots[$code] ?? [];
                $slotStart = $slot['start_time'] ?? ($checked ? $availStart : '');
                $slotEnd = $slot['end_time'] ?? ($checked ? $availEnd : '');
                $slotTz = $slot['timezone'] ?? ($checked ? $availTz : 'Asia/Dubai');
            @endphp
            <div class="availability-day-block" style="border:1px solid #e5e5e5;padding:10px 12px;margin-bottom:8px;border-radius:4px;">
                <label class="checkbox-inline" style="font-weight:600;margin-bottom:8px;">
                    <input type="checkbox" name="availability[days][]" value="{{ $code }}" class="js-avail-day" data-day="{{ $code }}" @checked($checked)> {{ $label }}
                </label>
                <div class="row js-avail-day-times" data-day="{{ $code }}" style="{{ $checked ? '' : 'display:none;' }}">
                    <div class="col-sm-3 form-group" style="margin-bottom:0;">
                        <label>From</label>
                        <input type="time" name="availability[day_slots][{{ $code }}][start_time]" class="form-control" value="{{ $slotStart }}">
                    </div>
                    <div class="col-sm-3 form-group" style="margin-bottom:0;">
                        <label>To</label>
                        <input type="time" name="availability[day_slots][{{ $code }}][end_time]" class="form-control" value="{{ $slotEnd }}">
                    </div>
                    <div class="col-sm-4 form-group" style="margin-bottom:0;">
                        <label>Timezone</label>
                        <select name="availability[day_slots][{{ $code }}][timezone]" class="form-control">
                            @foreach($tzOptions as $tzValue => $tzLabel)
                                <option value="{{ $tzValue }}" @selected($slotTz === $tzValue)>{{ $tzLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="form-group" style="max-width:220px;margin-top:8px;">
        <label>Flexible schedule?</label>
        <select name="availability[flexible]" class="form-control">
            <option value="no" @selected($availFlexible === 'no' || $availFlexible === false || $availFlexible === '0')>No</option>
            <option value="yes" @selected($availFlexible === 'yes' || $availFlexible === true || $availFlexible === '1')>Yes</option>
        </select>
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
