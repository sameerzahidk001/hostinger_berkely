@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
.select2-container { width: 100% !important; }
.select2-container .select2-selection--single {
    height: 34px;
    border: 1px solid #e5e6e7;
    border-radius: 1px;
}
.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 32px;
    padding-left: 12px;
}
.select2-container .select2-selection--single .select2-selection__arrow { height: 32px; }
.select2-container .select2-selection--multiple {
    min-height: 34px;
    border: 1px solid #e5e6e7;
    border-radius: 1px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #1ab394;
    border-color: #1ab394;
    color: #fff;
}
</style>
@endpush
@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function () {
    const course = $('#course_id');
    const pkg = $('#fee_package_ids');
    const urlBase = @json(url('admin/study-materials/packages-by-course'));
    const selectedPackages = @json($selectedPackageIds ?? []);

    course.select2({
        placeholder: 'Type to find the course',
        allowClear: true,
        width: '100%'
    });
    pkg.select2({
        placeholder: 'Select one or more packages',
        allowClear: true,
        width: '100%'
    });
    if ($('#head_of_faculty_id').length) {
        $('#head_of_faculty_id').select2({
            placeholder: 'Type to find head of faculty',
            allowClear: true,
            width: '100%'
        });
    }
    if ($('#instructor_ids').length) {
        $('#instructor_ids').select2({
            placeholder: 'Type to find instructors',
            width: '100%',
            closeOnSelect: false
        });
    }

    function packageLabel(row) {
        return row.package_name + (row.price ? (' — ' + (row.currency || '') + ' ' + row.price) : '');
    }

    function loadPackages(keepSelected) {
        const courseId = course.val();
        pkg.empty();
        if (!courseId) {
            pkg.trigger('change');
            return;
        }
        fetch(urlBase + '/' + courseId)
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                const current = (keepSelected || []).map(String);
                rows.forEach(function (row) {
                    const opt = new Option(packageLabel(row), row.id, false, current.indexOf(String(row.id)) !== -1);
                    pkg.append(opt);
                });
                pkg.trigger('change');
            });
    }

    course.on('select2:select select2:clear', function () {
        loadPackages([]);
    });

    if (course.val() && pkg.find('option').length === 0) {
        loadPackages(selectedPackages.map(String));
    }

    $('#add-structure-root').on('click', function () {
        addStructureRow($('#structure-root'), 'structure', 0);
    });

    $(document).on('click', '.add-nested', function () {
        const $row = $(this).closest('.structure-row');
        const depth = parseInt($row.data('depth'), 10) || 0;
        if (depth >= 2) return;
        const nameInput = $row.children('.row').find('input[type="text"]').first();
        const prefix = (nameInput.attr('name') || '').replace(/\[name]$/, '');
        addStructureRow($row.children('.structure-children'), prefix + '[children]', depth + 1);
    });

    $(document).on('click', '.remove-structure', function () {
        const $row = $(this).closest('.structure-row');
        const $container = $row.parent();
        if ($container.is('#structure-root') && $container.children('.structure-row').length <= 1) {
            $row.find('input[type="text"]').val('');
            $row.children('.structure-children').empty();
            return;
        }
        $row.remove();
    });

    function addStructureRow($container, prefix, depth) {
        const tpl = document.getElementById('structure-row-template');
        if (!tpl) return;
        const idx = $container.children('.structure-row').length;
        const rowPrefix = prefix + '[' + idx + ']';
        const placeholder = depth === 0 ? 'Subfolder name' : (depth === 1 ? 'Sub-subfolder name' : 'Nested folder name');
        let html = tpl.innerHTML
            .replace(/__PREFIX__/g, rowPrefix)
            .replace(/__DEPTH__/g, String(depth))
            .replace(/__PLACEHOLDER__/g, placeholder);
        $container.append(html);
        if (depth >= 2) {
            $container.children('.structure-row').last().find('.add-nested').remove();
        }
    }
})();
</script>
@endpush
