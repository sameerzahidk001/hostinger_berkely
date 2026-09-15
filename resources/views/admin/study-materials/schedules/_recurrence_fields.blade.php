@php
    $supportsRecurrence = \App\Models\ClassSchedule::supportsRecurrenceColumns();
    $recurrenceType = old('recurrence_type', $schedule->recurrence_type ?? 'none');
    $selectedDays = collect(old('recurrence_days', $schedule->recurrence_days ?? []))->map(fn ($d) => strtoupper((string) $d))->all();
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
        <h4 style="margin-top:0;">Days, recurrence &amp; reminders</h4>
        <p class="help-block">Same options as Zoho Calendar when the class is synced: choose whether it repeats, on which days, and when to remind students.</p>
    </div>

    <div class="col-md-4 form-group">
        <label>Repeat</label>
        <select name="recurrence_type" id="recurrence_type" class="form-control">
            <option value="none" @selected($recurrenceType === 'none')>Does not repeat</option>
            <option value="daily" @selected($recurrenceType === 'daily')>Daily</option>
            <option value="weekdays" @selected($recurrenceType === 'weekdays')>Every weekday (Mon–Fri)</option>
            <option value="weekly" @selected($recurrenceType === 'weekly')>Weekly on selected days</option>
        </select>
    </div>
    <div class="col-md-4 form-group">
        <label>Ends after (classes)</label>
        <input type="number" name="recurrence_count" id="recurrence_count" class="form-control" min="1" max="52"
            value="{{ old('recurrence_count', $schedule->recurrence_count ?? 12) }}"
            @disabled($recurrenceType === 'none')>
        <span class="help-block">Used when no end date is set.</span>
    </div>
    <div class="col-md-4 form-group">
        <label>Or end date</label>
        <input type="date" name="recurrence_until" id="recurrence_until" class="form-control"
            value="{{ old('recurrence_until', optional($schedule->recurrence_until ?? null)->format('Y-m-d')) }}"
            @disabled($recurrenceType === 'none')>
    </div>

    <div class="col-md-12 form-group" id="recurrence_days_wrap" style="{{ $recurrenceType === 'weekly' ? '' : 'display:none;' }}">
        <label>Repeat on</label>
        <div class="row">
            @foreach(\App\Models\ClassSchedule::DAY_CODES as $code => $label)
                <div class="col-xs-6 col-sm-3 col-md-1" style="margin-bottom:6px;">
                    <label class="checkbox-inline" style="padding-left:0;">
                        <input type="checkbox" name="recurrence_days[]" value="{{ $code }}" @checked(in_array($code, $selectedDays, true))>
                        {{ $label }}
                    </label>
                </div>
            @endforeach
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
