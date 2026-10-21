<style>
    .lms-admission-wrap {
        overflow-x: hidden;
        width: 100%;
    }
    .lms-admission-wrap,
    .lms-admission-wrap table,
    .lms-admission-table {
        min-width: 0 !important;
        width: 100% !important;
    }
    .lms-admission-table th,
    .lms-admission-table td {
        white-space: normal;
        word-break: break-word;
        vertical-align: top;
    }
    .lms-admission-table th.col-action,
    .lms-admission-table td.col-action {
        width: 92px;
        max-width: 100px;
        white-space: nowrap;
    }
    .lms-admission-actions {
        display: flex;
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
        width: 88px;
    }
    .lms-admission-actions .btn {
        margin: 0;
        padding: 4px 6px;
        font-size: 11px;
        white-space: nowrap;
        width: 100%;
    }
    @media (max-width: 991px) {
        .lms-admission-table thead { display: none; }
        .lms-admission-table,
        .lms-admission-table tbody,
        .lms-admission-table tr,
        .lms-admission-table td {
            display: block;
            width: 100%;
        }
        .lms-admission-table tr {
            margin-bottom: 12px;
            border: 1px solid #e7eaec;
            background: #fff;
        }
        .lms-admission-table td {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 12px;
            border: 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .lms-admission-table td:last-child { border-bottom: 0; }
        .lms-admission-table td::before {
            content: attr(data-label);
            font-weight: 600;
            flex: 0 0 42%;
            color: #676a6c;
        }
        .lms-admission-table th.col-action,
        .lms-admission-table td.col-action {
            width: 100%;
            max-width: none;
        }
        .lms-admission-actions {
            width: auto;
            flex: 1;
        }
        .table-responsive,
        .ibox-content,
        .wrapper-content {
            overflow-x: hidden !important;
        }
    }
</style>
