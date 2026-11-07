@extends('admin.layout.app')
@section('title', 'Certificate templates')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Certificate templates</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.certificates.index') }}">Certificates</a></li>
            <li class="active"><strong>Templates</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.certificates.templates.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Add template</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    <div class="ibox">
        <div class="ibox-title"><h5>Templates used when marking complete / issuing</h5></div>
        <div class="ibox-content">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Title on PDF</th>
                            <th>Orientation</th>
                            <th>Status</th>
                            <th style="width:160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $row)
                            <tr>
                                <td>
                                    {{ $row->name }}
                                    @if($row->is_default)<span class="label label-primary" style="margin-left:6px;">Default</span>@endif
                                </td>
                                <td>{{ $row->titleText() }}</td>
                                <td>{{ ucfirst($row->paperOrientation()) }}</td>
                                <td>
                                    @if($row->is_active)
                                        <span class="label label-primary">Active</span>
                                    @else
                                        <span class="label label-default">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn btn-xs btn-white" href="{{ route('admin.certificates.templates.edit', $row->id) }}">Edit</a>
                                    @if(! $row->is_default)
                                        <form method="POST" action="{{ route('admin.certificates.templates.destroy', $row->id) }}" style="display:inline;" onsubmit="return confirm('Delete this template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No templates yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
