@extends('admin.layout.app')
@section('title', 'Edit Instructor Access')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Edit Instructor Access</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.study-materials.access.instructors') }}">Instructor Access</a></li>
            <li class="active"><strong>Edit</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.study-materials.access.instructor.update', $access->id) }}">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Folder *</label>
                        <select name="folder_id" class="form-control js-type-find" data-placeholder="Type to find folder" required>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}" @selected(old('folder_id', $access->folder_id) == $folder->id)>
                                    {{ $folder->displayName() }} — {{ $folder->course->title ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Instructor *</label>
                        <select name="instructor_id" class="form-control js-type-find" data-placeholder="Type to find instructor" required>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(old('instructor_id', $access->instructor_id) == $ins->id)>{{ $ins->name }} ({{ $ins->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Issued at</label>
                        <input type="date" name="issued_at" class="form-control" value="{{ old('issued_at', optional($access->issued_at)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Access till</label>
                        <input type="date" name="access_till" class="form-control" value="{{ old('access_till', optional($access->access_till)->format('Y-m-d')) }}">
                        <span class="help-block">Leave blank for no expiry.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Status *</label>
                        <select name="status" class="form-control" required>
                            <option value="disabled" @selected(old('status', $access->status) === 'disabled')>Disabled</option>
                            <option value="active" @selected(old('status', $access->status) === 'active')>Active</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('admin.study-materials.access.instructors') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
@include('admin.partials.type-find-selects')
