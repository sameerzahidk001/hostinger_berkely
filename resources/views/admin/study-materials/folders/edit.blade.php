@extends('admin.layout.app')
@section('title', 'Edit Study Folder')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-7">
        <h2>Edit Folder: {{ $folder->name }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.study-materials.folders.index') }}">Folders</a></li>
            <li class="active"><strong>Edit</strong></li>
        </ol>
    </div>
    <div class="col-lg-5 text-right" style="padding-top:20px;">
        <form action="{{ route('admin.study-materials.folders.send-students', $folder->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Email all assigned students for this folder? This also activates their access.');">
            @csrf
            <button type="submit" class="btn btn-primary">Send to students ({{ $folder->studentAccess->count() }})</button>
        </form>
        @if($isAdmin)
        <form action="{{ route('admin.study-materials.folders.send-instructors', $folder->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Email all assigned instructors for this folder? This also activates their access.');">
            @csrf
            <button type="submit" class="btn btn-default">Send to instructors ({{ $folder->instructorAccess->count() }})</button>
        </form>
        @endif
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="ibox">
        <div class="ibox-title"><h5>1. Folder details</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.study-materials.folders.update', $folder->id) }}">
                @csrf @method('PUT')
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Folder name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $folder->name) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Folder code</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code', $folder->code) }}" maxlength="40">
                        <span class="help-block">Unique code shown on the folders list. Created {{ $folder->created_at?->timezone(config('app.timezone'))->format('d M Y') }}.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course *</label>
                        <select name="course_id" id="course_id" class="form-control" required>
                            <option value="">Type to find the course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected(old('course_id', $folder->course_id) == $course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                        <span class="help-block">Type to search — many courses start with the same name.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Fee packages</label>
                        <select name="fee_package_ids[]" id="fee_package_ids" class="form-control" multiple>
                            @foreach($packages as $package)
                                <option value="{{ $package->id }}" @selected(collect($selectedPackageIds ?? [])->contains($package->id))>
                                    {{ $package->package_name }}{{ $package->price ? ' — ' . ($package->currency ?? '') . ' ' . $package->price : '' }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">Select one or more packages for this folder.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Validity (months)</label>
                        <select name="validity_months" class="form-control">
                            @php $oldValidity = old('validity_months', $folder->validity_months); @endphp
                            <option value="" @selected($oldValidity === '' || $oldValidity === null || (int) $oldValidity === 0)>None</option>
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
                            <option value="disabled" @selected($folder->status === 'disabled')>Disabled</option>
                            <option value="active" @selected($folder->status === 'active')>Active</option>
                        </select>
                        <span class="help-block">Disabled folders cannot be opened by students.</span>
                    </div>
                    @else
                    <div class="col-md-6 form-group">
                        <label>Folder status</label>
                        @if($folder->status === 'active')
                            <select name="status" class="form-control">
                                <option value="active" selected>Active</option>
                                <option value="disabled">Disabled</option>
                            </select>
                            <span class="help-block">Disable to lock this folder on student screens. An admin must re-enable it.</span>
                        @else
                            <input class="form-control" value="Disabled" disabled>
                            <span class="help-block">Students cannot open this folder. Ask an admin to re-enable it.</span>
                        @endif
                    </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">Save details</button>
            </form>
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>2. Add subfolder / sub-subfolder</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.study-materials.folders.subfolders.store', $folder->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-5 form-group">
                        <label>Put inside</label>
                        <select name="parent_id" class="form-control">
                            @foreach($folderOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 form-group">
                        <label>New folder name *</label>
                        <input type="text" name="subfolder_name" class="form-control" value="{{ old('subfolder_name') }}" placeholder="Subfolder name" required>
                    </div>
                    <div class="col-md-2 form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-default btn-block">Add folder</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>3. Add files</h5></div>
        <div class="ibox-content">
            <p class="help-block">Select the main folder or a subfolder, then upload to Zoho WorkDrive, upload to this server, or paste an existing WorkDrive link.</p>
            <form method="POST" action="{{ route('admin.study-materials.folders.files.store', $folder->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Put file in *</label>
                        <select name="parent_id" class="form-control">
                            @foreach($folderOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>File source *</label>
                        <div>
                            <label class="radio-inline">
                                <input type="radio" name="source" value="workdrive" {{ old('source', ($zohoWorkDriveReady ?? false) ? 'workdrive' : 'upload') === 'workdrive' ? 'checked' : '' }}> Upload to Zoho WorkDrive
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="source" value="upload" {{ old('source', ($zohoWorkDriveReady ?? false) ? 'workdrive' : 'upload') === 'upload' ? 'checked' : '' }}> Upload to server
                            </label>
                            <label class="radio-inline">
                                <input type="radio" name="source" value="zoho" {{ old('source') === 'zoho' ? 'checked' : '' }}> Paste WorkDrive link
                            </label>
                        </div>
                        @unless($zohoWorkDriveReady ?? false)
                            <span class="help-block">WorkDrive API is not connected yet. Paste a link, or upload to the server until OAuth is set.</span>
                        @endunless
                    </div>
                    <div class="col-md-12 form-group" id="source-upload">
                        <label>Upload files</label>
                        <input type="file" name="files[]" class="form-control" multiple>
                        <span class="help-block" id="source-upload-help">PDF, Word, Excel, PPT, audio, video, any file. Maximum 100 MB per file.</span>
                    </div>
                    <div class="col-md-6 form-group" id="source-zoho-name" style="display:none;">
                        <label>File name *</label>
                        <input type="text" name="zoho_name" class="form-control" value="{{ old('zoho_name') }}" placeholder="e.g. CMA Case Study PDF">
                    </div>
                    <div class="col-md-6 form-group" id="source-zoho-url" style="display:none;">
                        <label>Zoho WorkDrive URL *</label>
                        <input type="url" name="zoho_url" class="form-control" value="{{ old('zoho_url') }}" placeholder="https://workdrive.zoho.com/...">
                        <span class="help-block">Students will open this file from Zoho WorkDrive — it is not stored on the hosting server.</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Add file</button>
            </form>
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Current structure</h5></div>
        <div class="ibox-content">
            <p class="help-block">Use <strong>Download: Yes/No</strong> on each file. If Yes, students see a Download button. If No, they can only open it in the portal.</p>
            @include('admin.study-materials.folders._tree', ['items' => $folder->rootItems])
        </div>
    </div>
</div>
@endsection
@include('admin.study-materials.folders._form_assets', ['selectedPackageIds' => $selectedPackageIds ?? []])
@push('script')
<script>
(function () {
    function toggleSource() {
        const source = document.querySelector('input[name="source"]:checked');
        const isZoho = source && source.value === 'zoho';
        const isWorkdrive = source && source.value === 'workdrive';
        document.getElementById('source-upload').style.display = isZoho ? 'none' : '';
        document.getElementById('source-zoho-name').style.display = isZoho ? '' : 'none';
        document.getElementById('source-zoho-url').style.display = isZoho ? '' : 'none';
        const help = document.getElementById('source-upload-help');
        if (help) {
            help.textContent = isWorkdrive
                ? 'Files go to Zoho WorkDrive (max 100 MB). Students open them from WorkDrive, not this server.'
                : 'PDF, Word, Excel, PPT, audio, video, any file. Maximum 100 MB. Stored on this server.';
        }
    }
    document.querySelectorAll('input[name="source"]').forEach(function (el) {
        el.addEventListener('change', toggleSource);
    });
    toggleSource();
})();
</script>
@endpush
