@extends('admin.layout.app')
@section('title', 'Media')
@section('content')
{{-- lms-media-20261106c match filename-only page image links --}}
@php
    $seoLimits = function_exists('seo_field_limits') ? seo_field_limits() : [];
    $altMax = (int) ($seoLimits['image_alt_max'] ?? 125);
    $titleMax = (int) ($seoLimits['image_title_max'] ?? 60);
    $captionMax = (int) ($seoLimits['image_caption_max'] ?? 125);
    $descriptionMax = (int) ($seoLimits['image_description_max'] ?? 160);
@endphp
<style>
    .lms-assigned-pager {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        padding: 12px 0 4px;
        flex-wrap: wrap;
    }
    .lms-assigned-pager .lms-page-label { font-weight: 600; color: #333; }
    .lms-media-thumb { width:72px;height:54px;object-fit:cover;border:1px solid #e7eaec;display:block;background:#f4f4f4; }
    .lms-media-skip { display:block;width:72px;height:54px;line-height:54px;text-align:center;background:#f4f4f4;border:1px solid #e7eaec;font-size:10px;color:#888; }
    .lms-media-broken { display:block;width:72px;height:54px;padding:6px 4px;line-height:1.2;text-align:center;background:#fff0f0;border:1px solid #f5c2c2;font-size:10px;color:#c0392b; }
    .lms-media-size { display:block; margin-top:4px; font-size:11px; color:#555; line-height:1.3; text-align:center; max-width:86px; font-weight:600; }
    .lms-media-size.is-over { color:#c0392b; }
    .lms-media-size-badge { display:inline-block; margin-top:2px; padding:1px 5px; border-radius:3px; background:#f5f5f5; font-size:10px; font-weight:600; color:#555; }
    .lms-media-size-badge.is-over { background:#fdecea; color:#c0392b; }
    .lms-media-actions { display:flex; flex-direction:column; gap:4px; align-items:stretch; min-width:110px; }
    .lms-media-actions .btn { margin:0; }
    .lms-copy-ok { color:#1ab394; font-size:11px; display:none; }
    .lms-meta-preview { font-size:11px; color:#676a6c; margin-top:4px; line-height:1.35; }
    .lms-meta-preview strong { color:#333; }
    .lms-meta-col { min-width:140px; max-width:200px; vertical-align:top !important; }
    .lms-meta-col .form-control { font-size:12px; }
    .lms-meta-col textarea.form-control { min-height:54px; resize:vertical; }
    .lms-meta-missing { background:#fff8e6 !important; border-color:#f0ad4e !important; }
    .lms-meta-empty-label { display:block; font-size:10px; color:#c87f0a; margin-top:3px; }
    .lms-meta-col-label { display:block; font-size:10px; font-weight:600; color:#888; text-transform:uppercase; letter-spacing:.02em; margin-bottom:3px; }
    #lms-media-table th.lms-meta-col { white-space:nowrap; }
    .lms-media-uploaded { white-space:nowrap; font-size:12px; line-height:1.35; }
    .lms-media-uploaded .t { display:block; color:#888; font-size:11px; }
    #lms-media-upload .ibox-content { padding-top:12px; padding-bottom:12px; }
    #lms-media-upload .form-group { margin-bottom:8px; }
    #lms-media-upload .form-group > label { margin-bottom:3px; }
    #lms-media-upload .help-block { margin:3px 0 0; }
    #lms-media-upload textarea.form-control { min-height:36px; height:36px; resize:vertical; }
    #lms-media-upload .row { margin-bottom:0; }
    #lms-media-table thead th.lms-sort { cursor:pointer; user-select:none; white-space:nowrap; }
    #lms-media-table thead th.lms-sort:after { content:' \2195'; color:#bbb; font-size:11px; }
    #lms-media-table thead th.lms-sort.is-asc:after { content:' \25B2'; color:#333; }
    #lms-media-table thead th.lms-sort.is-desc:after { content:' \25BC'; color:#333; }
    .lms-media-links a { display:block; }
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Media</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Media</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.media.download-images-zip') }}" class="btn btn-white">
            <i class="fa fa-download"></i> Download images zip
        </a>
        <button type="button" class="btn btn-primary" data-toggle="collapse" data-target="#lms-media-upload">
            <i class="fa fa-upload"></i> Upload image
        </button>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div id="lms-media-upload" class="ibox collapse {{ $errors->has('image') || session('uploaded_url') ? 'in' : '' }}">
        <div class="ibox-title"><h5>Upload image</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.media.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Image file *</label>
                            <input type="file" name="image" class="form-control lms-image-max-input" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,image/*" required data-max-kb="{{ function_exists('lms_image_upload_max_kb') ? lms_image_upload_max_kb() : 200 }}">
                            <span class="help-block">Saved to <code>images/library/</code>. Make sure the image size is Max 200 KB.</span>
                        </div>
                        <div class="form-group">
                            <label>Image Caption</label>
                            <input type="text" name="caption" class="form-control" value="{{ old('caption') }}" maxlength="{{ $captionMax }}" data-maxlength="{{ $captionMax }}" placeholder="Optional caption">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Alternative Text</label>
                            <input type="text" name="alt" class="form-control" value="{{ old('alt') }}" maxlength="{{ $altMax }}" data-maxlength="{{ $altMax }}" placeholder="Describe the image">
                        </div>
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" maxlength="{{ $titleMax }}" data-maxlength="{{ $titleMax }}" placeholder="Optional title">
                        </div>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="1" maxlength="{{ $descriptionMax }}" data-maxlength="{{ $descriptionMax }}" placeholder="Optional longer description">{{ old('description') }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> Upload</button>
            </form>
            @if(session('uploaded_url'))
                <hr>
                <label>Full URL</label>
                <div class="input-group">
                    <input type="text" class="form-control" value="{{ session('uploaded_url') }}" readonly>
                    <span class="input-group-btn">
                        <button type="button" class="btn btn-info lms-copy-url-btn" data-url="{{ session('uploaded_url') }}">
                            <i class="fa fa-copy"></i> Copy URL
                        </button>
                    </span>
                </div>
                <span class="help-block">Paste as <code>src="…"</code> in HTML.</span>
            @endif
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title">
            <h5>All images <small class="text-muted">({{ (int) ($rowsTotal ?? $rows->total()) }} images · 30 per page)</small></h5>
        </div>
        <div class="ibox-content">
            @if(!empty($unusedSkipped))
                <div class="alert alert-warning">{{ $unusedSkipped }}</div>
            @endif
            <form method="GET" class="form-group" action="{{ route('admin.media.index') }}">
                <div class="row">
                    <div class="col-sm-7">
                        <div class="input-group">
                            <input type="search" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="Search filename">
                            <span class="input-group-btn">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </span>
                        </div>
                    </div>
                    <div class="col-sm-5" style="padding-top:7px;">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="unused" value="1" @checked(!empty($unusedOnly)) onchange="this.form.submit()">
                            Unused only (not linked to a page/course)
                        </label>
                        @if(!empty($unusedOnly) || ($search ?? '') !== '')
                            <a href="{{ route('admin.media.index') }}" class="btn btn-default btn-xs" style="margin-left:8px;">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="lms-media-table">
                    <thead>
                        <tr>
                            <th class="lms-sort" data-sort="num" style="width:110px;">Uploaded date</th>
                            <th class="lms-sort" data-sort="text" style="width:90px;">Image</th>
                            <th class="lms-sort" data-sort="text" style="min-width:160px;">File</th>
                            <th class="lms-sort lms-meta-col" data-sort="input">Alternative Text</th>
                            <th class="lms-sort lms-meta-col" data-sort="input">Title</th>
                            <th class="lms-sort lms-meta-col" data-sort="input">Image Caption</th>
                            <th class="lms-sort lms-meta-col" data-sort="input">Description</th>
                            <th class="lms-sort" data-sort="text" data-col="links">Linked pages</th>
                            <th style="width:130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $formId = 'meta-' . md5($row['rel']);
                                $altMissing = trim((string) ($row['alt'] ?? '')) === '';
                                $titleMissing = trim((string) ($row['title'] ?? '')) === '';
                                $captionMissing = trim((string) ($row['caption'] ?? '')) === '';
                                $descriptionMissing = trim((string) ($row['description'] ?? '')) === '';
                                $bytes = (int) ($row['size'] ?? 0);
                                $maxBytes = function_exists('lms_image_upload_max_bytes') ? lms_image_upload_max_bytes() : (200 * 1024);
                                $isOver = $bytes > $maxBytes;
                                if ($bytes >= 1048576) {
                                    $sizeLabel = rtrim(rtrim(number_format($bytes / 1048576, 2, '.', ''), '0'), '.') . ' MB';
                                } elseif ($bytes >= 1024) {
                                    $sizeLabel = number_format((int) round($bytes / 1024)) . ' KB';
                                } else {
                                    $sizeLabel = $bytes . ' B';
                                }
                                $dimLabel = '';
                                if (! empty($row['width']) && ! empty($row['height'])) {
                                    $dimLabel = (int) $row['width'] . '×' . (int) $row['height'];
                                }
                                $hasLinks = ! empty($row['links']);
                                $canDelete = ! function_exists('admin_can_delete') || admin_can_delete();
                                $deleteTitle = $hasLinks ? 'Remove links first' : 'Delete unused image';
                            @endphp
                            <tr @if($isOver) style="background:#fff8f6;" @endif>
                                <td class="lms-media-uploaded" data-sort-value="{{ (int) ($row['mtime'] ?? 0) }}">
                                    {{ $row['uploaded_date'] ?? '—' }}
                                    @if(!empty($row['uploaded_time']))
                                        <span class="t">{{ $row['uploaded_time'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if(empty($row['valid']))
                                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="lms-media-broken" title="File exists but is not a valid image (Hostinger returns Invalid source image)">Broken file</a>
                                    @else
                                        <img src="{{ $row['url'] }}" alt="{{ $row['alt'] ?: $row['name'] }}" title="{{ $row['title'] ?? '' }}" class="lms-media-thumb" width="72" height="54" loading="lazy" decoding="async" onerror="this.replaceWith(Object.assign(document.createElement('a'),{className:'lms-media-broken',textContent:'Broken file',href:this.src,target:'_blank',rel:'noopener'}));">
                                    @endif
                                    <span class="lms-media-size {{ $isOver ? 'is-over' : '' }}" title="{{ number_format($bytes) }} bytes">
                                        @if($dimLabel !== ''){{ $dimLabel }}<br>@endif
                                        <span class="lms-media-size-badge {{ $isOver ? 'is-over' : '' }}">{{ $sizeLabel }}@if($isOver) · over 200 KB @endif</span>
                                    </span>
                                </td>
                                <td data-sort-value="{{ strtolower((string) $row['name']) }}">
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener" style="word-break:break-all;">{{ $row['name'] }}</a>
                                    <label class="lms-meta-col-label" style="margin-top:6px;">Filename</label>
                                    <input type="text" name="filename" form="{{ $formId }}" class="form-control input-sm" value="{{ $row['name'] }}" maxlength="255" title="Rename file">
                                    @if(!empty($row['also_on_disk']))
                                        <div class="text-muted small" style="margin-top:6px;">Also on disk: {{ $row['also_on_disk'] }}</div>
                                    @endif
                                </td>
                                <td class="lms-meta-col">
                                    <input type="text" name="alt" form="{{ $formId }}" class="form-control input-sm {{ $altMissing ? 'lms-meta-missing' : '' }}" value="{{ $row['alt'] }}" maxlength="{{ $altMax }}" data-maxlength="{{ $altMax }}" placeholder="—">
                                    @if($altMissing)<span class="lms-meta-empty-label">Empty</span>@endif
                                </td>
                                <td class="lms-meta-col">
                                    <input type="text" name="title" form="{{ $formId }}" class="form-control input-sm {{ $titleMissing ? 'lms-meta-missing' : '' }}" value="{{ $row['title'] }}" maxlength="{{ $titleMax }}" data-maxlength="{{ $titleMax }}" placeholder="—">
                                    @if($titleMissing)<span class="lms-meta-empty-label">Empty</span>@endif
                                </td>
                                <td class="lms-meta-col">
                                    <textarea name="caption" form="{{ $formId }}" class="form-control input-sm {{ $captionMissing ? 'lms-meta-missing' : '' }}" rows="2" maxlength="{{ $captionMax }}" data-maxlength="{{ $captionMax }}" placeholder="—">{{ $row['caption'] }}</textarea>
                                    @if($captionMissing)<span class="lms-meta-empty-label">Empty</span>@endif
                                </td>
                                <td class="lms-meta-col">
                                    <textarea name="description" form="{{ $formId }}" class="form-control input-sm {{ $descriptionMissing ? 'lms-meta-missing' : '' }}" rows="2" maxlength="{{ $descriptionMax }}" data-maxlength="{{ $descriptionMax }}" placeholder="—">{{ $row['description'] ?? '' }}</textarea>
                                    @if($descriptionMissing)<span class="lms-meta-empty-label">Empty</span>@endif
                                </td>
                                <td class="lms-media-links" data-col="links" data-rels="{{ e(json_encode($row['variant_rels'] ?? [$row['rel']])) }}">
                                    <span class="text-muted small">Checking links…</span>
                                </td>
                                <td>
                                    <form id="{{ $formId }}" method="POST" action="{{ route('admin.media.meta') }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="path" value="{{ $row['rel'] }}">
                                        <input type="hidden" name="sync_linked" value="1">
                                        <button type="submit" class="btn btn-primary btn-xs btn-block">
                                            <i class="fa fa-save"></i> Save
                                        </button>
                                    </form>
                                    <div class="lms-media-actions" style="margin-top:6px;">
                                        <button type="button" class="btn btn-info btn-xs lms-copy-url-btn" data-url="{{ $row['url'] }}" title="Copy full URL for src">
                                            <i class="fa fa-copy"></i> Copy URL
                                        </button>
                                        <span class="lms-copy-ok">Copied</span>
                                        @if($canDelete)
                                            <form action="{{ route('admin.media.destroy') }}" method="POST" onsubmit="return confirm('Delete this image?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="path" value="{{ $row['rel'] }}">
                                                <button type="submit" class="btn btn-danger btn-xs lms-media-delete" @if($hasLinks) disabled @endif title="{{ $deleteTitle }}">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9">No uploaded images found{{ !empty($unusedOnly) ? ' matching unused filter' : '' }}.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.assessments.partials.table-pager', ['pager' => $rows])
        </div>
    </div>
</div>
@endsection
@push('script')
<script>
(function () {
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }
        return new Promise(function (resolve, reject) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy') ? resolve() : reject();
            } catch (e) {
                reject(e);
            }
            document.body.removeChild(ta);
        });
    }

    $(document).on('click', '.lms-copy-url-btn', function () {
        var btn = $(this);
        var url = btn.attr('data-url') || '';
        if (!url) return;
        copyText(url).then(function () {
            var ok = btn.siblings('.lms-copy-ok');
            if (ok.length) {
                ok.show();
                setTimeout(function () { ok.fadeOut(200); }, 1500);
            } else {
                var prev = btn.html();
                btn.html('<i class="fa fa-check"></i> Copied');
                setTimeout(function () { btn.html(prev); }, 1500);
            }
        }).catch(function () {
            window.prompt('Copy this URL:', url);
        });
    });

    $(document).on('input', '.lms-meta-col .form-control', function () {
        var el = $(this);
        var empty = !$.trim(el.val() || '');
        el.toggleClass('lms-meta-missing', empty);
        var label = el.siblings('.lms-meta-empty-label');
        if (empty) {
            if (!label.length) {
                el.after('<span class="lms-meta-empty-label">Empty</span>');
            }
        } else {
            label.remove();
        }
    });

    $(document).on('change', '.lms-image-max-input', function () {
        var input = this;
        var maxKb = parseInt(input.getAttribute('data-max-kb') || '200', 10);
        var file = input.files && input.files[0];
        if (!file) return;
        if (file.size > maxKb * 1024) {
            alert('Image must be ' + maxKb + ' KB or smaller. This file is ' + Math.round(file.size / 1024) + ' KB.');
            input.value = '';
        }
    });

    var linksUrl = @json($mediaLinksUrl ?? '');
    function renderLinks(cell, usages) {
        var rels = [];
        try { rels = JSON.parse(cell.getAttribute('data-rels') || '[]'); } catch (e) { rels = []; }
        var found = [];
        var seen = {};
        rels.forEach(function (rel) {
            var key = String(rel || '').replace(/\\/g, '/').toLowerCase();
            var name = key.split('/').pop();
            var stem = name.replace(/\.[^.]+$/, '');
            var list = [].concat(
                (usages.by_path && usages.by_path[key]) || [],
                (usages.by_name && usages.by_name[name]) || [],
                (usages.by_name && usages.by_name[stem]) || []
            );
            list.forEach(function (item) {
                if (!item || seen[item]) return;
                seen[item] = true;
                found.push(item);
            });
        });
        found.sort();
        var $cell = $(cell);
        var $row = $cell.closest('tr');
        var $del = $row.find('.lms-media-delete');
        if (!found.length) {
            $cell.attr('data-sort-value', 'not-linked').html('<span class="label label-warning">Not linked</span><div class="text-muted small" style="margin-top:4px;">Safe to delete if unused elsewhere</div>');
            $del.prop('disabled', false).attr('title', 'Delete unused image');
            return;
        }
        var html = '<ul class="list-unstyled m-b-none">';
        var labels = [];
        found.forEach(function (item) {
            var parts = String(item).split('|');
            var label = parts.shift() || 'Page';
            var href = parts.join('|') || '#';
            labels.push(label);
            html += '<li><a href="' + href.replace(/"/g, '&quot;') + '">' + $('<div>').text(label).html() + '</a></li>';
        });
        html += '</ul>';
        $cell.attr('data-sort-value', labels.join(' ').toLowerCase()).html(html);
        $del.prop('disabled', true).attr('title', 'Remove links first');
    }
    function loadMediaLinks() {
        if (!linksUrl) return;
        var cells = $('#lms-media-table .lms-media-links').toArray();
        var rels = [];
        var seen = {};
        cells.forEach(function (cell) {
            var list = [];
            try { list = JSON.parse(cell.getAttribute('data-rels') || '[]'); } catch (e) { list = []; }
            list.forEach(function (rel) {
                if (!rel || seen[rel]) return;
                seen[rel] = true;
                rels.push(rel);
            });
        });
        if (!rels.length) return;
        var csrf = document.querySelector('meta[name="csrf-token"]');
        fetch(linksUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf ? csrf.content : ''
            },
            body: JSON.stringify({ rel: rels })
        })
            .then(function (res) {
                if (!res.ok) throw new Error('links ' + res.status);
                return res.json();
            })
            .then(function (data) {
                var usages = (data && data.usages) ? data.usages : { by_path: {}, by_name: {} };
                cells.forEach(function (cell) { renderLinks(cell, usages); });
            })
            .catch(function () {
                cells.forEach(function (cell) {
                    $(cell).attr('data-sort-value', 'zzz').html('<span class="text-muted small">Could not load linked pages. Refresh this page.</span>');
                });
            });
    }
    loadMediaLinks();

    $('#lms-media-table thead').on('click', 'th.lms-sort', function () {
        var $th = $(this);
        var $table = $('#lms-media-table');
        var index = $th.index();
        var col = $th.attr('data-col');
        var type = $th.data('sort') || 'text';
        var asc = !$th.hasClass('is-asc');
        $table.find('thead th').removeClass('is-asc is-desc');
        $th.addClass(asc ? 'is-asc' : 'is-desc');
        var rows = $table.find('tbody tr').get();
        rows.sort(function (a, b) {
            var ac = col ? a.querySelector('[data-col="' + col + '"]') : a.cells[index];
            var bc = col ? b.querySelector('[data-col="' + col + '"]') : b.cells[index];
            var av = sortValue(ac, type);
            var bv = sortValue(bc, type);
            if (av < bv) return asc ? -1 : 1;
            if (av > bv) return asc ? 1 : -1;
            return 0;
        });
        $table.find('tbody').append(rows);
    });
    function sortValue(cell, type) {
        if (!cell) return '';
        var attr = cell.getAttribute('data-sort-value');
        if (type === 'num') return parseFloat(attr || '0') || 0;
        if (type === 'input') {
            var field = cell.querySelector('input, textarea');
            return ((field && field.value) || '').toLowerCase();
        }
        return String(attr || cell.textContent || '').toLowerCase().trim();
    }
})();
</script>
@endpush
