{{-- Shared Summernote + plain textarea character counters for Assessments. --}}
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
<style>
    .lms-char-count {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: #676a6c;
    }
    .lms-char-count.is-over {
        color: #c0392b;
        font-weight: 700;
    }
    .lms-char-hint {
        font-weight: 400;
        color: #888;
        font-size: 12px;
    }
    .note-editor .note-editable table,
    .ibox-content table,
    .well table {
        width: 100%;
        border-collapse: collapse;
        margin: 8px 0;
    }
    .note-editor .note-editable table td,
    .note-editor .note-editable table th,
    .ibox-content table td,
    .ibox-content table th,
    .well table td,
    .well table th {
        border: 1px solid #c5c5c5;
        padding: 6px 8px;
        min-width: 40px;
    }
</style>
@once
@push('script')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script>
window.LmsLimitedEditor = window.LmsLimitedEditor || (function () {
    function plainLen(html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html || '';
        return (tmp.textContent || tmp.innerText || '').replace(/\u00a0/g, ' ').trim().length;
    }

    function ensureCounter($el) {
        var max = parseInt($el.attr('data-max') || '0', 10);
        if (!max) return null;
        var id = $el.attr('id') || ('lms_ed_' + Math.random().toString(36).slice(2));
        $el.attr('id', id);
        var $count = $el.closest('.form-group, .panel-body, td, .ibox-content').find('.lms-char-count[data-for="' + id + '"]');
        if (!$count.length) {
            $count = $('<small class="lms-char-count" data-for="' + id + '"></small>');
            if ($el.next('.note-editor').length) {
                $el.next('.note-editor').after($count);
            } else {
                $el.after($count);
            }
        }
        return { max: max, $count: $count };
    }

    function renderCount($el, len, max) {
        var meta = ensureCounter($el);
        if (!meta) return true;
        meta.$count.text(len + ' / ' + max + ' characters');
        meta.$count.toggleClass('is-over', len > max);
        return len <= max;
    }

    function initOne(el) {
        var $el = $(el);
        if ($el.data('lms-limited-ready')) return;
        var max = parseInt($el.attr('data-max') || '0', 10);
        var height = parseInt($el.attr('data-height') || '140', 10);
        var isRich = $el.hasClass('text-editor') || $el.attr('data-rich') === '1';

        if (isRich) {
            if (!$el.hasClass('summernote-initialized') && !$el.next('.note-editor').length) {
                $el.summernote({
                    height: height,
                    toolbar: [
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['para', ['ul', 'ol']],
                        ['table', ['table']],
                        ['insert', ['link']],
                        ['view', ['codeview']]
                    ],
                    callbacks: {
                        onKeydown: function (e) {
                            if (!max) return;
                            var len = plainLen($el.summernote('code'));
                            if (len >= max && e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
                                e.preventDefault();
                            }
                        },
                        onChange: function (contents) {
                            renderCount($el, plainLen(contents), max);
                        },
                        onInit: function () {
                            renderCount($el, plainLen($el.summernote('code')), max);
                        }
                    }
                });
                $el.addClass('summernote-initialized');
            } else {
                renderCount($el, plainLen($el.next('.note-editor').length ? $el.summernote('code') : $el.val()), max);
            }
        } else {
            if (max) {
                $el.attr('maxlength', max);
            }
            $el.on('input change', function () {
                renderCount($el, ($el.val() || '').length, max);
            });
            renderCount($el, ($el.val() || '').length, max);
        }
        $el.data('lms-limited-ready', true);
    }

    function init(scope) {
        $(scope || document).find('textarea[data-max], textarea.text-editor, input[data-max]').each(function () {
            initOne(this);
        });
    }

    function sync(scope) {
        $(scope || document).find('textarea.text-editor, textarea[data-rich="1"]').each(function () {
            var $el = $(this);
            if ($el.next('.note-editor').length) {
                $el.val($el.summernote('code'));
            }
        });
    }

    function validate(scope) {
        var ok = true;
        $(scope || document).find('textarea[data-max], input[data-max]').each(function () {
            var $el = $(this);
            var max = parseInt($el.attr('data-max') || '0', 10);
            if (!max) return;
            var len = $el.hasClass('text-editor') || $el.attr('data-rich') === '1'
                ? plainLen($el.next('.note-editor').length ? $el.summernote('code') : $el.val())
                : ($el.val() || '').length;
            if (!renderCount($el, len, max)) {
                ok = false;
            }
        });
        return ok;
    }

    return { init: init, sync: sync, validate: validate, plainLen: plainLen };
})();

$(function () {
    window.LmsLimitedEditor.init(document);
    $(document).on('submit', 'form', function (e) {
        var form = this;
        window.LmsLimitedEditor.sync(form);
        if (!window.LmsLimitedEditor.validate(form)) {
            e.preventDefault();
            alert('One or more fields exceed the maximum character limit. Shorten the highlighted fields and try again.');
        }
    });
});
</script>
@endpush
@endonce
