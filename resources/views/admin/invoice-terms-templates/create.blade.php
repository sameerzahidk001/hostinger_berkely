@extends('admin.layout.app')
@section('title', 'Create Invoice T&C Template')
@push('style')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Create Invoice T&amp;C Template</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.invoice-terms-templates.index') }}">Invoice T&amp;C Templates</a></li>
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
            <form method="POST" action="{{ route('admin.invoice-terms-templates.store') }}">
                @csrf
                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. UK Bank Payment Instructions" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="is_active" class="form-control">
                        <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                        <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Terms &amp; Conditions body</label>
                    <textarea name="body" id="tnc_body" class="form-control">{{ old('body') }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
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
