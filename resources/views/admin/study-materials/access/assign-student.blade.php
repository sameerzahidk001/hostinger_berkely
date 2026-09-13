@extends('admin.layout.app')
@section('title', 'Assign Student')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Assign Folder to Student</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.study-materials.access.students') }}">Student Access</a></li>
            <li class="active"><strong>Assign Student</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <div class="alert alert-warning">Only <strong>Active</strong> folders. Access stays disabled until <strong>Send</strong>.</div>
            <form method="POST" action="{{ route('admin.study-materials.access.assign-student.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Active folder *</label>
                        <select name="folder_id" id="folder_id" class="form-control" required>
                            <option value="">Select folder</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder->id }}"
                                    data-months="{{ $folder->hasUnlimitedValidity() ? '' : $folder->validity_months }}"
                                    @selected(old('folder_id', $selectedFolder) == $folder->id)>
                                    {{ $folder->displayName() }} — {{ $folder->course->title ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Student *</label>
                        <select name="student_id" class="form-control" required>
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} ({{ $student->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Date of issuance</label>
                        <input type="date" name="issued_at" id="issued_at" class="form-control" value="{{ old('issued_at', now()->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Access till date</label>
                        <input type="date" name="access_till" id="access_till" class="form-control" value="{{ old('access_till', $defaultTill) }}">
                        <span class="help-block">Leave blank for no expiry (folder validity None).</span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save (Disabled)</button>
                <a href="{{ route('admin.study-materials.access.students') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
@push('script')
<script>
(function () {
    const folder = document.getElementById('folder_id');
    const issued = document.getElementById('issued_at');
    const till = document.getElementById('access_till');
    function recalc() {
        const opt = folder.options[folder.selectedIndex];
        const months = parseInt(opt?.dataset?.months || '0', 10);
        if (!months || !issued.value) {
            if (!months) till.value = '';
            return;
        }
        const d = new Date(issued.value + 'T00:00:00');
        d.setMonth(d.getMonth() + months);
        till.value = d.toISOString().slice(0, 10);
    }
    folder.addEventListener('change', recalc);
    issued.addEventListener('change', recalc);
})();
</script>
@endpush
