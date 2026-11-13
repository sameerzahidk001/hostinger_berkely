@php
    $seoMeta = $seoMeta ?? null;
    $colClass = $colClass ?? 'col-lg-4';
    $indexValue = old('robots_index', $seoMeta->robots_index ?? 'index');
    $followValue = old('robots_follow', $seoMeta->robots_follow ?? 'follow');
@endphp
<div class="{{ $colClass }}">
    <label for="robots_index">Google Index</label>
    <select class="form-control" name="robots_index" id="robots_index">
        <option value="index" @selected($indexValue === 'index')>Index (allow Google to list)</option>
        <option value="noindex" @selected($indexValue === 'noindex')>No Index (hide from Google)</option>
    </select>
    <p class="help-block text-muted" style="margin-top:4px;">New pages default to Index. Choose No Index for drafts or private URLs.</p>
</div>
<div class="{{ $colClass }}">
    <label for="robots_follow">Follow Links</label>
    <select class="form-control" name="robots_follow" id="robots_follow">
        <option value="follow" @selected($followValue === 'follow')>Follow links</option>
        <option value="nofollow" @selected($followValue === 'nofollow')>No Follow links</option>
    </select>
    <p class="help-block text-muted" style="margin-top:4px;">Usually keep Follow. No Follow tells Google not to use the links on this page.</p>
</div>
