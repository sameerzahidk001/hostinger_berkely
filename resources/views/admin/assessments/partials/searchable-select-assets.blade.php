{{-- Select2 type-to-find for assessment / question bank dropdowns. --}}
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
</style>
@once
@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
window.LmsSearchableSelect = window.LmsSearchableSelect || (function () {
    function init(scope) {
        $(scope || document).find('select.lms-searchable').each(function () {
            var $el = $(this);
            if ($el.hasClass('select2-hidden-accessible')) {
                return;
            }
            var placeholder = $el.data('placeholder') || 'Type to find…';
            var allowClear = $el.data('allow-clear') !== 0 && $el.data('allow-clear') !== '0';
            $el.select2({
                placeholder: placeholder,
                allowClear: allowClear && !$el.prop('multiple'),
                width: '100%',
                closeOnSelect: ! $el.prop('multiple')
            });
        });
    }
    return { init: init };
})();
$(function () {
    if (window.LmsSearchableSelect) {
        window.LmsSearchableSelect.init(document);
    }
});
</script>
@endpush
@endonce
