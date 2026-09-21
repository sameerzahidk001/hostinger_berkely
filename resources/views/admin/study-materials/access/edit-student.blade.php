@extends('admin.layout.app')
@section('title', 'Edit Student Access')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Edit Student Access</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.study-materials.access.students') }}">Student Access</a></li>
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
            <form method="POST" action="{{ route('admin.study-materials.access.student.update', $access->id) }}">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Folder *</label>
                        <select name="folder_id" id="folder_id" class="form-control js-type-find" data-placeholder="Type to find folder" required>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}"
                                    data-months="{{ $folder->hasUnlimitedValidity() ? '' : $folder->validity_months }}"
                                    @selected(old('folder_id', $access->folder_id) == $folder->id)>
                                    {{ $folder->displayName() }} — {{ $folder->course->title ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Student *</label>
                        <select name="student_id" class="form-control js-type-find" data-placeholder="Type to find student" required>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id', $access->student_id) == $student->id)>{{ $student->name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Date of issuance</label>
                        <input type="date" name="issued_at" id="issued_at" class="form-control" value="{{ old('issued_at', optional($access->issued_at)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Access till date</label>
                        <input type="date" name="access_till" id="access_till" class="form-control" value="{{ old('access_till', optional($access->access_till)->format('Y-m-d')) }}">
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
                <a href="{{ route('admin.study-materials.access.students') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
@include('admin.partials.type-find-selects')
