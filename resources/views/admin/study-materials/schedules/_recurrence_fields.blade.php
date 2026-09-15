@php
    $supportsRecurrence = \App\Models\ClassSchedule::supportsRecurrenceColumns();
    $recurrenceType = old('recurrence_type', $schedule->recurrence_type ?? 'none');
    $selectedDays = collect(old('recurrence_days', $schedule->recurrence_days ?? []))->map(fn ($d) => strtoupper((string) $d))->all();
    if ($recurrenceType === 'weekly' && $selectedDays === [] && !empty($schedule->scheduled_at)) {
        $map = [1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU'];
        $selectedDays = [$map[$schedule->scheduled_at->dayOfWeekIso] ?? 'MO'];
    }
    $endMode = old('recurrence_end');
    if (! $endMode) {
        if (!empty($schedule->recurrence_until)) {
            $endMode = 'on';
        } elseif (!empty($schedule->recurrence_count)) {
            $endMode = 'after';
        } elseif (($schedule->recurrence_type ?? 'none') !== 'none') {
            $endMode = 'never';
        } else {
            $endMode = 'after';
        }
    }
    $dayLetters = ['SU' => 'S', 'MO' => 'M', 'TU' => 'T', 'WE' => 'W', 'TH' => 'T', 'FR' => 'F', 'SA' => 'S'];
    $defaultReminders = [
        ['action' => 'email', 'amount' => 1, 'unit' => 'days'],
        ['action' => 'email', 'amount' => 2, 'unit' => 'days'],
        ['action' => 'email', 'amount' => 4, 'unit' => 'days'],
        ['action' => 'popup', 'amount' => 1, 'unit' => 'days'],
    ];
    $reminderRows = old('reminders');
    if (! is_array($reminderRows)) {
        $stored = $schedule->reminders ?? null;
        if (is_array($stored) && $stored !== []) {
            $reminderRows = collect($stored)->map(function ($row) {
                $minutes = abs((int) ($row['minutes'] ?? 0));
                if ($minutes >= 1440 && $minutes % 1440 === 0) {
                    return ['action' => $row['action'] ?? 'email', 'amount' => (int) ($minutes / 1440), 'unit' => 'days'];
                }
                if ($minutes >= 60 && $minutes % 60 === 0) {
                    return ['action' => $row['action'] ?? 'email', 'amount' => (int) ($minutes / 60), 'unit' => 'hours'];
                }

                return ['action' => $row['action'] ?? 'email', 'amount' => max(1, $minutes), 'unit' => 'minutes'];
            })->all();
        } else {
            $reminderRows = $defaultReminders;
        }
    }
@endphp

@if($supportsRecurrence)
    <div class="col-md-12">
        <hr>
        <h4 style="margin-top:0;">Custom recurrence</h4>
        <p class="help-block">Pick multiple weekdays (like Zoho), then choose when the series ends.</p>
    </div>

    <div class="col-md-12 form-group">
        <div class="btn-group recurrence-tabs" role="group">
            <label class="btn btn-default {{ $recurrenceType === 'none' ? 'active' : '' }}">
                <input type="radio" name="recurrence_type" value="none" autocomplete="off" @checked($recurrenceType === 'none')> Does not repeat
            </label>
            <label class="btn btn-default {{ $recurrenceType === 'daily' ? 'active' : '' }}">
                <input type="radio" name="recurrence_type" value="daily" autocomplete="off" @checked($recurrenceType === 'daily')> Day
            </label>
            <label class="btn btn-default {{ in_array($recurrenceType, ['weekly','weekdays'], true) ? 'active' : '' }}">
                <input type="radio" name="recurrence_type" value="weekly" autocomplete="off" @checked(in_array($recurrenceType, ['weekly','weekdays'], true))> Week
            </label>
        </div>
    </div>

    <div class="col-md-12 form-group" id="recurrence_days_wrap" style="{{ in_array($recurrenceType, ['weekly','weekdays'], true) ? '' : 'display:none;' }}">
        <label>Repeats on <span class="text-muted">(select multiple days)</span></label>
        <div class="weekday-circles">
            @foreach($dayLetters as $code => $letter)
                <label class="weekday-circle {{ in_array($code, $selectedDays, true) ? 'is-on' : '' }}" title="{{ \App\Models\ClassSchedule::DAY_CODES[$code] }}">
                    <input type="checkbox" name="recurrence_days[]" value="{{ $code }}" @checked(in_array($code, $selectedDays, true))>
                    <span>{{ $letter }}</span>
                </label>
            @endforeach
        </div>
        <div id="recurrence_summary" class="recurrence-summary">Weekly on selected days</div>
    </div>

    <div class="col-md-12 form-group recurrence-ends" id="recurrence_ends_wrap" style="{{ $recurrenceType === 'none' ? 'display:none;' : '' }}">
        <label>Ends</label>
        <div class="radio">
            <label>
                <input type="radio" name="recurrence_end" value="never" @checked($endMode === 'never')>
                Never
            </label>
        </div>
        <div class="radio" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <label style="margin:0;">
                <input type="radio" name="recurrence_end" value="on" @checked($endMode === 'on')>
                On
            </label>
            <input type="date" name="recurrence_until" id="recurrence_until" class="form-control" style="width:auto; display:inline-block;"
                value="{{ old('recurrence_until', optional($schedule->recurrence_until ?? null)->format('Y-m-d') ?: now()->addMonths(3)->format('Y-m-d')) }}">
        </div>
        <div class="radio" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:6px;">
            <label style="margin:0;">
                <input type="radio" name="recurrence_end" value="after" @checked($endMode === 'after')>
                After
            </label>
            <input type="number" name="recurrence_count" id="recurrence_count" class="form-control" style="width:90px; display:inline-block;" min="1" max="52"
                value="{{ old('recurrence_count', $schedule->recurrence_count ?? 13) }}">
            <span>Occurrences</span>
        </div>
    </div>

    <div class="col-md-12 form-group">
        <label>Reminders</label>
        <div id="reminder-rows">
            @foreach($reminderRows as $index => $row)
                <div class="row reminder-row" style="margin-bottom:8px;">
                    <div class="col-sm-3">
                        <select name="reminders[{{ $index }}][action]" class="form-control">
                            <option value="email" @selected(($row['action'] ?? '') === 'email')>Email</option>
                            <option value="popup" @selected(($row['action'] ?? '') === 'popup')>Popup</option>
                            <option value="notification" @selected(($row['action'] ?? '') === 'notification')>Notification</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" value="before" disabled>
                    </div>
                    <div class="col-sm-2">
                        <input type="number" name="reminders[{{ $index }}][amount]" class="form-control" min="1" max="60" value="{{ $row['amount'] ?? 1 }}">
                    </div>
                    <div class="col-sm-3">
                        <select name="reminders[{{ $index }}][unit]" class="form-control">
                            <option value="minutes" @selected(($row['unit'] ?? '') === 'minutes')>minutes</option>
                            <option value="hours" @selected(($row['unit'] ?? '') === 'hours')>hours</option>
                            <option value="days" @selected(($row['unit'] ?? 'days') === 'days')>days</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <button type="button" class="btn btn-white btn-block remove-reminder" title="Remove">&times;</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-default btn-sm" id="add-reminder">+ Add reminder</button>
    </div>
@endif
