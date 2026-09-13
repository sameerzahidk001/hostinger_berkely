@extends('admin.layout.app')
@section('title', 'Study Material Folders')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Study Material Folders</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Folders</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.study-materials.folders.create') }}" class="btn btn-primary">Create Folder</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    <div class="ibox">
        <div class="ibox-content table-responsive">
            <form method="GET" action="{{ route('admin.study-materials.folders.index') }}" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-sm-8 col-md-6">
                        <input type="text" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="Search folder code, name, course or instructor">
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(($search ?? '') !== '')
                            <a href="{{ route('admin.study-materials.folders.index') }}" class="btn btn-default">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Folder Code</th>
                        <th>Created Date</th>
                        <th>Folder</th>
                        <th>Course</th>
                        <th>Package</th>
                        <th>Instructor</th>
                        <th>Owner</th>
                        <th>Folder Status</th>
                        @if($isAdmin)<th>Instructor Access</th>@endif
                        <th>Actions</th>
                        <th>Assign Access</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($folders as $folder)
                        @php
                            $activeInstructor = $folder->instructorAccess->firstWhere('status', 'active');
                            $anyInstructor = $folder->instructorAccess->first();
                        @endphp
                        <tr>
                            <td><strong>{{ $folder->code ?: '—' }}</strong></td>
                            <td>{{ $folder->created_at?->timezone(config('app.timezone'))->format('d M Y') }}</td>
                            <td><strong>{{ $folder->name }}</strong></td>
                            <td>{{ $folder->course->title ?? '—' }}</td>
                            <td>{{ $folder->packageNames() }}</td>
                            <td>{{ $folder->instructorNames() }}</td>
                            <td>{{ $folder->ownerName() }}</td>
                            <td>
                                <span class="label {{ $folder->status === 'active' ? 'label-primary' : 'label-default' }}">
                                    {{ ucfirst($folder->status) }}
                                </span>
                            </td>
                            @if($isAdmin)
                            <td>
                                @if($activeInstructor)
                                    <span class="label label-primary">Active</span>
                                @elseif($anyInstructor)
                                    <span class="label label-warning">Disabled</span>
                                @else
                                    <span class="label label-default">—</span>
                                @endif
                            </td>
                            @endif
                            <td>
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.study-materials.folders.edit', $folder->id) }}">Edit</a>
                                @if($isAdmin)
                                <form action="{{ route('admin.study-materials.folders.destroy', $folder->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this folder? All student and instructor access will be removed.');">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-xs btn-danger" type="submit">Delete</button>
                                </form>
                                @endif
                            </td>
                            <td>
                                @if($folder->status === 'active')
                                    <a class="btn btn-xs btn-success" href="{{ route('admin.study-materials.access.assign-student', ['folder_id' => $folder->id]) }}">Assign Student</a>
                                    <form action="{{ route('admin.study-materials.folders.send-students', $folder->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Email all assigned students for this folder?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-primary">Send students ({{ $folder->studentAccess->count() }})</button>
                                    </form>
                                @endif
                                @if($isAdmin)
                                    <a class="btn btn-xs btn-default" href="{{ route('admin.study-materials.access.assign-instructor', ['folder_id' => $folder->id]) }}">Assign Instructor</a>
                                    <form action="{{ route('admin.study-materials.folders.send-instructors', $folder->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Email all assigned instructors for this folder?');">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-default">Send instructors ({{ $folder->instructorAccess->count() }})</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isAdmin ? 11 : 10 }}" class="text-center">No folders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $folders->links() }}
        </div>
    </div>
</div>
@endsection
