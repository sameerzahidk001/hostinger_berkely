@extends('admin.layout.app')

@section('title', 'Path Branding')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Path Branding (Logo &amp; Menu)</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('site-settings.index') }}">Site Settings</a></li>
            <li class="active"><strong>Path Branding</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.path-branding.create') }}" class="btn btn-primary">
            <i class="fa fa-plus"></i> Add Path Branding
        </a>
    </div>
</div>

<div class="wrapper wrapper-content">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('fail'))
        <div class="alert alert-danger">{{ session('fail') }}</div>
    @endif

    <div class="ibox">
        <div class="ibox-title">
            <h5>URL path groups</h5>
        </div>
        <div class="ibox-content">
            <p class="text-muted" style="margin-bottom:16px;">
                Use this for standalone sections such as <code>/academic-journal</code> and
                <code>/academic-journal/about</code>. Matching pages show this logo and menu instead of the main site header.
            </p>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Path prefix</th>
                            <th>Logo</th>
                            <th>Menu group</th>
                            <th>Home URL</th>
                            <th>Status</th>
                            <th style="width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->name }}</td>
                                <td><code>/{{ $row->path_prefix }}</code></td>
                                <td>
                                    @if($row->logoUrl())
                                        <a href="{{ $row->logoUrl() }}" target="_blank" rel="noopener">
                                            <img src="{{ $row->logoUrl() }}" alt="" style="max-height:36px;max-width:120px;object-fit:contain;">
                                        </a>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>{{ $row->menu_group ?: '—' }}</td>
                                <td><code>{{ $row->home_url ?: ('/' . $row->path_prefix) }}</code></td>
                                <td>
                                    @if($row->is_active)
                                        <span class="label label-primary">Active</span>
                                    @else
                                        <span class="label label-default">Off</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.path-branding.edit', $row->id) }}" class="btn btn-xs btn-primary">Edit</a>
                                    <form action="{{ route('admin.path-branding.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Remove this path branding?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    No path branding yet. Add one for <code>academic-journal</code>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
