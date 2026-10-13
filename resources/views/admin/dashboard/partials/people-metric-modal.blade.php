{{-- Shared full-width people popup used by Students / Instructors overview cards. --}}
@php
    $modalId = $modalId ?? 'peopleMetricModal';
    $panelId = $panelId ?? 'people-overview-panel';
    $details = $details ?? [];
    $triggerSelector = $triggerSelector ?? '.js-people-metric';
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label">
    <div class="modal-dialog people-metric-modal-dialog" role="document" style="width:96%;max-width:1400px;margin:20px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="{{ $modalId }}Label">People</h4>
            </div>
            <div class="modal-body" style="padding:12px 16px;">
                <div class="people-metric-table-wrap" style="max-height:65vh;overflow-y:auto;overflow-x:hidden;">
                    <table class="table table-striped table-bordered table-hover" style="width:100%;table-layout:fixed;margin:0;">
                        <thead>
                            <tr>
                                <th style="width:4%;">#</th>
                                <th style="width:14%;">Name</th>
                                <th style="width:18%;">Email</th>
                                <th style="width:8%;">Gender</th>
                                <th style="width:12%;">Country</th>
                                <th style="width:10%;">City</th>
                                <th style="width:12%;">Nationality</th>
                                <th style="width:14%;">Registered date</th>
                                <th style="width:8%;">Profile</th>
                            </tr>
                        </thead>
                        <tbody id="{{ $modalId }}Body">
                            <tr><td colspan="9" class="text-center text-muted">No people found.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
    #{{ $modalId }} .people-metric-table-wrap table th,
    #{{ $modalId }} .people-metric-table-wrap table td {
        white-space: normal;
        word-break: break-word;
        overflow-wrap: anywhere;
        vertical-align: top;
        font-size: 12px;
    }
</style>
<script>
(function () {
    var details = @json($details);
    var modalId = @json($modalId);
    var triggerSelector = @json($triggerSelector);
    document.querySelectorAll(triggerSelector).forEach(function (el) {
        el.addEventListener('click', function () {
            var key = el.getAttribute('data-metric') || '';
            var title = el.getAttribute('data-title') || 'People';
            var bucket = details[key] || {};
            var people = bucket.people || [];
            var modalTitle = document.getElementById(modalId + 'Label');
            var body = document.getElementById(modalId + 'Body');
            if (modalTitle) {
                modalTitle.textContent = (bucket.title || title) + ' (' + people.length + ')';
            }
            if (!body) {
                return;
            }
            if (!people.length) {
                body.innerHTML = '<tr><td colspan="9" class="text-center text-muted">No people found for this metric.</td></tr>';
            } else {
                body.innerHTML = people.map(function (person, index) {
                    var profile = person.url
                        ? '<a href="' + person.url + '" target="_blank" rel="noopener">Open</a>'
                        : '—';
                    return '<tr>'
                        + '<td>' + (index + 1) + '</td>'
                        + '<td>' + (person.name || '—') + '</td>'
                        + '<td>' + (person.email || '—') + '</td>'
                        + '<td>' + (person.gender || '—') + '</td>'
                        + '<td>' + (person.country || '—') + '</td>'
                        + '<td>' + (person.city || '—') + '</td>'
                        + '<td>' + (person.nationality || '—') + '</td>'
                        + '<td>' + (person.registered_at || '—') + '</td>'
                        + '<td>' + profile + '</td>'
                        + '</tr>';
                }).join('');
            }
            if (window.jQuery) {
                window.jQuery('#' + modalId).modal('show');
            }
        });
    });
})();
</script>
