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
        if (t === 'action' || t === 'actions') {
            return true;
        }
        if (th.querySelector('input, select, button, a')) {
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
            return row.cells.length > 1;
        });
        rows.sort(function (a, b) {
            var ca = a.cells[col];
            var cb = b.cells[col];
            var av = ((ca && (ca.getAttribute('data-sort') || ca.textContent)) || '').replace(/\s+/g, ' ').trim().toLowerCase();
            var bv = ((cb && (cb.getAttribute('data-sort') || cb.textContent)) || '').replace(/\s+/g, ' ').trim().toLowerCase();
            var an = parseFloat(av);
            var bn = parseFloat(bv);
            var cmp;
            if (!isNaN(an) && !isNaN(bn) && /^-?\d+(\.\d+)?$/.test(av) && /^-?\d+(\.\d+)?$/.test(bv)) {
                cmp = an - bn;
            } else {
                cmp = String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' });
            }
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

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table:not(.dataTables-example):not(.dataTable) thead th').forEach(function (th) {
            if (!skipTh(th)) {
                th.classList.add('lms-sort-th');
            }
        });
    });
})();
</script>
