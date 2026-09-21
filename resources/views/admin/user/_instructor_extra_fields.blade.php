@php
    \App\Models\User::ensureInstructorExtraColumns();
    $methodOptions = \App\Models\User::teachingMethodologyOptions();
    $selectedMethods = old('teaching_methodology', $user->teachingMethodologyList());
    $availability = old('availability', $user->availabilityData());
    $availFlexible = old('availability.flexible', $availability['flexible'] ?? 'no');
    $dayCodes = \App\Models\User::teachingAvailabilityDays();
    $slotCodes = \App\Models\User::teachingAvailabilitySlots();
    $grid = old('availability.grid');
    if (! is_array($grid)) {
        $grid = $user->availabilityGrid();
    } else {
        // Normalize checkbox post into bool matrix
        $normalized = [];
        foreach ($dayCodes as $day => $_) {
            $normalized[$day] = [];
            foreach ($slotCodes as $slot => $__) {
                $normalized[$day][$slot] = in_array($grid[$day][$slot] ?? null, [true, 1, '1', 'on', 'yes'], true);
            }
        }
        $grid = $normalized;
    }
@endphp

<style>
    .teaching-avail-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 6px;
        margin-bottom: 8px;
    }
    .teaching-avail-table th,
    .teaching-avail-table td {
        text-align: center;
        vertical-align: middle;
        font-size: 13px;
    }
    .teaching-avail-table th {
        color: #333;
        font-weight: 600;
        padding: 6px 4px;
    }
    .teaching-avail-table th.slot-label {
        text-align: left;
        padding-left: 8px;
        white-space: nowrap;
        color: #444;
        font-weight: 600;
    }
    .teaching-avail-cell {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 36px;
        border-radius: 8px;
        border: 1px solid #d9d9d9;
        background: #f3f3f3;
        cursor: pointer;
        margin: 0;
        position: relative;
    }
    .teaching-avail-cell input {
        position: absolute;
        opacity: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        margin: 0;
    }
    .teaching-avail-cell .mark {
        color: transparent;
        font-size: 16px;
        line-height: 1;
        pointer-events: none;
    }
    .teaching-avail-cell:has(input:checked),
    .teaching-avail-cell.is-on {
        background: #3b82f6;
        border-color: #2563eb;
    }
    .teaching-avail-cell:has(input:checked) .mark,
    .teaching-avail-cell.is-on .mark {
        color: #fff;
    }
    .teaching-avail-legend {
        display: flex;
        gap: 16px;
        align-items: center;
        font-size: 12px;
        color: #666;
        margin-top: 6px;
    }
    .teaching-avail-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .teaching-avail-swatch {
        width: 18px;
        height: 14px;
        border-radius: 4px;
        display: inline-block;
    }
    .teaching-avail-swatch.on { background: #3b82f6; }
    .teaching-avail-swatch.off { background: #f3f3f3; border: 1px solid #d9d9d9; }
</style>

<h3 class="profile-section-heading">Teaching Availability</h3>
<div class="form-group">
    <input type="hidden" name="availability[type]" value="grid">
    <p class="help-block">Click a cell to mark when you are available. Blue = available.</p>
    <div style="overflow-x:auto;">
        <table class="teaching-avail-table">
            <thead>
                <tr>
                    <th></th>
                    @foreach($dayCodes as $day => $dayLabel)
                        <th>{{ $dayLabel }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($slotCodes as $slot => $slotLabel)
                    <tr>
                        <th class="slot-label">{{ $slotLabel }}</th>
                        @foreach($dayCodes as $day => $dayLabel)
                            @php $on = ! empty($grid[$day][$slot]); @endphp
                            <td>
                                <label class="teaching-avail-cell {{ $on ? 'is-on' : '' }}">
                                    <input type="checkbox"
                                        name="availability[grid][{{ $day }}][{{ $slot }}]"
                                        value="1"
                                        @checked($on)
                                        onchange="this.parentElement.classList.toggle('is-on', this.checked)">
                                    <span class="mark">✓</span>
                                </label>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="teaching-avail-legend">
        <span><i class="teaching-avail-swatch on"></i> Available</span>
        <span><i class="teaching-avail-swatch off"></i> Not available</span>
    </div>
    <div class="form-group" style="max-width:220px;margin-top:12px;">
        <label>Flexible schedule?</label>
        <select name="availability[flexible]" class="form-control">
            <option value="no" @selected($availFlexible === 'no' || $availFlexible === false || $availFlexible === '0')>No</option>
            <option value="yes" @selected($availFlexible === 'yes' || $availFlexible === true || $availFlexible === '1')>Yes</option>
        </select>
    </div>
</div>

<h3 class="profile-section-heading">Teaching Methodology</h3>
<div class="form-group">
    <select name="teaching_methodology[]" class="form-control" multiple size="3">
        @foreach($methodOptions as $value => $label)
            <option value="{{ $value }}" @selected(in_array($value, (array) $selectedMethods, true))>{{ $label }}</option>
        @endforeach
    </select>
    <span class="help-block">Hold Ctrl/Cmd to select multiple.</span>
</div>
