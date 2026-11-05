@extends('admin.layout.app')
@section('title', $mode === 'edit' ? 'Edit certificate template' : 'Add certificate template')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $mode === 'edit' ? 'Edit template' : 'Add template' }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.certificates.templates.index') }}">Templates</a></li>
            <li class="active"><strong>{{ $mode === 'edit' ? 'Edit' : 'Add' }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ $mode === 'edit' ? route('admin.certificates.templates.update', $template->id) : route('admin.certificates.templates.store') }}">
                @csrf
                @if($mode === 'edit') @method('PUT') @endif
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Template name (admin) *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required maxlength="255">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Orientation *</label>
                        <select name="orientation" class="form-control" required>
                            <option value="landscape" @selected(old('orientation', $template->orientation) === 'landscape')>Landscape</option>
                            <option value="portrait" @selected(old('orientation', $template->orientation) === 'portrait')>Portrait</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Status</label>
                        <select name="is_active" class="form-control">
                            <option value="1" @selected(old('is_active', $template->is_active ? '1' : '0') === '1')>Active</option>
                            <option value="0" @selected(old('is_active', $template->is_active ? '1' : '0') === '0')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Eyebrow (top small text)</label>
                        <input type="text" name="eyebrow" class="form-control" value="{{ old('eyebrow', $template->eyebrow) }}" maxlength="255">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Title on PDF</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $template->title) }}" maxlength="255">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>School name</label>
                        <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $template->school_name) }}" maxlength="255">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Line before student name</label>
                        <input type="text" name="intro_line" class="form-control" value="{{ old('intro_line', $template->intro_line) }}" maxlength="500">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Line before course name</label>
                        <input type="text" name="completion_line" class="form-control" value="{{ old('completion_line', $template->completion_line) }}" maxlength="500">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Primary colour</label>
                        <input type="color" name="primary_color" class="form-control" value="{{ old('primary_color', $template->primary_color ?: '#000435') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Accent colour</label>
                        <input type="color" name="accent_color" class="form-control" value="{{ old('accent_color', $template->accent_color ?: '#f8961f') }}">
                    </div>
                    <div class="col-md-6 form-group" style="padding-top:28px;">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $template->is_default))>
                            Set as default template
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.certificates.templates.index') }}" class="btn btn-white">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
