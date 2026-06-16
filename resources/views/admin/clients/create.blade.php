@extends('admin.layout.app')
@section('title', 'Create Client')
@push('style')
    <style>
        .page-heading {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }

        .col {
            flex: 1;
        }

        .instruction {
            font-size: 14px;
            color: #555;
            margin-top: 5px;
        }
    </style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Clients</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li>
                <a href="{{ route('admin.clients.index') }}">Clients</a>
            </li>
            <li class="active">
                <strong>Create</strong>
            </li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Create</h5>
                </div>

                <div class="ibox-content">

                    <form role="form" action="{{ route('admin.clients.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text" name="title" id="title" class="form-control" value="{{ old('title') }}" required>
                                </div>
                                @error('title')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <div class="form-group">
                                    <label for="image">Image</label>
                                    <!-- Accept only one image -->
                                    <input type="file" name="image" id="image" class="form-control" required>
                                </div>
                                @error('image')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-4" style="margin-bottom: 15px;">
                                <div class="form-group">
                                    <label for="url">URL</label>
                                    <input type="url" name="url" id="url" class="form-control" value="{{ old('url') }}" required>
                                    <div class="" style="margin-top: 4px;">
                                        <label for="open_new_tab">
                                            <input type="checkbox" name="open_new_tab" id="open_new_tab" value="1" {{ old('open_new_tab') == '1' ? 'checked' : '' }} style="margin: 0;"> Open in new tab
                                        </label>
                                        <label for="no_follow">
                                            <input type="checkbox" name="no_follow" id="no_follow" value="1" {{ old('open_new_tab') == '1' ? 'checked' : '' }} style="margin: 0;"> NoFollow
                                        </label>
                                    </div>
                                </div>
                                @error('url')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-lg-12" style="margin-bottom: 15px;">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" maxlength="255" placeholder="Write something here..."
                                    name="body">{!! old('description') !!}</textarea>
                                @error('description')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;">
                                <button class="btn btn-primary " type="submit"><i
                                        class="fa fa-check"></i>&nbsp;Submit</button>
                                <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary"><i
                                        class="fa fa-arrow-left"></i>&nbsp;Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')

@endpush