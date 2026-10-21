@extends('admin.layout.app')
@section('title', 'Media')
@section('content')
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
            <h5>All images</h5>
        </div>
        <div class="ibox-content">
            <p class="text-muted">Images in <code>images/</code> and <code>uploads/</code>. Linked pages and courses are listed so you can see where each file is used.</p>
            <div class="form-group">
                <input type="search" id="lms-media-filter" class="form-control" placeholder="Search filename or page">
            </div>
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
                                    <img src="{{ $row['url'] }}" alt="" style="width:72px;height:54px;object-fit:cover;border:1px solid #e7eaec;">
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
        </div>
    </div>
</div>
@endsection
@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var filter = document.getElementById('lms-media-filter');
    var table = document.getElementById('lms-media-table');
    if (!filter || !table) return;
    filter.addEventListener('input', function () {
        var q = (this.value || '').toLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.style.display = !q || (row.textContent || '').toLowerCase().indexOf(q) !== -1 ? '' : 'none';
        });
    });
});
</script>
@endpush
