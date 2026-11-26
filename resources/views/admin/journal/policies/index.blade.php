@extends('admin.layout.app')
@section('title', 'Journal Policies')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-12"><h2>Journal policies</h2></div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @foreach($rows as $row)
        <div class="ibox">
            <div class="ibox-title"><h5>{{ $row->title }} <small>({{ $row->key }})</small></h5></div>
            <div class="ibox-content">
                <form method="POST" action="{{ route('admin.journal.policies.update', $row->id) }}">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label>Title</label>
                        <input name="title" class="form-control" value="{{ $row->title }}" required>
                    </div>
                    <div class="form-group">
                        <label>Body</label>
                        <textarea name="body" class="form-control" rows="6">{{ $row->body }}</textarea>
                    </div>
                    <label><input type="checkbox" name="is_published" value="1" {{ $row->is_published ? 'checked' : '' }}> Published on site</label>
                    <div style="margin-top:10px;"><button class="btn btn-primary">Save</button></div>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
