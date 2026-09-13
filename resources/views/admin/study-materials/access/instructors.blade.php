@extends('admin.layout.app')
@section('title', 'Instructor Access')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Instructor Access</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Instructor Access</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.study-materials.access.assign-instructor') }}" class="btn btn-primary">Assign Instructor</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Instructor access</h5></div>
        <div class="ibox-content table-responsive">
            <form method="GET" action="{{ route('admin.study-materials.access.instructors') }}" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-sm-8 col-md-6">
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search instructor, email, folder or course">
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if($search !== '')
                            <a href="{{ route('admin.study-materials.access.instructors') }}" class="btn btn-default">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Folder Code</th><th>Folder</th><th>Instructor</th><th>Course</th><th>Issued</th><th>Till</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->folder->code ?? '—' }}</td>
                            <td>{{ $row->folder->name ?? '—' }}</td>
                            <td>
                                {{ $row->instructor->name ?? '—' }}
                                @if(!empty($row->instructor->email))
                                    <br><small class="text-muted">{{ $row->instructor->email }}</small>
                                @endif
                            </td>
                            <td>{{ $row->folder->course->title ?? '—' }}</td>
                            <td>{{ optional($row->issued_at)->format('d M Y') }}</td>
                            <td>{{ optional($row->access_till)->format('d M Y') ?? 'None' }}</td>
                            <td><span class="label {{ $row->status === 'active' ? 'label-primary' : 'label-warning' }}">{{ ucfirst($row->status) }}</span></td>
                            <td>
                                <a class="btn btn-xs btn-default" href="{{ route('admin.study-materials.access.instructor.edit', $row->id) }}">Edit</a>
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.study-materials.access.instructor.send', $row->id) }}">Send</a>
                                <a class="btn btn-xs btn-danger" href="{{ route('admin.study-materials.access.instructor.disable', $row->id) }}">Disable</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No instructor access records{{ $search !== '' ? ' matching this search' : '' }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
