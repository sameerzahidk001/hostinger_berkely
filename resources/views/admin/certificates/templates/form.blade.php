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
            <p class="text-muted">Per-student values filled at issue time: student name, student ID, course, duration, CPD hours, awarded date, certificate number, QR.</p>
            <form method="POST" action="{{ $mode === 'edit' ? route('admin.certificates.templates.update', $template->id) : route('admin.certificates.templates.store') }}" enctype="multipart/form-data">
                @csrf
                @if($mode === 'edit') @method('PUT') @endif
                <div class="row">
                    @if($mode === 'edit')
                    <div class="col-md-2 form-group">
                        <label>Template ID</label>
                        <input type="text" class="form-control" value="{{ $template->id }}" readonly>
                    </div>
                    @endif
                    <div class="col-md-{{ $mode === 'edit' ? '4' : '6' }} form-group">
                        <label>Template name (admin) *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required maxlength="255">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Orientation *</label>
                        <select name="orientation" class="form-control" required>
                            <option value="portrait" @selected(old('orientation', $template->orientation ?: 'portrait') === 'portrait')>Portrait (A4)</option>
                            <option value="landscape" @selected(old('orientation', $template->orientation) === 'landscape')>Landscape</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Status</label>
                        <select name="is_active" class="form-control">
                            <option value="1" @selected(old('is_active', $template->is_active ? '1' : '0') === '1')>Active</option>
                            <option value="0" @selected(old('is_active', $template->is_active ? '1' : '0') === '0')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Heading</label>
                        <input type="text" name="heading_line" class="form-control" value="{{ old('heading_line', $template->heading_line ?: $template->headingText()) }}" maxlength="255">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Body text (under student name)</label>
                        <textarea name="body_text" class="form-control" rows="2" maxlength="2000">{{ old('body_text', $template->body_text ?: $template->bodyText()) }}</textarea>
                    </div>
                    <div class="col-md-12 form-group">
                        <label>School name</label>
                        <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $template->school_name) }}" maxlength="255">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Default programme duration</label>
                        <input type="text" name="default_duration" class="form-control" value="{{ old('default_duration', $template->default_duration) }}" placeholder="e.g. 6 Months">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Default CPD hours</label>
                        <input type="text" name="default_cpd_hours" class="form-control" value="{{ old('default_cpd_hours', $template->default_cpd_hours) }}" placeholder="e.g. 30">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Primary colour</label>
                        <input type="color" name="primary_color" class="form-control" value="{{ old('primary_color', $template->primary_color ?: '#6b1d2a') }}">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Accent colour</label>
                        <input type="color" name="accent_color" class="form-control" value="{{ old('accent_color', $template->accent_color ?: '#c5a46a') }}">
                    </div>
                </div>

                <h4>Images</h4>
                <div class="row">
                    @php
                        $bgView = $template->imageViewUrl($template->resolvedBackgroundPath());
                        $logoView = $template->imageViewUrl($template->logo_path);
                        $sealView = $template->imageViewUrl($template->seal_path);
                        $leftView = $template->imageViewUrl($template->signer_left_signature);
                        $rightView = $template->imageViewUrl($template->signer_right_signature);
                    @endphp
                    <div class="col-md-4 form-group">
                        <label>Background</label>
                        <input type="file" name="background_image" class="form-control" accept="image/*">
                        <p class="help-block m-b-none">Leave empty to use the Berkeley background. PNG/JPG up to 2 MB.</p>
                        @if($bgView)
                            <p class="help-block m-b-none" style="margin-top:8px;">
                                <a href="{{ $bgView }}" target="_blank" rel="noopener">View</a>
                            </p>
                            <img src="{{ $bgView }}" alt="Background" style="max-width:100%;max-height:140px;border:1px solid #e5e7eb;margin-top:4px;">
                        @endif
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Logo (optional extra)</label>
                        <input type="file" name="logo_image" class="form-control" accept="image/*">
                        @if($logoView)
                            <p class="help-block m-b-none" style="margin-top:8px;">
                                <a href="{{ $logoView }}" target="_blank" rel="noopener">View</a>
                            </p>
                            <img src="{{ $logoView }}" alt="Logo" style="max-width:100%;max-height:80px;border:1px solid #e5e7eb;margin-top:4px;">
                        @endif
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Seal / badge (optional extra)</label>
                        <input type="file" name="seal_image" class="form-control" accept="image/*">
                        @if($sealView)
                            <p class="help-block m-b-none" style="margin-top:8px;">
                                <a href="{{ $sealView }}" target="_blank" rel="noopener">View</a>
                            </p>
                            <img src="{{ $sealView }}" alt="Seal" style="max-width:100%;max-height:80px;border:1px solid #e5e7eb;margin-top:4px;">
                        @endif
                    </div>
                </div>

                <h4>Signing authorities</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="well">
                            <strong>Left signer</strong>
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="signer_left_name" class="form-control" value="{{ old('signer_left_name', $template->signer_left_name) }}">
                            </div>
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="signer_left_title" class="form-control" value="{{ old('signer_left_title', $template->signer_left_title) }}">
                            </div>
                            <div class="form-group">
                                <label>E-signature image</label>
                                <input type="file" name="signer_left_image" class="form-control" accept="image/*">
                                @if($leftView)
                                    <p class="help-block m-b-none" style="margin-top:8px;">
                                        <a href="{{ $leftView }}" target="_blank" rel="noopener">View</a>
                                    </p>
                                    <img src="{{ $leftView }}" alt="Left signature" style="max-height:48px;max-width:100%;margin-top:4px;">
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="well">
                            <strong>Right signer</strong>
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" name="signer_right_name" class="form-control" value="{{ old('signer_right_name', $template->signer_right_name) }}">
                            </div>
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="signer_right_title" class="form-control" value="{{ old('signer_right_title', $template->signer_right_title) }}">
                            </div>
                            <div class="form-group">
                                <label>E-signature image</label>
                                <input type="file" name="signer_right_image" class="form-control" accept="image/*">
                                @if($rightView)
                                    <p class="help-block m-b-none" style="margin-top:8px;">
                                        <a href="{{ $rightView }}" target="_blank" rel="noopener">View</a>
                                    </p>
                                    <img src="{{ $rightView }}" alt="Right signature" style="max-height:48px;max-width:100%;margin-top:4px;">
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <h4>Verification footer</h4>
                <div class="row">
                    <div class="col-md-12 form-group">
                        <label>Verify heading</label>
                        <input type="text" name="verify_heading" class="form-control" value="{{ old('verify_heading', $template->verify_heading) }}">
                    </div>
                    <div class="col-md-12 form-group">
                        <label>Organisation line</label>
                        <input type="text" name="footer_org_line" class="form-control" value="{{ old('footer_org_line', $template->footer_org_line) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Registrar email</label>
                        <input type="text" name="footer_email" class="form-control" value="{{ old('footer_email', $template->footer_email) }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Website</label>
                        <input type="text" name="footer_website" class="form-control" value="{{ old('footer_website', $template->footer_website) }}">
                    </div>
                    <div class="col-md-12 form-group">
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
