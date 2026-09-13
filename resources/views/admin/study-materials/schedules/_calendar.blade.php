@push('style')
<link href="{{ asset('/admin/css/plugins/fullcalendar/fullcalendar.css') }}" rel="stylesheet">
<style>
#class-calendar { background:#fff; }
.fc-event { cursor: pointer; }
</style>
@endpush
<div id="class-calendar"></div>
@push('script')
<script src="{{ asset('/admin/js/plugins/fullcalendar/moment.min.js') }}"></script>
<script src="{{ asset('/admin/js/plugins/fullcalendar/fullcalendar.min.js') }}"></script>
<script>
(function () {
    var events = @json($calendarEvents ?? []);
    $('#class-calendar').fullCalendar({
        header: { left: 'prev,next today', center: 'title', right: 'month,agendaWeek,agendaDay' },
        defaultView: 'month',
        editable: false,
        eventLimit: true,
        timeFormat: 'H:mm',
        events: events
    });
})();
</script>
@endpush
