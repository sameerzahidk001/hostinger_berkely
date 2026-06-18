@php($limits = seo_field_limits())
<script>
window.SeoFieldLimits = {
    priority: {
        maxTags: {{ $limits['priority_keywords_max_tags'] }},
        maxTagLength: {{ $limits['keyword_tag_max_length'] }},
        maxTotalLength: {{ $limits['priority_keywords_max_total'] }},
    },
    additional: {
        maxTags: {{ $limits['additional_keywords_max_tags'] }},
        maxTagLength: {{ $limits['keyword_tag_max_length'] }},
        maxTotalLength: {{ $limits['additional_keywords_max_total'] }},
    },
};

function attachSeoTagsCounter($input, limits) {
    const $wrap = $input.closest('.mb, .form-group, [class*="col-lg"]').first();
    let $counter = $wrap.find('.seo-tags-counter');
    if (!$counter.length) {
        $counter = $('<div class="seo-tags-counter char-count text-muted small text-right" style="margin-top:4px;"></div>');
        $wrap.append($counter);
    }

    const updateCounter = () => {
        const raw = ($input.val() || '').trim();
        const tags = raw ? raw.split(',').map(t => t.trim()).filter(Boolean) : [];
        const totalLen = raw.length;
        $counter.text(
            tags.length + ' / ' + limits.maxTags + ' keywords · ' + totalLen + ' / ' + limits.maxTotalLength + ' characters'
        );
        const over = tags.length > limits.maxTags || totalLen > limits.maxTotalLength;
        $counter.css('color', over ? '#bc1904' : '#888');
    };

    $input.on('change keyup', updateCounter);
    updateCounter();

    return updateCounter;
}

function initSeoTagsInput(selector, limitsKey) {
    const limits = window.SeoFieldLimits[limitsKey];
    const $el = $(selector);
    if (!$el.length || $el.data('seoTagsReady')) {
        return;
    }

    const updateCounter = attachSeoTagsCounter($el, limits);
    const defaultText = limitsKey === 'priority' ? 'Add priority keyword' : 'Add additional keyword';

    $el.tagsInput({
        defaultText: defaultText,
        unique: true,
        onAddTag: function (tag) {
            if (tag.length > limits.maxTagLength) {
                $el.removeTag(tag);
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Each keyword must be ' + limits.maxTagLength + ' characters or less.');
                }
                return;
            }

            const tags = ($el.val() || '').split(',').map(t => t.trim()).filter(Boolean);
            if (tags.length > limits.maxTags) {
                $el.removeTag(tag);
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Maximum ' + limits.maxTags + ' keywords allowed.');
                }
                return;
            }

            if (($el.val() || '').length > limits.maxTotalLength) {
                $el.removeTag(tag);
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Total keyword length cannot exceed ' + limits.maxTotalLength + ' characters.');
                }
                return;
            }

            updateCounter();
        },
        onRemoveTag: updateCounter,
    });

    $el.data('seoTagsReady', true);
    updateCounter();
}

$(document).ready(function () {
    initSeoTagsInput('#keywords', 'priority');
    initSeoTagsInput('#additional_keywords', 'additional');
    initSeoTagsInput('#meta_keywords', 'priority');
    initSeoTagsInput('#meta_additional_keywords', 'additional');
});
</script>
