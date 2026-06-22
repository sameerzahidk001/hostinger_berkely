<style>
    .payments-dt-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px 16px;
        margin-bottom: 14px;
        width: 100%;
    }

    .payments-dt-toolbar .dataTables_length,
    .payments-dt-toolbar .dt-buttons,
    .payments-dt-toolbar .dataTables_filter {
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .payments-dt-toolbar .dataTables_length label,
    .payments-dt-toolbar .dataTables_filter label {
        margin-bottom: 0;
        font-weight: normal;
    }

    .payments-dt-toolbar .dataTables_filter {
        margin-left: auto !important;
    }

    .payments-dt-toolbar .dt-buttons {
        display: inline-flex;
        align-items: center;
    }

    .payments-dt-toolbar .dt-button.btn-excel-export,
    .payments-dt-toolbar .dt-button.btn-excel-export:hover,
    .payments-dt-toolbar .dt-button.btn-excel-export:focus,
    .payments-dt-toolbar .dt-button.btn-excel-export:active {
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

    .payments-dt-toolbar .dt-button.btn-excel-export:hover {
        background: #18a689 !important;
    }

    @media (max-width: 768px) {
        .payments-dt-toolbar .dataTables_filter {
            margin-left: 0 !important;
            width: 100%;
        }

        .payments-dt-toolbar .dataTables_filter input {
            width: 100%;
            max-width: 100%;
        }
    }
</style>
