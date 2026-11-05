@extends('admin.layout.app')
@section('title', 'Media')
@section('content')
{{-- lms-media-20261105b alt/title/caption + unused filter --}}
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
    .lms-media-actions { display:flex; flex-direction:column; gap:4px; align-items:stretch; min-width:110px; }
    .lms-media-actions .btn { margin:0; }
    .lms-copy-ok { color:#1ab394; font-size:11px; display:none; }
    .lms-meta-preview { font-size:11px; color:#676a6c; margin-top:4px; line-height:1.35; }
    .lms-meta-preview strong { color:#333; }
    .lms-meta-form textarea.form-control { min-height:54px; }
    .lms-meta-form .form-group { margin-bottom:8px; }
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
                    <div class="col-md-6 form-group">
                        <label>Image file *</label>
                        <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.svg,image/*" required>
                        <span class="help-block">Saved to <code>images/library/</code>. Max 10 MB.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Alt text</label>
                        <input type="text" name="alt" class="form-control" value="{{ old('alt') }}" maxlength="255" placeholder="Describe the image">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" maxlength="255" placeholder="Optional title">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Caption</label>
                        <input type="text" name="caption" class="form-control" value="{{ old('caption') }}" maxlength="2000" placeholder="Optional caption">
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
                        <button type="button" class="btn btn-white lms-copy-url-btn" data-url="{{ session('uploaded_url') }}">
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
            <h5>All images <small class="text-muted">({{ (int) ($rowsTotal ?? $rows->total()) }} files · 15 per page)</small></h5>
        </div>
        <div class="ibox-content">
            <p class="text-muted">
                Edit <strong>Alt</strong>, <strong>Title</strong>, and <strong>Caption</strong> per image. Saving also updates linked pages/courses when that file is used.
                Use <strong>Unused only</strong> to find images safe to delete (delete is blocked while still linked).
            </p>
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
                            <th style="width:90px;">Image</th>
                            <th>File</th>
                            <th style="min-width:220px;">Alt / Title / Caption</th>
                            <th>Linked pages</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php $editId = 'meta-' . md5($row['rel']); @endphp
                            <tr>
                                <td>
                                    @if(($row['size'] ?? 0) > 800000)
                                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="lms-media-skip">Open</a>
                                    @else
                                        <img src="{{ $row['url'] }}" alt="{{ $row['alt'] ?: $row['name'] }}" title="{{ $row['title'] ?? '' }}" class="lms-media-thumb" width="72" height="54" loading="lazy" decoding="async" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'lms-media-skip',textContent:'Missing'}));">
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener">{{ $row['name'] }}</a>
                                    <div class="text-muted small">{{ $row['rel'] }}</div>
                                    <div class="text-muted small" style="word-break:break-all;">{{ $row['url'] }}</div>
                                    <div class="lms-meta-preview">
                                        <div><strong>Alt:</strong> {{ $row['alt'] ?: '—' }}</div>
                                        <div><strong>Title:</strong> {{ $row['title'] ?: '—' }}</div>
                                        <div><strong>Caption:</strong> {{ \Illuminate\Support\Str::limit($row['caption'] ?: '—', 80) }}</div>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-white" data-toggle="collapse" data-target="#{{ $editId }}">
                                        <i class="fa fa-pencil"></i> Edit meta
                                    </button>
                                    <div id="{{ $editId }}" class="collapse lms-meta-form" style="margin-top:8px;">
                                        <form method="POST" action="{{ route('admin.media.meta') }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="path" value="{{ $row['rel'] }}">
                                            <div class="form-group">
                                                <label>Alt</label>
                                                <input type="text" name="alt" class="form-control input-sm" value="{{ $row['alt'] }}" maxlength="255">
                                            </div>
                                            <div class="form-group">
                                                <label>Title</label>
                                                <input type="text" name="title" class="form-control input-sm" value="{{ $row['title'] }}" maxlength="255">
                                            </div>
                                            <div class="form-group">
                                                <label>Caption</label>
                                                <textarea name="caption" class="form-control input-sm" rows="2" maxlength="2000">{{ $row['caption'] }}</textarea>
                                            </div>
                                            <label class="checkbox-inline" style="margin-bottom:8px;">
                                                <input type="checkbox" name="sync_linked" value="1" checked>
                                                Also update linked pages/courses
                                            </label>
                                            <div>
                                                <button type="submit" class="btn btn-primary btn-xs">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    @if($row['links'] === [])
                                        <span class="label label-warning">Not linked</span>
                                        <div class="text-muted small" style="margin-top:4px;">Safe to delete if unused elsewhere</div>
                                    @else
                                        <ul class="list-unstyled m-b-none">
                                            @foreach($row['links'] as $link)
                                                @php [$label, $href] = array_pad(explode('|', $link, 2), 2, '#'); @endphp
                                                <li><a href="{{ $href }}">{{ $label }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                                <td>
                                    <div class="lms-media-actions">
                                        <button type="button" class="btn btn-white btn-xs lms-copy-url-btn" data-url="{{ $row['url'] }}" title="Copy full URL for src">
                                            <i class="fa fa-copy"></i> Copy URL
                                        </button>
                                        <span class="lms-copy-ok">Copied</span>
                                        @if(function_exists('admin_can_delete') ? admin_can_delete() : true)
                                            <form action="{{ route('admin.media.destroy') }}" method="POST" onsubmit="return confirm('Delete this image?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="path" value="{{ $row['rel'] }}">
                                                <button type="submit" class="btn btn-danger btn-xs" @disabled($row['links'] !== []) title="{{ $row['links'] !== [] ? 'Remove links first' : 'Delete unused image' }}">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No uploaded images found{{ !empty($unusedOnly) ? ' matching unused filter' : '' }}.</td></tr>
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
})();
</script>
@endpush
