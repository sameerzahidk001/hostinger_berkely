@php
    $reports = $contentReports ?? [];
    $metricLabels = $contentReportMetricLabels ?? [];
    $activeTab = request('report_tab', 'pages');
    if (! in_array($activeTab, ['pages', 'courses', 'seo'], true)) {
        $activeTab = 'pages';
    }
    $rangeFrom = request('date_from', $reports['date_from'] ?? '');
    $rangeTo = request('date_to', $reports['date_to'] ?? '');
    $reportActorKey = $contentReportActorKey ?? request('report_user', '');
    $showReportUserFilter = (bool) ($showContentReportUserFilter ?? false);
    $reportUsers = $contentReportUsers ?? collect();
@endphp

<div class="row" style="padding: 0 10px 12px;" id="content-reports-panel">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <h5 style="margin:0;">Content performance reports</h5>
                @if($showReportUserFilter)
                <form method="GET" action="{{ route('admin.home') }}" class="form-inline" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0;" id="contentReportUserForm">
                    @foreach(request()->except(['report_user', 'page']) as $key => $value)
                        @if(is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="content_report_user" class="m-r-xs" style="margin:0;font-weight:normal;">User</label>
                    <select id="content_report_user" name="report_user" class="form-control" style="min-width:240px;" onchange="this.form.submit()">
                        @foreach($reportUsers as $ru)
                            <option value="{{ $ru['key'] }}" @selected((string) $reportActorKey === (string) $ru['key'])>{{ $ru['label'] }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
                <small class="text-muted">Click any statistic for detailed records. Exports include your username.</small>
            </div>
            <div class="ibox-content">
                <ul class="nav nav-tabs" role="tablist" style="margin-bottom:16px;">
                    <li class="{{ $activeTab === 'pages' ? 'active' : '' }}">
                        <a href="#report-pages" data-toggle="tab">Pages</a>
                    </li>
                    <li class="{{ $activeTab === 'courses' ? 'active' : '' }}">
                        <a href="#report-courses" data-toggle="tab">Courses</a>
                    </li>
                    <li class="{{ $activeTab === 'seo' ? 'active' : '' }}">
                        <a href="#report-seo" data-toggle="tab">SEO</a>
                    </li>
                </ul>

                <div class="tab-content">
                    @foreach(['pages' => 'Pages', 'courses' => 'Courses', 'seo' => 'SEO'] as $module => $moduleTitle)
                        @php $stats = $reports[$module] ?? []; @endphp
                        <div role="tabpanel" class="tab-pane {{ $activeTab === $module ? 'active' : '' }}" id="report-{{ $module }}">
                            <div class="row">
                                @foreach(($metricLabels[$module] ?? []) as $metricKey => $metricLabel)
                                    @php $value = (int) ($stats[$metricKey] ?? 0); @endphp
                                    <div class="col-lg-3 col-md-4 col-sm-6" style="margin-bottom:12px;">
                                        <a href="javascript:void(0);"
                                            class="content-report-stat"
                                            data-module="{{ $module }}"
                                            data-metric="{{ $metricKey }}"
                                            data-label="{{ $metricLabel }}"
                                            style="display:block;border:1px solid #e7eaec;border-radius:4px;padding:14px 16px;background:#f9f9f9;color:#676a6c;text-decoration:none;min-height:88px;">
                                            <div style="font-size:12px;text-transform:uppercase;letter-spacing:.03em;">{{ $metricLabel }}</div>
                                            <div style="font-size:28px;font-weight:700;color:#1ab394;line-height:1.2;">{{ $value }}</div>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="contentReportModal" tabindex="-1" role="dialog" aria-labelledby="contentReportModalLabel">
    <div class="modal-dialog modal-lg" style="width:95%;max-width:1200px;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="contentReportModalLabel">Report details</h4>
            </div>
            <div class="modal-body">
                <div class="row" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
                    <div class="col-sm-3" style="padding-left:0;">
                        <input type="text" id="contentReportSearch" class="form-control" placeholder="Search title / keyword...">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="content_report_date_from" class="m-r-xs" style="font-weight:normal;font-size:12px;">From</label>
                        <input type="date" id="content_report_date_from" class="form-control" value="{{ $rangeFrom }}">
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="content_report_date_to" class="m-r-xs" style="font-weight:normal;font-size:12px;">To</label>
                        <input type="date" id="content_report_date_to" class="form-control" value="{{ $rangeTo }}">
                    </div>
                    <button type="button" id="contentReportApplyRange" class="btn btn-primary btn-sm">Apply range</button>
                    <div class="col-sm-auto text-right" style="margin-left:auto;padding-right:0;">
                        <a href="#" id="contentReportExportExcel" class="btn btn-success btn-sm" target="_blank">
                            <i class="fa fa-file-excel-o"></i> Export Excel
                        </a>
                        <a href="#" id="contentReportExportPdf" class="btn btn-danger btn-sm" target="_blank">
                            <i class="fa fa-file-pdf-o"></i> Export PDF
                        </a>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:65vh;overflow:auto;">
                    <table class="table table-striped table-bordered table-hover" id="contentReportTable">
                        <thead>
                            <tr>
                                <th data-sort-key="#" style="width:50px;cursor:pointer;"># <span class="sort-ind"></span></th>
                                <th data-sort-key="title" style="cursor:pointer;">Title <span class="sort-ind"></span></th>
                                <th data-sort-key="status" style="width:90px;cursor:pointer;">Status <span class="sort-ind"></span></th>
                                <th data-sort-key="seo_status" style="width:110px;cursor:pointer;">SEO Status <span class="sort-ind"></span></th>
                                <th data-sort-key="seo_score" style="width:90px;cursor:pointer;">SEO Score <span class="sort-ind"></span></th>
                                <th data-sort-key="created_by" style="width:130px;cursor:pointer;">Created By <span class="sort-ind"></span></th>
                                <th data-sort-key="updated_by" style="width:130px;cursor:pointer;">Updated By <span class="sort-ind"></span></th>
                                <th data-sort-key="created_at" style="width:120px;cursor:pointer;">Created <span class="sort-ind"></span></th>
                                <th data-sort-key="updated_at" style="width:120px;cursor:pointer;">Updated <span class="sort-ind"></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="9" class="text-center text-muted">Loading…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
(function ($) {
    var currentModule = 'pages';
    var currentMetric = 'total';
    var currentLabel = 'Total';
    var searchTimer = null;
    var detailsUrl = @json(route('admin.home.content-report-details'));
    var exportUrl = @json(route('admin.home.content-report-export'));
    var colCount = 9;
    var allRows = [];
    var sortKey = '#';
    var sortDir = 'asc';

    function filterQuery() {
        var params = new URLSearchParams(window.location.search);
        var formFrom = $('#content_report_date_from').val() || '';
        var formTo = $('#content_report_date_to').val() || '';
        return {
            date_from: formFrom || params.get('date_from') || '',
            date_to: formTo || params.get('date_to') || '',
            user_id: params.get('user_id') || '',
            report_user: params.get('report_user') || @json($reportActorKey)
        };
    }

    function buildUrl(base, extra) {
        var q = filterQuery();
        var params = Object.assign({}, q, extra || {});
        var usp = new URLSearchParams();
        Object.keys(params).forEach(function (key) {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                usp.set(key, params[key]);
            }
        });
        return base + '?' + usp.toString();
    }

    function scoreNum(val) {
        if (val === null || val === undefined || val === '' || val === '—') return -1;
        var m = String(val).match(/(\d+)/);
        return m ? parseInt(m[1], 10) : -1;
    }

    function sortRows(rows) {
        var key = sortKey;
        var dir = sortDir === 'asc' ? 1 : -1;
        var sorted = rows.slice();
        if (key === '#') {
            return dir === 1 ? sorted : sorted.reverse();
        }
        sorted.sort(function (a, b) {
            var av, bv;
            if (key === 'seo_score') {
                av = scoreNum(a.seo_score);
                bv = scoreNum(b.seo_score);
                return (av - bv) * dir;
            }
            av = String(a[key] || '').toLowerCase();
            bv = String(b[key] || '').toLowerCase();
            if (av < bv) return -1 * dir;
            if (av > bv) return 1 * dir;
            return 0;
        });
        return sorted;
    }

    function updateSortIndicators() {
        $('#contentReportTable thead th').each(function () {
            var key = $(this).data('sort-key');
            var ind = $(this).find('.sort-ind');
            if (key === sortKey) {
                ind.text(sortDir === 'asc' ? ' ▲' : ' ▼');
            } else {
                ind.text('');
            }
        });
    }

    function renderRows(rows) {
        if (!rows.length) {
            $('#contentReportTable tbody').html('<tr><td colspan="' + colCount + '" class="text-center text-muted">No records found.</td></tr>');
            return;
        }
        var sorted = sortRows(rows);
        var html = sorted.map(function (row, idx) {
            var title = row.url
                ? '<a href="' + row.url + '" target="_blank" rel="noopener">' + $('<div>').text(row.title || '').html() + '</a>'
                : $('<div>').text(row.title || '').html();
            return '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td>' + title + '</td>' +
                '<td>' + $('<div>').text(row.status || '—').html() + '</td>' +
                '<td>' + $('<div>').text(row.seo_status || '—').html() + '</td>' +
                '<td>' + $('<div>').text(row.seo_score || '—').html() + '</td>' +
                '<td>' + $('<div>').text(row.created_by || 'Admin').html() + '</td>' +
                '<td>' + $('<div>').text(row.updated_by || 'Admin').html() + '</td>' +
                '<td>' + $('<div>').text(row.created_at || '—').html() + '</td>' +
                '<td>' + $('<div>').text(row.updated_at || '—').html() + '</td>' +
                '</tr>';
        }).join('');
        $('#contentReportTable tbody').html(html);
        updateSortIndicators();
    }

    function loadDetails() {
        var q = $('#contentReportSearch').val() || '';
        $('#contentReportTable tbody').html('<tr><td colspan="' + colCount + '" class="text-center text-muted">Loading…</td></tr>');
        $('#contentReportModalLabel').text(currentLabel + ' — ' + currentModule.charAt(0).toUpperCase() + currentModule.slice(1));

        $('#contentReportExportExcel').attr('href', buildUrl(exportUrl, {
            module: currentModule,
            metric: currentMetric,
            format: 'excel',
            q: q
        }));
        $('#contentReportExportPdf').attr('href', buildUrl(exportUrl, {
            module: currentModule,
            metric: currentMetric,
            format: 'pdf',
            q: q
        }));

        $.getJSON(buildUrl(detailsUrl, {
            module: currentModule,
            metric: currentMetric,
            q: q
        })).done(function (resp) {
            allRows = (resp.rows || []).map(function (row) {
                if (!row.created_by || row.created_by === '-' || row.created_by === '—') {
                    row.created_by = 'Admin';
                }
                if (!row.updated_by || row.updated_by === '-' || row.updated_by === '—') {
                    row.updated_by = 'Admin';
                }
                return row;
            });
            renderRows(allRows);
        }).fail(function () {
            allRows = [];
            $('#contentReportTable tbody').html('<tr><td colspan="' + colCount + '" class="text-center text-danger">Failed to load report details.</td></tr>');
        });
    }

    $(document).on('click', '.content-report-stat', function () {
        currentModule = $(this).data('module');
        currentMetric = $(this).data('metric');
        currentLabel = $(this).data('label');
        sortKey = '#';
        sortDir = 'asc';
        $('#contentReportSearch').val('');
        $('#contentReportModal').modal('show');
        loadDetails();
    });

    $('#contentReportSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadDetails, 350);
    });

    $('#contentReportApplyRange').on('click', function () {
        loadDetails();
    });

    $('#contentReportTable thead').on('click', 'th[data-sort-key]', function () {
        var key = $(this).data('sort-key');
        if (sortKey === key) {
            sortDir = sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            sortKey = key;
            sortDir = key === '#' ? 'asc' : 'asc';
        }
        renderRows(allRows);
    });
})(jQuery);
</script>
@endpush
