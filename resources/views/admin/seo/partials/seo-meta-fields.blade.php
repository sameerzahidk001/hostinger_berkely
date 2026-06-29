@php
    $limits = seo_field_limits();
    $names = array_merge([
        'title' => 'title',
        'meta_description' => 'meta_description',
        'focus_keyword' => 'focus_keyword',
        'keywords' => 'keywords',
        'additional_keywords' => 'additional_keywords',
        'thumbnail_path' => 'thumbnail_img_path',
        'thumbnail_file' => 'thumbnail_img',
        'thumbnail_alt' => 'thumbnail_alt',
        'thumbnail_id_base' => 'thumbnail_img',
    ], $fieldNames ?? []);

    $seoMeta = $seoMeta ?? $page_seo ?? null;
    $idBase = $names['thumbnail_id_base'];
    $thumbnailValue = old($names['thumbnail_path'], $seoMeta->thumbnail ?? '');
@endphp

<div class="col-lg-12 mb">
    <label for="{{ $names['title'] }}">SEO Title <small>(max {{ $limits['title_max'] }} characters — browser tab &amp; Google)</small></label>
    <input type="text" class="form-control" name="{{ $names['title'] }}" id="{{ $names['title'] }}"
        placeholder="e.g. Page Name | Berkeley School"
        value="{{ old($names['title'], $seoMeta->title ?? '') }}"
        data-maxlength="{{ $limits['title_max'] }}"
        maxlength="{{ $limits['title_max'] }}">
    @error($names['title'])
        <p class="text-danger text-xs italic">{{ $message }}</p>
    @enderror
</div>

<div class="col-lg-12 mb">
    <label for="{{ $names['meta_description'] }}">Meta Description <small>( Max {{ $limits['meta_description_max'] }} characters )</small></label>
    <textarea class="form-control" name="{{ $names['meta_description'] }}" id="{{ $names['meta_description'] }}"
        placeholder="Add meta description"
        data-maxlength="{{ $limits['meta_description_max'] }}"
        maxlength="{{ $limits['meta_description_max'] }}">{{ old($names['meta_description'], $seoMeta->meta_description ?? '') }}</textarea>
    @error($names['meta_description'])
        <p class="text-danger text-xs italic">{{ $message }}</p>
    @enderror
</div>

<div class="col-lg-12 mb">
    <label for="{{ $names['focus_keyword'] }}">Focus Keyword <small>(primary phrase for SEO scoring — max {{ $limits['focus_keyword_max'] }} characters)</small></label>
    <input type="text" class="form-control" name="{{ $names['focus_keyword'] }}" id="{{ $names['focus_keyword'] }}"
        placeholder="e.g. Executive Women Leadership Programme"
        value="{{ old($names['focus_keyword'], $seoMeta->focus_keyword ?? '') }}"
        data-maxlength="{{ $limits['focus_keyword_max'] }}"
        maxlength="{{ $limits['focus_keyword_max'] }}">
    <p class="help-block text-muted" style="margin-top:4px;">Main keyword the SEO tool checks in title, description, URL, and live page H1. If left empty, the first priority keyword is used.</p>
    @error($names['focus_keyword'])
        <p class="text-danger text-xs italic">{{ $message }}</p>
    @enderror
</div>

<div class="col-lg-12 mb tags-field">
    <label for="{{ $names['keywords'] }}">Priority Keywords <small>( Max {{ $limits['priority_keywords_max_tags'] }} keywords, {{ $limits['keyword_tag_max_length'] }} chars each )</small></label>
    <input class="form-control" name="{{ $names['keywords'] }}" id="{{ $names['keywords'] }}"
        placeholder="Add keywords with comma separated"
        value="{{ old($names['keywords'], $seoMeta->keywords ?? '') }}">
    @error($names['keywords'])
        <p class="text-danger text-xs italic">{{ $message }}</p>
    @enderror
</div>

<div class="col-lg-12 mb tags-field">
    <label for="{{ $names['additional_keywords'] }}">Additional Keywords <small>( Max {{ $limits['additional_keywords_max_tags'] }} keywords, {{ $limits['keyword_tag_max_length'] }} chars each )</small></label>
    <input class="form-control" name="{{ $names['additional_keywords'] }}" id="{{ $names['additional_keywords'] }}"
        placeholder="Add keywords with comma separated"
        value="{{ old($names['additional_keywords'], $seoMeta->additional_keywords ?? '') }}">
    @error($names['additional_keywords'])
        <p class="text-danger text-xs italic">{{ $message }}</p>
    @enderror
</div>

<div class="col-lg-12 mb">
    <label class="mb-1">Thumbnail <small>( 1200 x 627 px )</small></label>

    <div class="input-group">
        <input type="text" id="{{ $idBase }}_display" class="form-control"
            placeholder="Choose or upload..." readonly
            value="{{ $thumbnailValue }}"
            onclick="MediaPicker.open({ idBase:'{{ $idBase }}', multiple:false, accept:'image/*', fmType:'image' })">
        <span class="input-group-btn">
            <button class="btn btn-default" type="button"
                onclick="MediaPicker.open({ idBase:'{{ $idBase }}', multiple:false, accept:'image/*', fmType:'image' })">
                Browse
            </button>
        </span>
    </div>

    <input type="hidden" id="{{ $idBase }}_path" name="{{ $names['thumbnail_path'] }}"
        value="{{ $thumbnailValue }}">

    <input type="file" id="{{ $idBase }}_file" name="{{ $names['thumbnail_file'] }}" accept="image/*"
        style="display:none">

    <div id="{{ $idBase }}_preview" class="picker-preview" style="margin-top:6px;">
        @if($thumbnailValue)
            <img src="{{ asset(ltrim($thumbnailValue, '/')) }}"
                style="max-height:60px;border-radius:4px;" alt="">
        @endif
    </div>

    @if($thumbnailValue)
        <a href="{{ asset(ltrim($thumbnailValue, '/')) }}" target="_blank" rel="noopener">View image here</a>
    @endif

    @include('admin.partials.image-alt-input', [
        'id' => $names['thumbnail_alt'],
        'name' => $names['thumbnail_alt'],
        'value' => old($names['thumbnail_alt'], $seoMeta->thumbnail_alt ?? ''),
    ])
    <p class="help-block text-muted" style="margin-top:4px;">Describe the social/OG thumbnail for accessibility and SEO scoring (include focus keyword when relevant).</p>
</div>
