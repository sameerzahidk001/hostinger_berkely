@extends('admin.layout.app')
@section('title', 'Journal News & Events')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10"><h2>News &amp; events</h2></div>
    <div class="col-lg-2" style="text-align:right;padding-top:18px;">
        <a href="#add-news" class="btn btn-primary btn-sm">+ Add</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="ibox" id="add-news">
        <div class="ibox-title"><h5>+ Add news or event</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.journal.news.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-8 form-group">
                        <label>Title</label>
                        <input name="title" class="form-control" required value="{{ old('title') }}">
                    </div>
                    <div class="col-md-4 form-group">
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="news" @selected(old('type', 'news') === 'news')>News</option>
                            <option value="event" @selected(old('type') === 'event')>Event</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Body</label>
                    <textarea name="body" class="form-control text-editor" rows="8">{{ old('body') }}</textarea>
                </div>
                <label><input type="checkbox" name="is_published" value="1" {{ old('is_published') ? 'checked' : '' }}> Publish now (listed on site)</label>
                <div style="margin-top:10px;"><button class="btn btn-primary">Add</button></div>
            </form>
        </div>
    </div>

    @forelse($rows as $row)
        <div class="ibox">
            <div class="ibox-title">
                <h5>{{ $row->title }} <small>({{ $row->type }})</small></h5>
                @if($row->is_published)
                    <span class="label label-primary">Published</span>
                @else
                    <span class="label label-default">Draft</span>
                @endif
            </div>
            <div class="ibox-content">
                <form method="POST" action="{{ route('admin.journal.news.update', $row->id) }}">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Title</label>
                            <input name="title" class="form-control" required value="{{ $row->title }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Type</label>
                            <select name="type" class="form-control">
                                <option value="news" @selected($row->type === 'news')>News</option>
                                <option value="event" @selected($row->type === 'event')>Event</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Body</label>
                        <textarea name="body" class="form-control text-editor" rows="8">{{ $row->body }}</textarea>
                    </div>
                    <label><input type="checkbox" name="is_published" value="1" {{ $row->is_published ? 'checked' : '' }}> Publish now (listed on site)</label>
                    <div style="margin-top:10px;"><button class="btn btn-primary">Save</button></div>
                </form>
                <form method="POST" action="{{ route('admin.journal.news.destroy', $row->id) }}" style="margin-top:8px;" onsubmit="return confirm('Delete this item?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger btn-sm">Delete</button>
                </form>
            </div>
        </div>
    @empty
        <div class="alert alert-info">No news or events yet. Use + Add above.</div>
    @endforelse
</div>
@endsection
