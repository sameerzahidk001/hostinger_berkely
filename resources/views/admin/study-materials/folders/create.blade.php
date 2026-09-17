@extends('admin.layout.app')
@section('title', 'Create Study Folder')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Create Study Materials Folder</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.study-materials.folders.index') }}">Folders</a></li>
            <li class="active"><strong>Create</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.study-materials.folders.store') }}">
                @csrf
                <div class="alert alert-info">
                    <strong>Step 1:</strong> Create the main folder and any subfolders / sub-subfolders.
                    After saving you will add files by choosing a folder, or by linking a Zoho WorkDrive file.
                </div>
                @if(!$isAdmin && $courses->isEmpty())
                    <div class="alert alert-warning">
                        You are not assigned to any course yet. Ask admin to add you on the course instructors list before creating folders or granting student access.
                    </div>
                @endif
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Folder name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        <span class="help-block">Must be unique.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Folder code</label>
                        <input type="text" class="form-control" value="Auto (SM-0001…)" readonly disabled>
                        <span class="help-block">Assigned automatically after save. Not editable.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course *</label>
                        <select name="course_id" id="course_id" class="form-control" required @disabled(!$isAdmin && $courses->isEmpty())>
                            <option value="">Type to find the course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                        <span class="help-block">Type to search — many courses start with the same name.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Fee packages</label>
                        <select name="fee_package_ids[]" id="fee_package_ids" class="form-control" multiple>
                        </select>
                        <span class="help-block">Select one or more packages for this folder.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Validity (months)</label>
                        <select name="validity_months" class="form-control">
                            @php $oldValidity = old('validity_months', 6); @endphp
                            <option value="" @selected($oldValidity === '' || $oldValidity === null)>None</option>
                            @for($i = 1; $i <= 24; $i++)
                                <option value="{{ $i }}" @selected((string) $oldValidity === (string) $i)>{{ $i }} Month{{ $i > 1 ? 's' : '' }}</option>
                            @endfor
                        </select>
                        <span class="help-block">None means this folder has no expiry.</span>
                    </div>
                    @if($isAdmin)
                    <div class="col-md-6 form-group">
                        <label>Folder status</label>
                        <select name="status" class="form-control">
                            <option value="disabled" @selected(old('status', 'disabled') === 'disabled')>Disabled (default)</option>
                            <option value="active" @selected(old('status') === 'active')>Active</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Head of the Faculty</label>
                        <select name="head_of_faculty_id" class="form-control">
                            <option value="">—</option>
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(old('head_of_faculty_id') == $ins->id)>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Instructors</label>
                        <select name="instructor_ids[]" class="form-control" multiple size="6">
                            @foreach($instructors as $ins)
                                <option value="{{ $ins->id }}" @selected(collect(old('instructor_ids', []))->contains($ins->id))>{{ $ins->name }}</option>
                            @endforeach
                        </select>
                        <span class="help-block">Hold Ctrl/Cmd to select multiple. Access stays disabled until you Send from Access list.</span>
                    </div>
                    @else
                    <div class="col-md-12">
                        <div class="alert alert-info">You are creating as instructor — you will be set as owner/instructor. Admin can enable the folder.</div>
                    </div>
                    @endif
                    @include('admin.study-materials.folders._subfolders')
                </div>
                <button type="submit" class="btn btn-primary">Save Folder</button>
                <a href="{{ route('admin.study-materials.folders.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
@include('admin.study-materials.folders._form_assets', ['selectedPackageIds' => old('fee_package_ids', [])])
