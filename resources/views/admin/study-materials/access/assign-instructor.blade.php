@extends('admin.layout.app')
@section('title', 'Assign Instructor')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Assign Folder to Instructor</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.study-materials.access.instructors') }}">Instructor Access</a></li>
            <li class="active"><strong>Assign Instructor</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <div class="alert alert-warning">Access stays <strong>Disabled</strong> until you click <strong>Send</strong> (invoice-style).</div>
            <form method="POST" action="{{ route('admin.study-materials.access.assign-instructor.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Folder *</label>
                        <select name="folder_id" class="form-control js-type-find" data-placeholder="Type to find folder" required>
                            <option value="">Select folder</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}" @selected(old('folder_id', $selectedFolder) == $folder->id)>
                                    {{ $folder->displayName() }} — {{ $folder->course->title ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Instructors *</label>
                        <select name="instructor_ids[]" class="form-control js-type-find" data-placeholder="Type to find instructors" multiple required>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(collect(old('instructor_ids'))->contains($ins->id))>{{ $ins->name }} ({{ $ins->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Issued at</label>
                        <input type="date" name="issued_at" class="form-control" value="{{ old('issued_at', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Access till</label>
                        <input type="date" name="access_till" class="form-control" value="{{ old('access_till') }}">
                        <span class="help-block">Leave blank to auto-calculate from folder validity, or keep blank when validity is None.</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save (Disabled)</button>
                <a href="{{ route('admin.study-materials.access.instructors') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
@include('admin.partials.type-find-selects')
