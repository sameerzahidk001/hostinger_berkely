@extends('admin.layout.app')
@section('title', 'Journal Policies')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10"><h2>Journal policies</h2></div>
    <div class="col-lg-2" style="text-align:right;padding-top:18px;">
        <a href="#add-policy" class="btn btn-primary btn-sm">+ Add</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="ibox" id="add-policy">
        <div class="ibox-title"><h5>+ Add policy</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.journal.policies.store') }}">
                @csrf
                <div class="form-group">
                    <label>Title</label>
                    <input name="title" class="form-control" value="{{ old('title') }}" required placeholder="e.g. Open access policy">
                </div>
                <div class="form-group">
                    <label>Body</label>
                    <textarea name="body" class="form-control text-editor" rows="8">{{ old('body') }}</textarea>
                </div>
                <label><input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}> Published on site</label>
                <div style="margin-top:10px;"><button class="btn btn-primary">Add policy</button></div>
            </form>
        </div>
    </div>

    @foreach($rows as $row)
        <div class="ibox">
            <div class="ibox-title">
                <h5>{{ $row->title }}</h5>
                @if($row->is_published)
                    <span class="label label-primary">Published</span>
                @else
                    <span class="label label-default">Draft</span>
                @endif
            </div>
            <div class="ibox-content">
                <form method="POST" action="{{ route('admin.journal.policies.update', $row->id) }}">
                    @csrf @method('PUT')
                    <div class="form-group">
                        <label>Title</label>
                        <input name="title" class="form-control" value="{{ $row->title }}" required>
                    </div>
                    <div class="form-group">
                        <label>Body</label>
                        <textarea name="body" class="form-control text-editor" rows="8">{{ $row->body }}</textarea>
                    </div>
                    <label><input type="checkbox" name="is_published" value="1" {{ $row->is_published ? 'checked' : '' }}> Published on site</label>
                    <div style="margin-top:10px;display:flex;gap:8px;align-items:center;">
                        <button class="btn btn-primary">Save</button>
                    </div>
                </form>
                <form method="POST" action="{{ route('admin.journal.policies.destroy', $row->id) }}" style="margin-top:8px;" onsubmit="return confirm('Delete this policy?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
