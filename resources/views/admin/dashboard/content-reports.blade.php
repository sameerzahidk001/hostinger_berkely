@php
    $reports = $contentReports ?? [];
    $metricLabels = $contentReportMetricLabels ?? [];
    $activeTab = request('report_tab', 'pages');
    if (! in_array($activeTab, ['pages', 'courses', 'seo'], true)) {
        $activeTab = 'pages';
    }
@endphp

<div class="row" style="padding: 0 10px 12px;" id="content-reports-panel">
    <div class="col-lg-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                <h5 style="margin:0;">Content performance reports</h5>
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
                <div class="row" style="margin-bottom:12px;">
                    <div class="col-sm-6">
                        <input type="text" id="contentReportSearch" class="form-control" placeholder="Search title / keyword...">
                    </div>
                    <div class="col-sm-6 text-right">
                        <a href="#" id="contentReportExportExcel" class="btn btn-success btn-sm" target="_blank">
                            <i class="fa fa-file-excel-o"></i> Excel
                        </a>
                        <a href="#" id="contentReportExportPdf" class="btn btn-danger btn-sm" target="_blank">
                            <i class="fa fa-file-pdf-o"></i> PDF
                        </a>
                    </div>
                </div>
                <div class="table-responsive" style="max-height:65vh;overflow:auto;">
                    <table class="table table-striped table-bordered table-hover" id="contentReportTable">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th style="width:100px;">Status</th>
                                <th style="width:140px;">Created By</th>
                                <th style="width:140px;">Updated By</th>
                                <th style="width:130px;">Created</th>
                                <th style="width:130px;">Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
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

    function filterQuery() {
        var params = new URLSearchParams(window.location.search);
        return {
            date_from: params.get('date_from') || '',
            date_to: params.get('date_to') || '',
            user_id: params.get('user_id') || ''
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

    function loadDetails() {
        var q = $('#contentReportSearch').val() || '';
        $('#contentReportTable tbody').html('<tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>');
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
            var rows = resp.rows || [];
            if (!rows.length) {
                $('#contentReportTable tbody').html('<tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>');
                return;
            }
            var html = rows.map(function (row) {
                var title = row.url
                    ? '<a href="' + row.url + '" target="_blank" rel="noopener">' + $('<div>').text(row.title || '').html() + '</a>'
                    : $('<div>').text(row.title || '').html();
                return '<tr>' +
                    '<td>' + title + '</td>' +
                    '<td>' + $('<div>').text(row.status || '—').html() + '</td>' +
                    '<td>' + $('<div>').text(row.created_by || '—').html() + '</td>' +
                    '<td>' + $('<div>').text(row.updated_by || '—').html() + '</td>' +
                    '<td>' + $('<div>').text(row.created_at || '—').html() + '</td>' +
                    '<td>' + $('<div>').text(row.updated_at || '—').html() + '</td>' +
                    '</tr>';
            }).join('');
            $('#contentReportTable tbody').html(html);
        }).fail(function () {
            $('#contentReportTable tbody').html('<tr><td colspan="6" class="text-center text-danger">Failed to load report details.</td></tr>');
        });
    }

    $(document).on('click', '.content-report-stat', function () {
        currentModule = $(this).data('module');
        currentMetric = $(this).data('metric');
        currentLabel = $(this).data('label');
        $('#contentReportSearch').val('');
        $('#contentReportModal').modal('show');
        loadDetails();
    });

    $('#contentReportSearch').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadDetails, 350);
    });
})(jQuery);
</script>
@endpush
