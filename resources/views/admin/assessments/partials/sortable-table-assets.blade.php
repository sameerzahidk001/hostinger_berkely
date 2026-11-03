{{-- Sorting is bound in admin/user layout (partials/table-sort). Keep this include for old pages. --}}
<style>
    table.js-sortable-table > thead > tr > th[data-sort-col] {
        cursor: pointer;
        user-select: none;
    }
    table.js-sortable-table .sort-ind {
        font-size: 10px;
        color: #888;
        font-weight: 700;
    }
</style>
