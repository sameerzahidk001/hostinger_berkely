@php
    $dayCodes = \App\Models\User::teachingAvailabilityDays();
    $slotCodes = \App\Models\User::teachingAvailabilitySlots();
    $grid = method_exists($user, 'availabilityGrid') ? $user->availabilityGrid() : [];
    $flexible = in_array(($user->availabilityData()['flexible'] ?? 'no'), ['yes', true, 1, '1'], true);
@endphp
@if(method_exists($user, 'hasAvailabilityGrid') && $user->hasAvailabilityGrid())
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
        margin: 0;
    }
    .teaching-avail-cell .mark {
        color: transparent;
        font-size: 16px;
        line-height: 1;
    }
    .teaching-avail-cell.is-on {
        background: #3b82f6;
        border-color: #2563eb;
    }
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
        flex-wrap: wrap;
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
<div style="overflow-x:auto;">
    <table class="teaching-avail-table">
        <thead>
            <tr>
                <th></th>
                @foreach($dayCodes as $dayLabel)
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
                            <span class="teaching-avail-cell {{ $on ? 'is-on' : '' }}">
                                <span class="mark">✓</span>
                            </span>
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
    @if($flexible)
        <span>· Flexible schedule</span>
    @endif
</div>
@endif
