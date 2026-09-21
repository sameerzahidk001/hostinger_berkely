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
window.initTypeFindSelects = function (root) {
    var $root = root ? $(root) : $(document);
    $root.find('select.js-type-find').each(function () {
        var $el = $(this);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        var multiple = !!$el.prop('multiple');
        $el.select2({
            placeholder: $el.data('placeholder') || (multiple ? 'Type to find…' : 'Type to find…'),
            allowClear: !multiple && !$el.prop('required'),
            width: '100%',
            closeOnSelect: !multiple
        });
    });
};
$(function () {
    window.initTypeFindSelects();
});
</script>
@endpush
