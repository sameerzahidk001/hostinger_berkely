{{-- Shared full-width people popup used by Students / Instructors overview cards. --}}
@php
    $modalId = $modalId ?? 'peopleMetricModal';
    $details = $details ?? [];
    $triggerSelector = $triggerSelector ?? '.js-people-metric';
    $enableCustomRange = (bool) ($enableCustomRange ?? false);
    $customMetricKey = $customMetricKey ?? 'reg_custom';
    $customFrom = $customFrom ?? '';
    $customTo = $customTo ?? '';
    $customFetchUrl = $customFetchUrl ?? '';
    $showPhone = (bool) ($showPhone ?? false);
    $colCount = $showPhone ? 10 : 9;
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label">
    <div class="modal-dialog people-metric-modal-dialog" role="document" style="width:96%;max-width:1400px;margin:20px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="{{ $modalId }}Label">People</h4>
            </div>
            <div class="modal-body" style="padding:12px 16px;">
                @if($enableCustomRange)
                <div id="{{ $modalId }}CustomRange" class="people-metric-custom-range" style="display:none;margin-bottom:12px;">
                    <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
                        <div class="form-group" style="margin:0;">
                            <label for="{{ $modalId }}From" class="m-r-xs">From</label>
                            <input type="date" id="{{ $modalId }}From" class="form-control" value="{{ $customFrom }}">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label for="{{ $modalId }}To" class="m-r-xs">To</label>
                            <input type="date" id="{{ $modalId }}To" class="form-control" value="{{ $customTo }}">
                        </div>
                        <button type="button" id="{{ $modalId }}Search" class="btn btn-primary btn-sm">Search</button>
                    </div>
                </div>
                @endif
                <div class="people-metric-table-wrap" style="max-height:65vh;overflow-y:auto;overflow-x:hidden;">
                    <table class="table table-striped table-bordered table-hover" style="width:100%;table-layout:fixed;margin:0;">
                        <thead>
                            <tr>
                                <th style="width:4%;">#</th>
                                <th style="width:{{ $showPhone ? '12%' : '14%' }};">Name</th>
                                <th style="width:{{ $showPhone ? '14%' : '18%' }};">Email</th>
                                @if($showPhone)
                                    <th style="width:11%;">Phone</th>
                                @endif
                                <th style="width:8%;">Gender</th>
                                <th style="width:{{ $showPhone ? '10%' : '12%' }};">Country</th>
                                <th style="width:9%;">City</th>
                                <th style="width:{{ $showPhone ? '10%' : '12%' }};">Nationality</th>
                                <th style="width:12%;">Registered date</th>
                                <th style="width:8%;">Profile</th>
                            </tr>
                        </thead>
                        <tbody id="{{ $modalId }}Body">
                            <tr><td colspan="{{ $colCount }}" class="text-center text-muted">No people found.</td></tr>
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
    var enableCustomRange = @json($enableCustomRange);
    var customMetricKey = @json($customMetricKey);
    var customFetchUrl = @json($customFetchUrl);
    var showPhone = @json($showPhone);
    var colCount = @json($colCount);

    function renderPeople(people, title, emptyMessage) {
        var modalTitle = document.getElementById(modalId + 'Label');
        var body = document.getElementById(modalId + 'Body');
        people = people || [];
        if (modalTitle) {
            modalTitle.textContent = (title || 'People') + ' (' + people.length + ')';
        }
        if (!body) {
            return;
        }
        if (!people.length) {
            body.innerHTML = '<tr><td colspan="' + colCount + '" class="text-center text-muted">'
                + (emptyMessage || 'No people found for this metric.')
                + '</td></tr>';
            return;
        }
        body.innerHTML = people.map(function (person, index) {
            var profile = person.url
                ? '<a href="' + person.url + '" target="_blank" rel="noopener">Open</a>'
                : '—';
            var row = '<tr>'
                + '<td>' + (index + 1) + '</td>'
                + '<td>' + (person.name || '—') + '</td>'
                + '<td>' + (person.email || '—') + '</td>';
            if (showPhone) {
                row += '<td>' + (person.phone || '—') + '</td>';
            }
            row += '<td>' + (person.gender || '—') + '</td>'
                + '<td>' + (person.country || '—') + '</td>'
                + '<td>' + (person.city || '—') + '</td>'
                + '<td>' + (person.nationality || '—') + '</td>'
                + '<td>' + (person.registered_at || '—') + '</td>'
                + '<td>' + profile + '</td>'
                + '</tr>';
            return row;
        }).join('');
    }

    function showModal() {
        if (window.jQuery) {
            window.jQuery('#' + modalId).modal('show');
        }
    }

    function loadCustomPeople() {
        if (!customFetchUrl) {
            return;
        }
        var fromEl = document.getElementById(modalId + 'From');
        var toEl = document.getElementById(modalId + 'To');
        var from = fromEl ? fromEl.value : '';
        var to = toEl ? toEl.value : '';
        if (!from || !to) {
            renderPeople([], 'Registered Instructors - Custom Date Range', 'Select From and To dates, then click Search.');
            return;
        }
        var body = document.getElementById(modalId + 'Body');
        if (body) {
            body.innerHTML = '<tr><td colspan="' + colCount + '" class="text-center text-muted">Loading…</td></tr>';
        }
        var url = customFetchUrl
            + (customFetchUrl.indexOf('?') === -1 ? '?' : '&')
            + 'date_from=' + encodeURIComponent(from)
            + '&date_to=' + encodeURIComponent(to);
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                renderPeople(data.people || [], data.title || 'Registered Instructors - Custom Date Range');
                var cardValue = document.querySelector(triggerSelector + '[data-metric="' + customMetricKey + '"] .instructor-stat-value, '
                    + triggerSelector + '[data-metric="' + customMetricKey + '"] .student-stat-value');
                if (cardValue && typeof data.count !== 'undefined') {
                    cardValue.textContent = String(data.count);
                }
            })
            .catch(function () {
                renderPeople([], 'Registered Instructors - Custom Date Range');
            });
    }

    document.querySelectorAll(triggerSelector).forEach(function (el) {
        el.addEventListener('click', function () {
            var key = el.getAttribute('data-metric') || '';
            var title = el.getAttribute('data-title') || 'People';
            var isCustom = enableCustomRange && (el.getAttribute('data-custom') === '1' || key === customMetricKey);
            var rangeBox = document.getElementById(modalId + 'CustomRange');
            if (rangeBox) {
                rangeBox.style.display = isCustom ? 'block' : 'none';
            }
            if (isCustom) {
                showModal();
                loadCustomPeople();
                return;
            }
            var bucket = details[key] || {};
            renderPeople(bucket.people || [], bucket.title || title);
            showModal();
        });
    });

    var searchBtn = document.getElementById(modalId + 'Search');
    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            loadCustomPeople();
        });
    }
})();
</script>
