@php
    $reg = $studentOverviewStats['registration'] ?? [];
    $enr = $studentOverviewStats['enrollment'] ?? [];
    $details = $studentOverviewStatsDetails ?? ($studentOverviewDetails ?? []);
    $cards = [
        ['key' => 'reg_total', 'value' => (int) ($reg['total'] ?? 0), 'label' => 'Total Registered'],
        ['key' => 'reg_today', 'value' => (int) ($reg['today'] ?? 0), 'label' => 'Registered Today'],
        ['key' => 'reg_yesterday', 'value' => (int) ($reg['yesterday'] ?? 0), 'label' => 'Registered Yesterday'],
        ['key' => 'reg_this_week', 'value' => (int) ($reg['this_week'] ?? 0), 'label' => 'Registered This Week'],
        ['key' => 'reg_last_week', 'value' => (int) ($reg['last_week'] ?? 0), 'label' => 'Registered Last Week'],
        ['key' => 'reg_this_month', 'value' => (int) ($reg['this_month'] ?? 0), 'label' => 'Registered This Month'],
        ['key' => 'reg_last_month', 'value' => (int) ($reg['last_month'] ?? 0), 'label' => 'Registered Last Month'],
        ['key' => 'reg_this_year', 'value' => (int) ($reg['this_year'] ?? 0), 'label' => 'Registered This Year'],
        ['key' => 'enr_enrolled_total', 'value' => (int) ($enr['enrolled_total'] ?? 0), 'label' => 'Enrolled Total Students'],
        ['key' => 'enr_enrolled_paid', 'value' => (int) ($enr['enrolled_paid'] ?? 0), 'label' => 'Invoice Fully Paid'],
        ['key' => 'enr_partially_paid_students', 'value' => (int) ($enr['partially_paid_students'] ?? 0), 'label' => 'Partially Paid Students'],
        ['key' => 'enr_invoice_unpaid_students', 'value' => (int) ($enr['invoice_unpaid_students'] ?? 0), 'label' => 'Invoice Issued And Unpaid'],
    ];
@endphp
<div class="row" style="padding: 0 10px 12px;" id="students-overview-panel">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <h5 style="margin:0;font-weight:700;text-transform:uppercase;letter-spacing:.02em;">Students Overview</h5>
            </div>
            <div class="ibox-content">
                <style>
                    #students-overview-panel .student-overview-tabs > li > a {
                        color: #fff !important;
                        border: none !important;
                        border-radius: 3px 3px 0 0;
                        margin-right: 4px;
                        padding: 10px 18px;
                        font-weight: 700;
                        text-transform: uppercase;
                        background: #1c84c6;
                    }
                    #students-overview-panel .student-overview-tabs > li.active > a,
                    #students-overview-panel .student-overview-tabs > li > a:hover {
                        background: #155a8a;
                        color: #fff !important;
                    }
                    #students-overview-panel .student-stat-value {
                        font-size: 28px;
                        font-weight: 700;
                        line-height: 1.15;
                        margin: 0 0 6px;
                        color: #ed5565;
                    }
                    #students-overview-panel .student-stat-row {
                        display: flex;
                        flex-wrap: wrap;
                    }
                    #students-overview-panel .student-stat-row > [class*="col-"] {
                        display: flex;
                        float: none;
                        margin-bottom: 12px;
                        width: 20%;
                        max-width: 20%;
                        flex: 0 0 20%;
                    }
                    @media (max-width: 1199px) {
                        #students-overview-panel .student-stat-row > [class*="col-"] {
                            width: 33.333%;
                            max-width: 33.333%;
                            flex: 0 0 33.333%;
                        }
                    }
                    @media (max-width: 767px) {
                        #students-overview-panel .student-stat-row > [class*="col-"] {
                            width: 50%;
                            max-width: 50%;
                            flex: 0 0 50%;
                        }
                    }
                    #students-overview-panel a.student-stat {
                        display: flex;
                        flex-direction: column;
                        justify-content: flex-start;
                        width: 100%;
                        min-height: 96px;
                        border: 1px solid #e7eaec;
                        border-radius: 4px;
                        padding: 12px 14px;
                        background: #f9f9f9;
                        color: #676a6c;
                        text-decoration: none !important;
                        cursor: pointer;
                    }
                    #students-overview-panel a.student-stat:hover {
                        border-color: #1c84c6;
                        background: #fff;
                    }
                    #students-overview-panel .student-stat-label {
                        font-size: 11px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: .02em;
                        line-height: 1.35;
                    }
                </style>

                <ul class="nav nav-tabs student-overview-tabs" role="tablist" style="margin-bottom:16px;border-bottom:none;">
                    <li class="active">
                        <a href="#student-overview-registration" data-toggle="tab">Overview</a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="student-overview-registration">
                        <div class="row student-stat-row">
                            @foreach($cards as $card)
                                <div class="col-sm-3">
                                    <a href="javascript:void(0)"
                                       class="student-stat js-student-metric"
                                       data-metric="{{ $card['key'] }}"
                                       data-title="{{ $card['label'] }}">
                                        <div class="student-stat-value">{{ $card['value'] }}</div>
                                        <div class="student-stat-label">{{ $card['label'] }}</div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-muted small m-t-sm m-b-none">Click a box to view the list of people in that count.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="studentMetricModal" tabindex="-1" role="dialog" aria-labelledby="studentMetricModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="studentMetricModalLabel">People</h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive" style="max-height:60vh;overflow:auto;">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Details</th>
                                <th style="width:80px;">Profile</th>
                            </tr>
                        </thead>
                        <tbody id="studentMetricModalBody">
                            <tr><td colspan="5" class="text-center text-muted">No people found.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var details = @json($details);
    document.querySelectorAll('#students-overview-panel .js-student-metric').forEach(function (el) {
        el.addEventListener('click', function () {
            var key = el.getAttribute('data-metric') || '';
            var title = el.getAttribute('data-title') || 'People';
            var bucket = details[key] || {};
            var people = bucket.people || [];
            var modalTitle = document.getElementById('studentMetricModalLabel');
            var body = document.getElementById('studentMetricModalBody');
            if (modalTitle) {
                modalTitle.textContent = (bucket.title || title) + ' (' + people.length + ')';
            }
            if (!body) {
                return;
            }
            if (!people.length) {
                body.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No people found for this metric.</td></tr>';
            } else {
                body.innerHTML = people.map(function (person, index) {
                    var profile = person.url
                        ? '<a href="' + person.url + '" target="_blank" rel="noopener">Open</a>'
                        : '—';
                    return '<tr>'
                        + '<td>' + (index + 1) + '</td>'
                        + '<td>' + (person.name || '—') + '</td>'
                        + '<td>' + (person.email || '—') + '</td>'
                        + '<td>' + (person.note || '—') + '</td>'
                        + '<td>' + profile + '</td>'
                        + '</tr>';
                }).join('');
            }
            if (window.jQuery) {
                window.jQuery('#studentMetricModal').modal('show');
            }
        });
    });
})();
</script>
