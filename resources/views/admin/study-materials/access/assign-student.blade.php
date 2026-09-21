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
            @if(!empty($isInstructor))
                <div class="alert alert-info">
                    You only see courses assigned to you by admin, and students enrolled on that course.
                    Select a folder first to load the matching student list.
                </div>
            @endif
            <form method="POST" action="{{ route('admin.study-materials.access.assign-student.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Active folder *</label>
                        <select name="folder_id" id="folder_id" class="form-control js-type-find" data-placeholder="Type to find folder" required>
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
                        <select name="student_id" id="student_id" class="form-control js-type-find" data-placeholder="Type to find student" required>
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
@include('admin.partials.type-find-selects')
@push('script')
<script>
(function () {
    const folder = document.getElementById('folder_id');
    const student = document.getElementById('student_id');
    const issued = document.getElementById('issued_at');
    const till = document.getElementById('access_till');
    const studentsUrl = @json(url('/admin/study-materials/students-by-folder'));
    const selectedStudent = @json((string) old('student_id', ''));

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

    function loadStudents() {
        const folderId = folder.value;
        if (window.jQuery && $(student).hasClass('select2-hidden-accessible')) {
            $(student).select2('destroy');
        }
        student.innerHTML = '<option value="">Select student</option>';
        if (!folderId) {
            if (window.initTypeFindSelects) window.initTypeFindSelects(student.parentElement);
            return;
        }
        fetch(studentsUrl + '/' + folderId, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.ok ? res.json() : []; })
            .then(function (rows) {
                (rows || []).forEach(function (row) {
                    const opt = document.createElement('option');
                    opt.value = row.id;
                    opt.textContent = row.name + ' (' + row.email + ')';
                    if (String(row.id) === String(selectedStudent)) {
                        opt.selected = true;
                    }
                    student.appendChild(opt);
                });
                if (window.initTypeFindSelects) window.initTypeFindSelects(student.parentElement);
            })
            .catch(function () {
                if (window.initTypeFindSelects) window.initTypeFindSelects(student.parentElement);
            });
    }

    $(folder).on('change select2:select select2:clear', function () {
        recalc();
        loadStudents();
    });
    issued.addEventListener('change', recalc);
})();
</script>
@endpush
