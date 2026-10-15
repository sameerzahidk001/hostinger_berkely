@extends('admin.layout.app')
@section('title', 'Edit Invoice T&C Template')
@push('style')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Edit {{ $template->displayCode() }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.payments.index') }}">Payments</a></li>
            <li><a href="{{ route('admin.invoice-terms-templates.index') }}">T&amp;C Templates</a></li>
            <li class="active"><strong>{{ $template->displayCode() }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.invoice-terms-templates.update', $template->id) }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>ID</label>
                    <input type="text" class="form-control" value="{{ $template->displayCode() }}" readonly>
                </div>
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" @selected((string) old('is_active', $template->is_active ? '1' : '0') === '1')>Active</option>
                        <option value="0" @selected((string) old('is_active', $template->is_active ? '1' : '0') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Terms &amp; Conditions body</label>
                    <textarea name="body" id="tnc_body" class="form-control">{{ old('body', $template->body) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.invoice-terms-templates.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
@push('script')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script>
$(function () {
    $('#tnc_body').summernote({
        height: 280,
        toolbar: [
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['para', ['ul', 'ol']],
            ['insert', ['link']],
            ['view', ['codeview']]
        ]
    });
});
</script>
@endpush
