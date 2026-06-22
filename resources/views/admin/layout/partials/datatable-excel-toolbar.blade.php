<style>
    .admin-dt-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px 16px;
        margin-bottom: 14px;
        width: 100%;
    }

    .admin-dt-toolbar .dataTables_length,
    .admin-dt-toolbar .dt-buttons,
    .admin-dt-toolbar .dataTables_filter {
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .admin-dt-toolbar .dataTables_length label,
    .admin-dt-toolbar .dataTables_filter label {
        margin-bottom: 0;
        font-weight: normal;
    }

    .admin-dt-toolbar .dataTables_filter {
        margin-left: auto !important;
    }

    .admin-dt-toolbar .dt-buttons {
        display: inline-flex;
        align-items: center;
    }

    .admin-dt-toolbar .dt-button.btn-excel-export,
    .admin-dt-toolbar .dt-button.btn-excel-export:hover,
    .admin-dt-toolbar .dt-button.btn-excel-export:focus,
    .admin-dt-toolbar .dt-button.btn-excel-export:active {
        background: #1ab394 !important;
        border: 1px solid #18a689 !important;
        color: #fff !important;
        border-radius: 3px;
        padding: 7px 16px !important;
        font-size: 13px;
        font-weight: 600;
        line-height: 1.4;
        box-shadow: none !important;
        text-shadow: none !important;
        margin: 0 !important;
    }

    .admin-dt-toolbar .dt-button.btn-excel-export:hover {
        background: #18a689 !important;
    }

    @media (max-width: 768px) {
        .admin-dt-toolbar .dataTables_filter {
            margin-left: 0 !important;
            width: 100%;
        }

        .admin-dt-toolbar .dataTables_filter input {
            width: 100%;
            max-width: 100%;
        }
    }
</style>

@once
    @push('script')
        <script>
            window.adminDatatableExportBody = function (data, row, column, node) {
                var exported = $(node).attr('data-export');
                if (exported !== undefined && exported !== '') {
                    return exported;
                }

                return $('<div>').html(data).text().trim().replace(/\s+/g, ' ');
            };

            window.adminDatatableExcelButton = function (title, filename) {
                return {
                    extend: 'excelHtml5',
                    className: 'btn-excel-export',
                    text: 'Export to Excel',
                    title: title,
                    filename: filename + '_' + new Date().toISOString().slice(0, 10),
                    exportOptions: {
                        columns: ':not(:last-child)',
                        format: {
                            body: window.adminDatatableExportBody
                        }
                    }
                };
            };
        </script>
    @endpush
@endonce
