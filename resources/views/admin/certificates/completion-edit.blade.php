@extends('admin.layout.app')
@section('title', 'Edit certificate details')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Edit certificate details</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.certificates.completions.index') }}">Completions</a></li>
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
            @php
                $student = $completion->user;
                $sid = $student ? $student->studentIdValue() : ($completion->student_number ?: '—');
            @endphp
            <p class="text-muted">Student ID is fixed. These details can be edited before and after the certificate is issued.</p>
            <div class="row" style="margin-bottom:12px;">
                <div class="col-md-4"><strong>Student:</strong> {{ $student->name ?? ('#'.$completion->user_id) }}</div>
                <div class="col-md-4"><strong>Student ID:</strong> {{ $sid }}</div>
                <div class="col-md-4"><strong>Course:</strong> {{ $completion->course->title ?? ('#'.$completion->course_id) }}</div>
            </div>
            <form method="POST" action="{{ route('admin.certificates.completions.update', $completion->id) }}">
                @csrf
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>Awarded / completion date</label>
                        <input type="date" name="completed_at" class="form-control" value="{{ old('completed_at', optional($completion->completed_at)->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-8 form-group">
                        <label>Template</label>
                        <select name="template_id" class="form-control">
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" @selected((string) old('template_id', $completion->template_id) === (string) $tpl->id)>{{ $tpl->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Course subtitle</label>
                        <input type="text" name="course_subtitle" class="form-control" value="{{ old('course_subtitle', $completion->course_subtitle) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Programme duration</label>
                        <input type="text" name="programme_duration" class="form-control" value="{{ old('programme_duration', $completion->programme_duration) }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>CPD hours</label>
                        <input type="text" name="cpd_hours" class="form-control" value="{{ old('cpd_hours', $completion->cpd_hours) }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Notes (internal)</label>
                        <input type="text" name="notes" class="form-control" value="{{ old('notes', $completion->notes) }}" maxlength="2000">
                    </div>
                    @if($completion->certificates->where('status', 'issued')->isNotEmpty())
                    <div class="col-md-12 form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="apply_to_certificate" value="1" @checked(old('apply_to_certificate', true))>
                            Apply these details to the issued certificate and update the PDF
                        </label>
                    </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.certificates.completions.index') }}" class="btn btn-white">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
