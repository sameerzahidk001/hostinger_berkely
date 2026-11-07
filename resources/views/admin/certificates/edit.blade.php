@extends('admin.layout.app')
@section('title', 'Edit certificate')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Edit certificate</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.certificates.index') }}">Certificates</a></li>
            <li class="active"><strong>{{ $cert->certificate_code }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <p class="text-muted">Student ID is fixed on the user and cannot be changed here. Saving updates the listed details@if(! $cert->isRevoked()) and can regenerate the PDF@endif.</p>
            <form method="POST" action="{{ route('admin.certificates.update', $cert->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Certificate number</label>
                        <input type="text" class="form-control" value="{{ $cert->certificate_code }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Student ID</label>
                        <input type="text" class="form-control" value="{{ $cert->student_number ?: (optional($cert->user)->student_id ?? optional($cert->user)->student_number) }}" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Status</label>
                        <input type="text" class="form-control" value="{{ ucfirst($cert->status) }}" readonly>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Student name *</label>
                        <input type="text" name="student_name" class="form-control" value="{{ old('student_name', $cert->student_name) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course title *</label>
                        <input type="text" name="course_title" class="form-control" value="{{ old('course_title', $cert->course_title) }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course subtitle</label>
                        <input type="text" name="course_subtitle" class="form-control" value="{{ old('course_subtitle', $cert->course_subtitle) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Programme duration</label>
                        <input type="text" name="programme_duration" class="form-control" value="{{ old('programme_duration', $cert->programme_duration) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>CPD hours</label>
                        <input type="text" name="cpd_hours" class="form-control" value="{{ old('cpd_hours', $cert->cpd_hours) }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Awarded / completion date</label>
                        @php
                            $awarded = $cert->completion_date;
                            $awardedVal = is_object($awarded) ? $awarded->format('Y-m-d') : substr((string) $awarded, 0, 10);
                        @endphp
                        <input type="date" name="completion_date" class="form-control" value="{{ old('completion_date', $awardedVal) }}">
                    </div>
                    <div class="col-md-8 form-group">
                        <label>Template</label>
                        <select name="template_id" class="form-control js-search-select" data-placeholder="Type to find template">
                            <option value=""></option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" @selected((string) old('template_id', $cert->template_id) === (string) $tpl->id)>{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(! $cert->isRevoked())
                    <div class="col-md-12 form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="regenerate_pdf" value="1" @checked(old('regenerate_pdf', true))>
                            Update the PDF with these details
                        </label>
                    </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.certificates.index') }}" class="btn btn-white">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>.select2-container { width: 100% !important; }</style>
@endpush
@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('.js-search-select').select2({ placeholder: 'Type to find template', allowClear: true, width: '100%' });
});
</script>
@endpush
