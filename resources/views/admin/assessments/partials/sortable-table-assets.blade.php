{{-- Lightweight click-to-sort for assessment tables (current page rows). --}}
<style>
    table.js-sortable-table > thead > tr > th[data-sort-col] {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
    }
    table.js-sortable-table > thead > tr > th[data-sort-col]:hover {
        background: #f5f5f5;
    }
    table.js-sortable-table .sort-ind {
        font-size: 10px;
        color: #888;
        font-weight: 700;
    }
</style>
@once
@push('script')
<script>
window.LmsSortableTable = window.LmsSortableTable || (function () {
    function bind($table) {
        if (!$table.length || $table.data('lms-sort-bound')) {
            return;
        }
        $table.data('lms-sort-bound', 1);
        var sortCol = -1;
        var sortDir = 'asc';

        $table.on('click', 'thead th[data-sort-col]', function () {
            var col = parseInt($(this).data('sort-col'), 10);
            if (isNaN(col)) {
                return;
            }
            if (sortCol === col) {
                sortDir = sortDir === 'asc' ? 'desc' : 'asc';
            } else {
                sortCol = col;
                sortDir = 'asc';
            }

            var $rows = $table.find('tbody tr').get().filter(function (row) {
                return $(row).find('td').length > 1;
            });

            $rows.sort(function (a, b) {
                var $a = $(a).children('td').eq(sortCol);
                var $b = $(b).children('td').eq(sortCol);
                var av = ($a.attr('data-sort') || $a.text() || '').trim().toLowerCase();
                var bv = ($b.attr('data-sort') || $b.text() || '').trim().toLowerCase();
                var an = parseFloat(av);
                var bn = parseFloat(bv);
                var cmp;
                if (!isNaN(an) && !isNaN(bn) && String(av).match(/^-?\d+(\.\d+)?$/)) {
                    cmp = an - bn;
                } else {
                    cmp = String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' });
                }
                return sortDir === 'asc' ? cmp : -cmp;
            });

            var $tbody = $table.children('tbody');
            $.each($rows, function (_, row) {
                $tbody.append(row);
            });

            $table.find('thead th .sort-ind').text('');
            $(this).find('.sort-ind').text(sortDir === 'asc' ? ' ▲' : ' ▼');
        });
    }

    function init(scope) {
        $(scope || document).find('table.js-sortable-table').each(function () {
            bind($(this));
        });
    }

    return { init: init };
})();
$(function () {
    if (window.LmsSortableTable) {
        window.LmsSortableTable.init(document);
    }
});
</script>
@endpush
@endonce
