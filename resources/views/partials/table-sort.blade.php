{{-- Click-to-sort for current-page table rows. Safe to include more than once. --}}
<style>
    table:not(.dataTables-example):not(.dataTable) > thead > tr > th.lms-sort-th {
        cursor: pointer;
        user-select: none;
    }
    table .sort-ind { font-size: 10px; color: #888; font-weight: 700; }
</style>
<script>
(function () {
    if (window.__lmsTableSortBound) {
        if (typeof window.__lmsMarkSortableTables === 'function') {
            window.__lmsMarkSortableTables();
        }
        return;
    }
    window.__lmsTableSortBound = true;
    var state = new WeakMap();

    function skipTable(table) {
        if (!table) {
            return true;
        }
        if (table.classList.contains('dataTables-example') || table.classList.contains('dataTable')) {
            return true;
        }
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable(table)) {
            return true;
        }
        return false;
    }

    function skipTh(th) {
        var t = (th.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
        if (th.classList.contains('col-actions') || th.classList.contains('col-action')) {
            return true;
        }
        if (t === 'action' || t === 'actions' || t === 'join' || t === '') {
            return true;
        }
        if (th.querySelector('input, select, button')) {
            return true;
        }
        return false;
    }

    function cellIndex(th) {
        var raw = th.getAttribute('data-sort-col');
        if (raw !== null && raw !== '') {
            var n = parseInt(raw, 10);
            if (!isNaN(n)) {
                return n;
            }
        }
        var i = 0, el = th;
        while (el.previousElementSibling) {
            i += 1;
            el = el.previousElementSibling;
        }
        return i;
    }

    function sortValue(cell) {
        if (!cell) {
            return '';
        }
        var raw = cell.getAttribute('data-sort');
        if (raw !== null && raw !== '') {
            return String(raw).replace(/\s+/g, ' ').trim();
        }
        return (cell.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function compareValues(av, bv) {
        var al = av.toLowerCase();
        var bl = bv.toLowerCase();
        var an = parseFloat(av);
        var bn = parseFloat(bv);
        if (!isNaN(an) && !isNaN(bn) && /^-?\d+(\.\d+)?$/.test(av) && /^-?\d+(\.\d+)?$/.test(bv)) {
            return an - bn;
        }
        return String(al).localeCompare(String(bl), undefined, { numeric: true, sensitivity: 'base' });
    }

    document.addEventListener('click', function (e) {
        var th = e.target.closest('table thead th');
        if (!th) {
            return;
        }
        var table = th.closest('table');
        if (skipTable(table) || skipTh(th)) {
            return;
        }
        var col = cellIndex(th);
        var st = state.get(table) || { col: -1, dir: 'asc' };
        if (st.col === col) {
            st.dir = st.dir === 'asc' ? 'desc' : 'asc';
        } else {
            st.col = col;
            st.dir = 'asc';
        }
        state.set(table, st);

        var tbody = table.tBodies[0];
        if (!tbody) {
            return;
        }
        var rows = Array.prototype.filter.call(tbody.rows, function (row) {
            return row.cells.length > 1 && !row.querySelector('td[colspan]');
        });
        rows.sort(function (a, b) {
            var cmp = compareValues(sortValue(a.cells[col]), sortValue(b.cells[col]));
            return st.dir === 'asc' ? cmp : -cmp;
        });
        rows.forEach(function (row) {
            tbody.appendChild(row);
        });
        table.querySelectorAll('thead th .sort-ind').forEach(function (s) {
            s.textContent = '';
        });
        var ind = th.querySelector('.sort-ind');
        if (!ind) {
            ind = document.createElement('span');
            ind.className = 'sort-ind';
            th.appendChild(ind);
        }
        ind.textContent = st.dir === 'asc' ? ' ▲' : ' ▼';
    }, true);

    function markSortable() {
        document.querySelectorAll('table:not(.dataTables-example):not(.dataTable) thead th').forEach(function (th) {
            if (!skipTh(th) && !skipTable(th.closest('table'))) {
                th.classList.add('lms-sort-th');
                if (!th.querySelector('.sort-ind')) {
                    var ind = document.createElement('span');
                    ind.className = 'sort-ind';
                    th.appendChild(ind);
                }
            }
        });
    }
    window.__lmsMarkSortableTables = markSortable;
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', markSortable);
    } else {
        markSortable();
    }
})();
</script>
