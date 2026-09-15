<style>
.recurrence-tabs > label { margin-right: 4px; }
.recurrence-tabs input[type=radio] { display: none; }
.recurrence-tabs .btn.active {
    background: #f7b731;
    border-color: #f7b731;
    color: #fff;
}
.weekday-circles {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 8px 0 10px;
}
.weekday-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid #d0d0d0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    margin: 0;
    font-weight: 600;
    background: #fff;
    color: #666;
}
.weekday-circle input { display: none; }
.weekday-circle.is-on {
    background: #f7b731;
    border-color: #f7b731;
    color: #fff;
}
.recurrence-summary {
    display: inline-block;
    background: #e8f4ff;
    color: #1c84c6;
    padding: 6px 10px;
    border-radius: 4px;
    font-size: 13px;
}
.meeting-auto-box {
    border: 1px solid #1c84c6;
    background: #f5faff;
    border-radius: 6px;
    padding: 12px 14px;
    margin-bottom: 12px;
}
.meeting-auto-box strong { color: #1c84c6; }
</style>
<script>
(function ($) {
    var dayNames = {SU:'Sunday',MO:'Monday',TU:'Tuesday',WE:'Wednesday',TH:'Thursday',FR:'Friday',SA:'Saturday'};

    function selectedDayCodes() {
        return $('.weekday-circle input:checked').map(function () { return this.value; }).get();
    }

    function updateSummary() {
        var type = $('input[name="recurrence_type"]:checked').val() || 'none';
        var days = selectedDayCodes().map(function (code) { return dayNames[code] || code; });
        var text = 'Does not repeat';
        if (type === 'daily') text = 'Daily';
        else if (type === 'weekly') text = days.length ? ('Weekly on ' + days.join(', ')) : 'Weekly — select days';
        $('#recurrence_summary').text(text);
    }

    function toggleRecurrenceFields() {
        var type = $('input[name="recurrence_type"]:checked').val() || 'none';
        $('.recurrence-tabs .btn').removeClass('active');
        $('input[name="recurrence_type"]:checked').closest('label').addClass('active');
        $('#recurrence_days_wrap').toggle(type === 'weekly');
        $('#recurrence_ends_wrap').toggle(type !== 'none');
        updateSummary();
    }

    function reindexReminders() {
        $('#reminder-rows .reminder-row').each(function (index) {
            $(this).find('select, input').each(function () {
                var name = $(this).attr('name');
                if (!name) return;
                $(this).attr('name', name.replace(/reminders\[\d+]/, 'reminders[' + index + ']'));
            });
        });
    }

    $(function () {
        toggleRecurrenceFields();

        $(document).on('change', 'input[name="recurrence_type"]', toggleRecurrenceFields);

        $(document).on('change', '.weekday-circle input', function () {
            $(this).closest('.weekday-circle').toggleClass('is-on', this.checked);
            updateSummary();
        });

        $('#add-reminder').on('click', function () {
            var index = $('#reminder-rows .reminder-row').length;
            if (index >= 8) return;
            var row = $(
                '<div class="row reminder-row" style="margin-bottom:8px;">' +
                    '<div class="col-sm-3"><select name="reminders[' + index + '][action]" class="form-control">' +
                        '<option value="email">Email</option><option value="popup">Popup</option><option value="notification">Notification</option>' +
                    '</select></div>' +
                    '<div class="col-sm-2"><input type="text" class="form-control" value="before" disabled></div>' +
                    '<div class="col-sm-2"><input type="number" name="reminders[' + index + '][amount]" class="form-control" min="1" max="60" value="1"></div>' +
                    '<div class="col-sm-3"><select name="reminders[' + index + '][unit]" class="form-control">' +
                        '<option value="minutes">minutes</option><option value="hours">hours</option><option value="days" selected>days</option>' +
                    '</select></div>' +
                    '<div class="col-sm-2"><button type="button" class="btn btn-white btn-block remove-reminder" title="Remove">&times;</button></div>' +
                '</div>'
            );
            $('#reminder-rows').append(row);
        });

        $(document).on('click', '.remove-reminder', function () {
            if ($('#reminder-rows .reminder-row').length <= 1) return;
            $(this).closest('.reminder-row').remove();
            reindexReminders();
        });

        $('form').on('submit', function () {
            var type = $('input[name="recurrence_type"]:checked').val();
            if (type === 'weekly' && selectedDayCodes().length === 0) {
                alert('Select at least one weekday for weekly recurrence.');
                return false;
            }
        });
    });
})(jQuery);
</script>
