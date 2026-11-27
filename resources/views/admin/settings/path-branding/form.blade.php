@extends('admin.layout.app')

@section('title', $row->exists ? 'Edit Path Branding' : 'Add Path Branding')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $row->exists ? 'Edit Path Branding' : 'Add Path Branding' }}</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.path-branding.index') }}">Path Branding</a></li>
            <li class="active"><strong>{{ $row->exists ? 'Edit' : 'Add' }}</strong></li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if(session('fail'))
        <div class="alert alert-danger">{{ session('fail') }}</div>
    @endif

    <div class="ibox">
        <div class="ibox-content">
            <form method="POST"
                  action="{{ $row->exists ? route('admin.path-branding.update', $row->id) : route('admin.path-branding.store') }}"
                  enctype="multipart/form-data">
                @csrf
                @if($row->exists)
                    @method('PUT')
                @endif

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $row->name) }}"
                               placeholder="Academic Journal" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Path prefix</label>
                        <input type="text" name="path_prefix" class="form-control"
                               value="{{ old('path_prefix', $row->path_prefix) }}"
                               placeholder="journal" required>
                        <span class="help-block">
                            Applies to <code>/journal</code> and every page under
                            <code>/journal/...</code>
                        </span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Menu group</label>
                        <select name="menu_group" class="form-control">
                            <option value="">— Use main site menu —</option>
                            @foreach($menuGroups as $group)
                                <option value="{{ $group }}" {{ old('menu_group', $row->menu_group) === $group ? 'selected' : '' }}>
                                    {{ $group }}
                                </option>
                            @endforeach
                        </select>
                        <span class="help-block">Create the journal menu under Settings → Menu, then select it here.</span>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Home URL for logo click</label>
                        <input type="text" name="home_url" class="form-control"
                               value="{{ old('home_url', $row->home_url) }}"
                               placeholder="/journal">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Logo image</label>
                        <input type="file" name="logo_file" class="form-control" accept="image/*">
                        @if($row->logoUrl())
                            <div style="margin-top:10px;">
                                <img src="{{ $row->logoUrl() }}" alt="Current logo" style="max-height:60px;max-width:220px;object-fit:contain;">
                                <div><a href="{{ $row->logoUrl() }}" target="_blank" rel="noopener">View current logo</a></div>
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6 form-group">
                        <label style="display:block;">Status</label>
                        <label class="checkbox-inline" style="padding-left:0;">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $row->is_active ? 1 : 0) ? 'checked' : '' }}>
                            Active
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-top:12px;">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('admin.path-branding.index') }}" class="btn btn-white">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
