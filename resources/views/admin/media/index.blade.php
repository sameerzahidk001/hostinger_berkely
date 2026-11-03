@extends('admin.layout.app')
@section('title', 'Media')
@section('content')
{{-- lms-media-20261103a page-by-page; do not load every image at once --}}
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
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Media</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Media</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    <div class="ibox">
        <div class="ibox-title">
            <h5>All images <small class="text-muted">({{ (int) ($rowsTotal ?? $rows->total()) }} files · 15 per page)</small></h5>
        </div>
        <div class="ibox-content">
            <p class="text-muted">Images in <code>images/</code> and <code>uploads/</code>. Linked pages and courses are listed so you can see where each file is used. List 20261103a</p>
            <form method="GET" class="form-group" action="{{ route('admin.media.index') }}">
                <div class="input-group">
                    <input type="search" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="Search filename">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </span>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="lms-media-table">
                    <thead>
                        <tr>
                            <th style="width:90px;">Image</th>
                            <th>File</th>
                            <th>Linked pages</th>
                            <th style="width:90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    @if(($row['size'] ?? 0) > 800000)
                                        <a href="{{ $row['url'] }}" target="_blank" rel="noopener" class="lms-media-skip">Open</a>
                                    @else
                                        <img src="{{ $row['url'] }}" alt="{{ $row['name'] }}" class="lms-media-thumb" width="72" height="54" loading="lazy" decoding="async" onerror="this.replaceWith(Object.assign(document.createElement('span'),{className:'lms-media-skip',textContent:'Missing'}));">
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ $row['url'] }}" target="_blank" rel="noopener">{{ $row['name'] }}</a>
                                    <div class="text-muted small">{{ $row['rel'] }}</div>
                                </td>
                                <td>
                                    @if($row['links'] === [])
                                        <span class="text-muted">Not linked</span>
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
                                    @if(function_exists('admin_can_delete') ? admin_can_delete() : true)
                                        <form action="{{ route('admin.media.destroy') }}" method="POST" onsubmit="return confirm('Delete this image?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="path" value="{{ $row['rel'] }}">
                                            <button type="submit" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No uploaded images found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('admin.assessments.partials.table-pager', ['pager' => $rows])
        </div>
    </div>
</div>
@endsection
