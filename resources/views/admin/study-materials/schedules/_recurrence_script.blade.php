<script>
(function ($) {
    function toggleRecurrenceFields() {
        var type = $('#recurrence_type').val() || 'none';
        var repeating = type !== 'none';
        $('#recurrence_count, #recurrence_until').prop('disabled', !repeating);
        $('#recurrence_days_wrap').toggle(type === 'weekly');
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
        $('#recurrence_type').on('change', toggleRecurrenceFields);

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
    });
})(jQuery);
</script>
