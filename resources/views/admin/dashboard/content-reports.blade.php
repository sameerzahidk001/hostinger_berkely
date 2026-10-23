@php
    $reports = $contentReports ?? [];
    $metricLabels = $contentReportMetricLabels ?? [];
    $activeTab = request('report_tab', 'courses');
    if (! in_array($activeTab, ['courses', 'pages', 'seo', 'training', 'fees'], true)) {
        $activeTab = 'courses';
    }
    $rangeFrom = request('date_from', $reports['date_from'] ?? '');
    $rangeTo = request('date_to', $reports['date_to'] ?? '');
    $reportActorKey = $contentReportActorKey ?? request('report_user', '');
    $showReportUserFilter = (bool) ($showContentReportUserFilter ?? false);
    $reportUsers = $contentReportUsers ?? collect();
@endphp

<div id="content-reports-panel" class="content-reports-panel" style="padding: 0 10px 12px;">
    <div>
        <div class="ibox float-e-margins">
            <div class="ibox-content">
                @if($showReportUserFilter)
                <form method="GET" action="{{ $panelBaseUrl ?? route('admin.home') }}" class="form-inline" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;justify-content:flex-end;margin:0 0 12px;" id="contentReportUserForm">
                    @foreach(request()->except(['report_user', 'page']) as $key => $value)
                        @if(is_scalar($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="content_report_user" class="m-r-xs" style="margin:0;font-weight:700;text-transform:uppercase;font-size:12px;">Search by User</label>
                    <select id="content_report_user" name="report_user" class="form-control" style="width:280px;max-width:280px;">
                        @foreach($reportUsers as $ru)
                            <option value="{{ $ru['key'] }}" @selected((string) $reportActorKey === (string) $ru['key'])>{{ $ru['label'] }}</option>
                        @endforeach
                    </select>
                </form>
                @endif
                <style>
                    .content-reports-panel .content-report-module-tabs {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 4px;
                    }
                    #content-reports-panel .content-report-module-tabs > li > a {
                        color: #fff !important;
                        border: none !important;
                        border-radius: 3px 3px 0 0;
                        margin-right: 4px;
                        padding: 10px 18px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: .02em;
                    }
                    #content-reports-panel .btn {
                        font-weight: 700;
                        text-transform: uppercase;
                    }
                    #content-reports-panel .content-report-module-tabs > li > a.tab-pages { background: #800000; }
                    #content-reports-panel .content-report-module-tabs > li > a.tab-courses { background: #000080; }
                    #content-reports-panel .content-report-module-tabs > li > a.tab-seo { background: #b8860b; }
                    #content-reports-panel .content-report-module-tabs > li > a.tab-training { background: #6f2dbd; }
                    #content-reports-panel .content-report-module-tabs > li > a.tab-fees { background: #2d6a4f; }
                    #content-reports-panel .content-report-module-tabs > li.active > a.tab-pages,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-pages:hover,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-pages:focus { background: #5c0000; color: #fff !important; }
                    #content-reports-panel .content-report-module-tabs > li.active > a.tab-courses,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-courses:hover,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-courses:focus { background: #00005c; color: #fff !important; }
                    #content-reports-panel .content-report-module-tabs > li.active > a.tab-seo,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-seo:hover,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-seo:focus { background: #8b6914; color: #fff !important; }
                    #content-reports-panel .content-report-module-tabs > li.active > a.tab-training,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-training:hover,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-training:focus { background: #551a99; color: #fff !important; }
                    #content-reports-panel .content-report-module-tabs > li.active > a.tab-fees,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-fees:hover,
                    #content-reports-panel .content-report-module-tabs > li > a.tab-fees:focus { background: #1b4332; color: #fff !important; }
                    #content-reports-panel .content-report-stat-value {
                        font-size: 28px;
                        font-weight: 700;
                        line-height: 1.15;
                        margin: 0 0 6px;
                    }
                    #content-reports-panel .content-report-stat-value.is-custom-icon {
                        color: #1c84c6 !important;
                        font-size: 26px;
                    }
                    #report-pages .content-report-stat-value { color: #800000; }
                    #report-courses .content-report-stat-value { color: #000080; }
                    #report-seo .content-report-stat-value { color: #b8860b; }
                    #report-training .content-report-stat-value { color: #6f2dbd; }
                    #report-fees .content-report-stat-value { color: #2d6a4f; }
                    #content-reports-panel .content-report-stat-row {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 12px;
                    }
                    #content-reports-panel .content-report-stat-item {
                        flex: 1 1 230px;
                        min-width: 0;
                        display: flex;
                    }
                    #content-reports-panel a.content-report-stat {
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
                        text-decoration: none;
                    }
                    #content-reports-panel a.content-report-stat-range {
                        justify-content: center;
                        min-height: 96px;
                    }
                    #content-reports-panel .content-report-stat-label {
                        font-size: 12px;
                        font-weight: 700;
                        text-transform: uppercase;
                        letter-spacing: .03em;
                        line-height: 1.3;
                        margin: 0;
                        overflow-wrap: anywhere;
                        word-break: break-word;
                    }
                </style>
                <h3 class="content-reports-heading" style="margin:0 0 12px;font-size:16px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;color:#2f4050;">
                    Website Content Reports
                </h3>
                <ul class="nav nav-tabs content-report-module-tabs" role="tablist" style="margin-bottom:16px;border-bottom:none;">
                    <li class="{{ $activeTab === 'courses' ? 'active' : '' }}">
                        <a href="#report-courses" data-toggle="tab" data-module="courses" class="tab-courses">Courses</a>
                    </li>
                    <li class="{{ $activeTab === 'pages' ? 'active' : '' }}">
                        <a href="#report-pages" data-toggle="tab" data-module="pages" class="tab-pages">Pages</a>
                    </li>
                    <li class="{{ $activeTab === 'seo' ? 'active' : '' }}">
                        <a href="#report-seo" data-toggle="tab" data-module="seo" class="tab-seo">SEO Active</a>
                    </li>
                    <li class="{{ $activeTab === 'training' ? 'active' : '' }}">
                        <a href="#report-training" data-toggle="tab" data-module="training" class="tab-training">Training Calendar</a>
                    </li>
                    <li class="{{ $activeTab === 'fees' ? 'active' : '' }}">
                        <a href="#report-fees" data-toggle="tab" data-module="fees" class="tab-fees">Fee Package</a>
                    </li>
                </ul>

                <div class="tab-content">
                    @foreach(['courses' => 'Courses', 'pages' => 'Pages', 'seo' => 'SEO Active', 'training' => 'Training Calendar', 'fees' => 'Fee Package'] as $module => $moduleTitle)
                        @php $stats = $reports[$module] ?? []; @endphp
                        <div role="tabpanel" class="tab-pane {{ $activeTab === $module ? 'active' : '' }}" id="report-{{ $module }}">
                            <div class="content-report-stat-row">
                                @foreach(($metricLabels[$module] ?? []) as $metricKey => $metricLabel)
                                    @php
                                        $value = (int) ($stats[$metricKey] ?? 0);
                                        $hideValue = in_array($metricKey, ['created_in_range', 'updated_in_range'], true);
                                    @endphp
                                    <div class="content-report-stat-item">
                                        <a href="javascript:void(0);"
                                            class="content-report-stat{{ $hideValue ? ' content-report-stat-range' : '' }}"
                                            data-module="{{ $module }}"
                                            data-metric="{{ $metricKey }}"
                                            data-label="{{ $metricLabel }}"
                                            data-count="{{ $hideValue ? 0 : $value }}">
                                            @if($hideValue)
                                                <div class="content-report-stat-value is-custom-icon"><i class="fa fa-calendar"></i></div>
                                            @else
                                                <div class="content-report-stat-value">{{ $value }}</div>
                                            @endif
                                            <div class="content-report-stat-label">{{ $metricLabel }}</div>
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
                <h4 class="modal-title" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0;font-weight:700;text-transform:uppercase;letter-spacing:.02em;">
                    <span id="contentReportModalLabel">Website Content Reports</span>
                    <span id="contentReportModalCount" style="display:inline-block;min-width:42px;padding:4px 10px;border:1px solid #cfd4d8;border-radius:3px;background:#f5f5f5;font-size:14px;font-weight:700;color:#333;text-align:center;text-transform:none;letter-spacing:0;">0</span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
                    <div class="col-sm-3" style="padding-left:0;">
                        <input type="text" id="contentReportSearch" class="form-control" placeholder="Search title / keyword...">
                    </div>
                    <div id="contentReportRangeFilters" style="display:none;flex-wrap:wrap;gap:8px;align-items:flex-end;">
                        <div class="form-group" style="margin:0;">
                            <label for="content_report_date_from" class="m-r-xs" style="font-weight:normal;font-size:12px;">From</label>
                            <input type="date" id="content_report_date_from" class="form-control" value="{{ $rangeFrom }}">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label for="content_report_date_to" class="m-r-xs" style="font-weight:normal;font-size:12px;">To</label>
                            <input type="date" id="content_report_date_to" class="form-control" value="{{ $rangeTo }}">
                        </div>
                        <button type="button" id="contentReportApplyRange" class="btn btn-primary btn-sm">Apply range</button>
                    </div>
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
                            <tr id="contentReportTableHeadRow"></tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="10" class="text-center text-muted">Loading…</td></tr>
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
    var currentModule = 'courses';
    var currentMetric = 'total';
    var currentLabel = 'Total';
    var currentCountHint = 0;
    var moduleTitles = {
        courses: 'COURSES',
        pages: 'PAGES',
        seo: 'SEO ACTIVE',
        training: 'TRAINING CALENDAR',
        fees: 'FEE PACKAGE'
    };

    function moduleTitle(module) {
        return moduleTitles[module] || String(module || '').toUpperCase();
    }
    var searchTimer = null;
    var detailsUrl = @json(route('admin.home.content-report-details'));
    var exportUrl = @json(route('admin.home.content-report-export'));
    var activityUrl = @json(route('admin.home.content-report-activity'));
    var csrfToken = @json(csrf_token());
    var defaultColumns = [
        { key: 'title', label: 'Title' },
        { key: 'status', label: 'Status' },
        { key: 'type', label: 'Type' },
        { key: 'seo_status', label: 'SEO Status' },
        { key: 'seo_score', label: 'SEO Score' },
        { key: 'created_by', label: 'Created By' },
        { key: 'created_at', label: 'Created Date' },
        { key: 'updated_by', label: 'Updated By' },
        { key: 'updated_at', label: 'Updated Date' }
    ];
    var currentColumns = defaultColumns.slice();
    var colCount = currentColumns.length + 1;
    var allRows = [];
    var sortKey = '#';
    var sortDir = 'asc';

    function isSelectedRangeMetric(metric) {
        return metric === 'created_in_range' || metric === 'updated_in_range';
    }

    function logContentReportActivity(action, item) {
        try {
            var payload = {
                _token: csrfToken,
                action: action,
                item: item,
                report_user: (filterQuery().report_user || '')
            };
            // Prefer beacon so filter-change navigation still records the click.
            if (navigator.sendBeacon) {
                var body = new Blob([$.param(payload)], { type: 'application/x-www-form-urlencoded;charset=UTF-8' });
                navigator.sendBeacon(activityUrl, body);
                return;
            }
            $.ajax({
                url: activityUrl,
                method: 'POST',
                async: false,
                data: payload
            });
        } catch (e) {}
    }

    function linkColumnKey(columns) {
        for (var i = 0; i < columns.length; i++) {
            if (columns[i].key === 'package_name' || columns[i].key === 'course' || columns[i].key === 'title') {
                return columns[i].key;
            }
        }
        return columns.length ? columns[0].key : 'title';
    }

    function renderTableHead(columns) {
        currentColumns = columns && columns.length ? columns : defaultColumns.slice();
        colCount = currentColumns.length + 1;
        var html = '<th data-sort-key="#" style="width:50px;cursor:pointer;"># <span class="sort-ind"></span></th>';
        currentColumns.forEach(function (col) {
            html += '<th data-sort-key="' + col.key + '" style="cursor:pointer;">'
                + $('<div>').text(col.label || col.key).html()
                + ' <span class="sort-ind"></span></th>';
        });
        $('#contentReportTableHeadRow').html(html);
        updateSortIndicators();
    }

    renderTableHead(defaultColumns);

    function syncRangeFilterVisibility() {
        var show = isSelectedRangeMetric(currentMetric);
        $('#contentReportRangeFilters').css('display', show ? 'flex' : 'none');
    }

    function filterQuery() {
        var params = new URLSearchParams(window.location.search);
        var q = {
            user_id: params.get('user_id') || '',
            report_user: params.get('report_user') || @json($reportActorKey)
        };
        // Date range applies only to Selected Range metrics.
        if (isSelectedRangeMetric(currentMetric)) {
            q.date_from = $('#content_report_date_from').val() || params.get('date_from') || '';
            q.date_to = $('#content_report_date_to').val() || params.get('date_to') || '';
        }
        return q;
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
        var linkKey = linkColumnKey(currentColumns);
        var html = sorted.map(function (row, idx) {
            var cells = '<td>' + (idx + 1) + '</td>';
            currentColumns.forEach(function (col) {
                var key = col.key;
                var raw = row[key];
                if (raw === null || raw === undefined || raw === '' || raw === '-' || raw === '—') {
                    raw = (key === 'created_by' || key === 'updated_by') ? 'Admin' : '—';
                }
                var text = $('<div>').text(String(raw)).html();
                if (key === linkKey && row.url) {
                    text = '<a href="' + row.url + '" target="_blank" rel="noopener">' + text + '</a>';
                }
                if (key === linkKey && row.web_url) {
                    text += ' <a href="' + row.web_url + '" target="_blank" rel="noopener" style="font-weight:normal;">(Visit Web)</a>';
                }
                cells += '<td>' + text + '</td>';
            });
            return '<tr>' + cells + '</tr>';
        }).join('');
        $('#contentReportTable tbody').html(html);
        updateSortIndicators();
    }

    function loadDetails() {
        var q = $('#contentReportSearch').val() || '';
        $('#contentReportTable tbody').html('<tr><td colspan="' + colCount + '" class="text-center text-muted">Loading…</td></tr>');
        $('#contentReportModalCount').text('…');

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
            if (resp.label) {
                currentLabel = resp.label;
            }
            $('#contentReportModalLabel').text(
                moduleTitle(currentModule) + ' -> ' + String(currentLabel || '').toUpperCase()
            );
            renderTableHead(resp.columns || defaultColumns);
            allRows = (resp.rows || []).map(function (row) {
                if (!row.created_by || row.created_by === '-' || row.created_by === '—') {
                    row.created_by = 'Admin';
                }
                if (!row.updated_by || row.updated_by === '-' || row.updated_by === '—') {
                    row.updated_by = 'Admin';
                }
                return row;
            });
            // Box count must match rows currently shown in the table.
            $('#contentReportModalCount').text(allRows.length);
            renderRows(allRows);
        }).fail(function () {
            allRows = [];
            $('#contentReportModalCount').text('0');
            $('#contentReportTable tbody').html('<tr><td colspan="' + colCount + '" class="text-center text-danger">Failed to load report details.</td></tr>');
        });
    }

    $(document).on('click', '.content-report-stat', function () {
        currentModule = $(this).data('module');
        currentMetric = $(this).data('metric');
        currentLabel = $(this).data('label');
        currentCountHint = parseInt($(this).attr('data-count'), 10) || 0;
        sortKey = '#';
        sortDir = 'asc';
        $('#contentReportSearch').val('');
        $('#contentReportModalLabel').text(
            moduleTitle(currentModule) + ' -> ' + String(currentLabel || '').toUpperCase()
        );
        $('#contentReportModalCount').text('…');
        syncRangeFilterVisibility();
        $('#contentReportModal').modal('show');
        loadDetails();
    });

    $('a[data-toggle="tab"][data-module]', '#content-reports-panel').on('shown.bs.tab', function () {
        var module = $(this).data('module');
        logContentReportActivity('Opened content report tab', moduleTitle(module));
    });

    $('#content_report_user').on('change', function () {
        var label = $.trim($(this).find('option:selected').text() || 'All Users');
        logContentReportActivity('Filtered content report user', label);
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
