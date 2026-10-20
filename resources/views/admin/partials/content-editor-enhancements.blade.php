{{-- Shared character counters + AI Write/Rewrite for Pages & Courses editors --}}
@php
    $editorModule = $editorModule ?? 'pages';
    $editorConfig = app(\App\Services\ContentEditorSettingsService::class)->frontendConfig($editorModule);
@endphp

@push('style')
<style>
    .ce-field-wrap { position: relative; margin-bottom: 6px; }
    .ce-toolbar {
        display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
        margin: 0 0 6px;
    }
    .ce-toolbar .btn { margin: 0; }
    .ce-counter {
        font-size: 12px; color: #676a6c; margin-top: 4px;
    }
    .ce-counter.is-warn { color: #f8ac59; font-weight: 600; }
    .ce-counter.is-over { color: #ed5565; font-weight: 700; }
    .ce-msg {
        display: none; font-size: 12px; color: #ed5565; margin-top: 2px;
    }
    .ce-msg.is-visible { display: block; }
    #ceAiModal .modal-dialog { width: 92%; max-width: 900px; }
    #ceAiPreview { min-height: 220px; }
    .ce-ai-status { min-height: 20px; }
</style>
@endpush

@push('script')
<script>
(function (window, $) {
    if (!window.jQuery) { return; }

    var CFG = @json($editorConfig);
    var GENERATE_URL = @json(route('admin.ai-content.generate'));
    var CSRF = @json(csrf_token());

    function plainLength(htmlOrText) {
        var tmp = document.createElement('div');
        tmp.innerHTML = htmlOrText || '';
        var text = (tmp.textContent || tmp.innerText || '').replace(/\u00a0/g, ' ').trim();
        // For plain textareas without HTML, fall back to raw value length when strip yields empty but value exists
        return text.length;
    }

    function rawPlain(value, isHtml) {
        if (!isHtml) {
            return String(value || '');
        }
        var tmp = document.createElement('div');
        tmp.innerHTML = value || '';
        return (tmp.textContent || tmp.innerText || '').replace(/\u00a0/g, ' ');
    }

    function resolveFieldKey($el) {
        var key = $el.attr('data-limit-key');
        if (key) { return key; }

        var name = String($el.attr('name') || '');
        var id = String($el.attr('id') || '');
        var module = CFG.module || 'pages';

        if (module === 'pages') {
            if (name === 'page_name') return 'page_name';
            if (name === 'url') return 'page_url';
            if (/\[title\]$/.test(name) && /cards/.test(name)) return 'card_title';
            if (/\[(description|content)\]$/.test(name) && /cards/.test(name)) return 'card_description';
            if (/list_items/.test(name)) return 'list_item';
            if (/\[(button_text|btn_text|cta)\]/.test(name)) return 'button_text';
            if (/\[(subtitle|sub_title)\]/.test(name)) return 'section_subtitle';
            if (/\[(title|heading|name)\]$/.test(name)) return 'section_title';
            if (/\[(description|content|text|body)\]/.test(name)) return 'section_description';
            if ($el.hasClass('editor')) return 'section_description';
            return 'default';
        }

        // courses
        if (name === 'title' || id === 'title') return 'title';
        if (name === 'short_description') return 'short_description';
        if (name === 'description') return 'description';
        if (name === 'vision_and_mission') return 'vision_and_mission';
        if (name === 'eligibility') return 'eligibility';
        if (name === 'career_path') return 'career_path';
        if (name === 'exam_dates') return 'exam_dates';
        if (name === 'exam_reg_deadline') return 'exam_reg_deadline';
        if (name === 'exam_passing_criteria') return 'exam_passing_criteria';
        if (name === 'exam_location_paragraph') return 'exam_location_paragraph';
        if (name === 'exam_info_custom_01') return 'exam_info_custom_01';
        if (name === 'course_exam_format_duration_overview') return 'course_exam_format_duration_overview';
        if (name === 'performance_standard_description') return 'performance_standard_description';
        if (name === 'custom_videos_desc') return 'custom_videos_desc';
        if (name === 'image_text_2col_description') return 'image_text_2col_description';
        if (name === 'custom_section_01_description') return 'custom_section_01_description';
        if (name === 'alumni_benefits_description') return 'alumni_benefits_description';
        if (name === 'contact_us_text') return 'contact_us_text';
        if (/^offered_by/.test(name)) return 'offered_by';
        if (/^benifits\[/.test(name) || /^benefits\[/.test(name)) return 'benefits_description';
        if (/^label\[/.test(name)) return 'label_text';
        return 'default';
    }

    function maxFor($el) {
        var key = resolveFieldKey($el);
        if (CFG.limits && CFG.limits[key] != null) {
            return parseInt(CFG.limits[key], 10) || 3000;
        }
        return parseInt((CFG.limits && CFG.limits.default) || 3000, 10);
    }

    function ensureModal() {
        if ($('#ceAiModal').length) { return; }
        $('body').append(`
<div class="modal fade" id="ceAiModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">AI Writing Assistant</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" id="ceAiMode">
        <div class="row">
          <div class="col-sm-6" style="margin-bottom:10px;">
            <label>Topic / focus (optional)</label>
            <input type="text" id="ceAiTopic" class="form-control" placeholder="e.g. ACCA course benefits">
          </div>
          <div class="col-sm-6" style="margin-bottom:10px;">
            <label>SEO keywords (optional)</label>
            <input type="text" id="ceAiKeywords" class="form-control" placeholder="keyword1, keyword2">
          </div>
          <div class="col-sm-12" style="margin-bottom:10px;">
            <label>Extra instructions (optional)</label>
            <input type="text" id="ceAiInstructions" class="form-control" placeholder="Tone, audience, must-include points…">
          </div>
        </div>
        <div class="ce-ai-status text-muted small" id="ceAiStatus"></div>
        <label>Review &amp; edit before applying</label>
        <textarea id="ceAiPreview" class="form-control"></textarea>
        <div class="ce-counter" id="ceAiPreviewCounter">0 / 0</div>
        <div class="ce-msg" id="ceAiPreviewMsg">Character limit exceeded. Shorten the content before applying.</div>
        <p class="text-muted small m-t-sm m-b-none">Nothing is saved until you click Apply. You can edit the AI result first.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-warning" id="ceAiGenerateBtn"><i class="fa fa-magic"></i> Generate</button>
        <button type="button" class="btn btn-primary" id="ceAiApplyBtn"><i class="fa fa-check"></i> Apply to field</button>
      </div>
    </div>
  </div>
</div>`);
    }

    var activeTarget = null;

    function getValue($el) {
        if ($el.hasClass('summernote-initialized') || $el.next('.note-editor').length) {
            try { return $el.summernote('code') || ''; } catch (e) {}
        }
        return $el.val() || '';
    }

    function setValue($el, value) {
        if ($el.hasClass('summernote-initialized') || $el.next('.note-editor').length) {
            try {
                $el.summernote('code', value);
                updateCounter($el);
                return;
            } catch (e) {}
        }
        $el.val(value).trigger('input').trigger('change');
        updateCounter($el);
    }

    function updateCounter($el) {
        var $wrap = $el.closest('.ce-field-wrap');
        if (!$wrap.length) { return; }
        var max = maxFor($el);
        var isHtml = $el.is('textarea') && ($el.hasClass('editor') || $el.hasClass('text-editor') || $el.hasClass('summernote-initialized'));
        var value = getValue($el);
        var len = isHtml ? plainLength(value) : String(value || '').length;
        // If HTML strip is empty but value has text without tags
        if (isHtml && len === 0 && String(value || '').replace(/<[^>]+>/g, '').length) {
            len = String(value || '').replace(/<[^>]+>/g, '').length;
        }
        var $counter = $wrap.find('.ce-counter').first();
        var $msg = $wrap.find('.ce-msg').first();
        $counter.text(len + ' / ' + max + ' characters');
        $counter.toggleClass('is-warn', len >= Math.floor(max * 0.9) && len <= max);
        $counter.toggleClass('is-over', len > max);
        if (len > max) {
            $msg.text('Maximum character limit (' + max + ') exceeded. Please shorten this field.').addClass('is-visible');
        } else if (len === max) {
            $msg.text('Maximum character limit reached.').addClass('is-visible');
        } else {
            $msg.removeClass('is-visible').text('');
        }
    }

    function enforceOnInput($el, isHtml) {
        var max = maxFor($el);
        var value = getValue($el);
        var plain = isHtml ? rawPlain(value, true) : String(value || '');
        if (plain.length <= max) {
            updateCounter($el);
            return;
        }
        // Do not hard-truncate existing over-limit legacy content on first enhance;
        // only block growth beyond max for plain fields. For rich editors, warn only.
        if (!isHtml) {
            $el.val(plain.slice(0, max));
        }
        updateCounter($el);
    }

    function buildToolbar($el) {
        var buttons = '';
        if (CFG.aiEnabled) {
            buttons = `
                <button type="button" class="btn btn-xs btn-info ce-ai-btn" data-mode="write" title="Generate new content"><i class="fa fa-magic"></i> AI Write</button>
                <button type="button" class="btn btn-xs btn-primary ce-ai-btn" data-mode="rewrite" title="Rewrite existing content"><i class="fa fa-refresh"></i> AI Rewrite</button>
                <button type="button" class="btn btn-xs btn-default ce-ai-btn" data-mode="grammar" title="Improve grammar"><i class="fa fa-check-circle"></i> Grammar</button>
                <button type="button" class="btn btn-xs btn-warning ce-ai-btn" data-mode="seo" title="Optimize for SEO"><i class="fa fa-search"></i> SEO</button>
            `;
        } else if (CFG.aiConfigured === false) {
            buttons = `<span class="text-muted small">AI available after Admin adds an API key in Settings → Content Editor</span>`;
        }
        return $(`<div class="ce-toolbar">${buttons}</div>`);
    }

    function enhanceElement(el) {
        var $el = $(el);
        if ($el.data('ce-enhanced')) { return; }
        if ($el.closest('#ceAiModal').length) { return; }

        $el.data('ce-enhanced', true);
        var $wrap = $el.closest('.ce-field-wrap');
        if (!$wrap.length) {
            $el.wrap('<div class="ce-field-wrap"></div>');
            $wrap = $el.closest('.ce-field-wrap');
        }

        if (!$wrap.find('.ce-toolbar').length) {
            $wrap.prepend(buildToolbar($el));
        }
        if (!$wrap.find('.ce-counter').length) {
            $el.after('<div class="ce-counter">0 / 0 characters</div><div class="ce-msg"></div>');
        }

        updateCounter($el);
    }

    function openAiModal($el, mode) {
        ensureModal();
        activeTarget = $el;
        $('#ceAiMode').val(mode);
        $('#ceAiTopic').val('');
        $('#ceAiKeywords').val('');
        $('#ceAiInstructions').val('');
        $('#ceAiPreview').val(getValue($el));
        $('#ceAiStatus').text('Mode: ' + mode + ' · Limit: ' + maxFor($el) + ' characters. Review output before Apply.');
        updatePreviewCounter();
        var titles = {
            write: 'AI Write — generate new content',
            rewrite: 'AI Rewrite — rewrite existing content',
            grammar: 'Improve Grammar',
            seo: 'Optimize for SEO'
        };
        $('#ceAiModal .modal-title').text(titles[mode] || 'AI Writing Assistant');
        $('#ceAiModal').modal('show');
    }

    function updatePreviewCounter() {
        if (!activeTarget) { return; }
        var max = maxFor(activeTarget);
        var val = $('#ceAiPreview').val() || '';
        var isHtml = activeTarget.is('textarea') && (activeTarget.hasClass('editor') || activeTarget.hasClass('text-editor') || activeTarget.hasClass('summernote-initialized'));
        var len = isHtml ? plainLength(val) : val.length;
        $('#ceAiPreviewCounter').text(len + ' / ' + max + ' characters')
            .toggleClass('is-over', len > max)
            .toggleClass('is-warn', len >= Math.floor(max * 0.9) && len <= max);
        $('#ceAiPreviewMsg').toggleClass('is-visible', len > max);
        $('#ceAiApplyBtn').prop('disabled', len > max || len === 0);
    }

    function generateAi() {
        if (!activeTarget) { return; }
        var mode = $('#ceAiMode').val();
        var max = maxFor(activeTarget);
        var isHtml = activeTarget.is('textarea') && (activeTarget.hasClass('editor') || activeTarget.hasClass('text-editor') || activeTarget.hasClass('summernote-initialized'));
        $('#ceAiStatus').html('<i class="fa fa-spinner fa-spin"></i> Generating…');
        $('#ceAiGenerateBtn').prop('disabled', true);

        $.ajax({
            url: GENERATE_URL,
            method: 'POST',
            data: {
                _token: CSRF,
                mode: mode,
                module: CFG.module || 'pages',
                field_key: resolveFieldKey(activeTarget),
                content: getValue(activeTarget),
                topic: $('#ceAiTopic').val(),
                keywords: $('#ceAiKeywords').val(),
                instructions: $('#ceAiInstructions').val(),
                as_html: isHtml ? 1 : 0,
                max_chars: max
            }
        }).done(function (resp) {
            if (resp && resp.success) {
                $('#ceAiPreview').val(resp.content || '');
                updatePreviewCounter();
                $('#ceAiStatus').text('Generated successfully. Edit if needed, then Apply.');
            } else {
                $('#ceAiStatus').text((resp && resp.message) || 'Generation failed.');
            }
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'AI request failed.';
            $('#ceAiStatus').text(msg);
        }).always(function () {
            $('#ceAiGenerateBtn').prop('disabled', false);
        });
    }

    function applyAi() {
        if (!activeTarget) { return; }
        var max = maxFor(activeTarget);
        var val = $('#ceAiPreview').val() || '';
        var isHtml = activeTarget.is('textarea') && (activeTarget.hasClass('editor') || activeTarget.hasClass('text-editor') || activeTarget.hasClass('summernote-initialized'));
        var len = isHtml ? plainLength(val) : val.length;
        if (len > max) {
            $('#ceAiPreviewMsg').addClass('is-visible');
            return;
        }
        setValue(activeTarget, val);
        $('#ceAiModal').modal('hide');
        if (window.toastr) {
            toastr.success('AI content applied. Remember to save the page/course.');
        }
    }

    function enhanceAll(root) {
        var $root = root ? $(root) : $(document);
        $root.find('textarea.editor, textarea.text-editor, textarea.short_description, textarea.description').each(function () {
            enhanceElement(this);
        });
        if ((CFG.module || '') === 'pages') {
            $root.find('input[name="page_name"], input[name="url"]').each(function () {
                enhanceElement(this);
            });
            // Common page builder text inputs inside sections
            $root.find('#builder-container input[type="text"][name*="[title]"], #builder-container input[type="text"][name*="[heading]"], #builder-container input[type="text"][name*="[button_text]"], #builder-container textarea[name*="[description]"], #builder-container textarea[name*="[content]"]').each(function () {
                enhanceElement(this);
            });
        }
        if ((CFG.module || '') === 'courses') {
            $root.find('input[name="title"]').each(function () {
                enhanceElement(this);
            });
        }
    }

    // Public hook for Summernote after init
    window.ContentEditorEnhancer = {
        config: CFG,
        enhanceAll: enhanceAll,
        enhanceElement: enhanceElement,
        attachSummernote: function ($el) {
            enhanceElement($el.get ? $el.get(0) : $el);
            $el.on('summernote.change', function () {
                updateCounter($el);
            });
        },
        maxFor: maxFor,
        resolveFieldKey: resolveFieldKey
    };

    $(function () {
        ensureModal();
        enhanceAll(document);

        $(document).on('input change keyup', '.ce-field-wrap textarea, .ce-field-wrap input', function () {
            var $el = $(this);
            var isHtml = $el.hasClass('editor') || $el.hasClass('text-editor') || $el.hasClass('summernote-initialized');
            enforceOnInput($el, isHtml);
        });

        $(document).on('click', '.ce-ai-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $wrap = $btn.closest('.ce-field-wrap');
            var $el = $wrap.find('textarea, input').filter(':not(.note-codable)').first();
            if (!$el.length) { return; }
            openAiModal($el, $btn.data('mode'));
        });

        $(document).on('click', '#ceAiGenerateBtn', function (e) {
            e.preventDefault();
            generateAi();
        });
        $(document).on('click', '#ceAiApplyBtn', function (e) {
            e.preventDefault();
            applyAi();
        });
        $(document).on('input keyup', '#ceAiPreview', updatePreviewCounter);

        // Watch dynamically added page-builder / benefit rows
        if (window.MutationObserver) {
            var obs = new MutationObserver(function (mutations) {
                mutations.forEach(function (m) {
                    if (!m.addedNodes) return;
                    m.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1) {
                            enhanceAll(node);
                        }
                    });
                });
            });
            var targets = ['#builder-container', '#benefitsContainer', '.wrapper.wrapper-content'];
            targets.forEach(function (sel) {
                var el = document.querySelector(sel);
                if (el) {
                    obs.observe(el, { childList: true, subtree: true });
                }
            });
        }

        // Form submit soft validation (warn, don't wipe legacy content)
        $('form').on('submit', function (e) {
            var over = false;
            $(this).find('.ce-field-wrap textarea, .ce-field-wrap input').each(function () {
                var $el = $(this);
                var max = maxFor($el);
                var isHtml = $el.hasClass('editor') || $el.hasClass('text-editor') || $el.hasClass('summernote-initialized');
                var len = isHtml ? plainLength(getValue($el)) : String($el.val() || '').length;
                if (len > max) {
                    over = true;
                    updateCounter($el);
                }
            });
            if (over) {
                if (!confirm('Some fields exceed their character limits. Save anyway? (Existing content will not be deleted.)')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    });
})(window, jQuery);
</script>
@endpush
