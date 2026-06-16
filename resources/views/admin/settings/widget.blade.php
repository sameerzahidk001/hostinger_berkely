@extends('admin.layout.app')
@section('title', 'Subjects')
@push('style')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
<style>
    .page-heading{
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }
    .col{
        flex: 1;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Widget Settings</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li class="active">
                <strong>Widget Settings</strong>
            </li>
        </ol>
    </div>
</div>
@foreach ($widgets as $widget)
    <div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
        <div class="row">
            <div class="col-lg-12">
                
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>{{ Str::of($widget->type)->replace('-', ' ')->title() }}</h5>
                        <div class="ibox-tools">
                            <a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                        </div>
                    </div>

                    <div class="ibox-content">

                        <form role="form" action="{{ route('widget.store') }}" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="{{ $widget->id }}">
                            <div class="row">
                                <div class="col-md-6" style="margin-bottom: 15px;">
                                    <label>Title:</label>
                                    <input type="text" class="form-control" name="title" value="{{ old('title', $widget->title ?? '') }}" required>
                                    @error('title')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-6" style="margin-bottom: 15px;">
                                    <label>Select Menu:</label>
                                    <select name="menu" id="menu" class="form-control">
                                        <option value="">Select Menu</option>
                                        @foreach ($menus as $menu_group)
                                            <option value="{{ $menu_group }}" {{ old('menu', $widget->menu) == $menu_group ? 'selected' : '' }}>
                                                {{ $menu_group }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('menu')
                                        <p class="text-danger text-xs italic">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-md-12" style="margin-bottom: 15px;">
                                    <label for="description" class="form-label">Description:</label>
                                    <textarea class="form-control editor" id="description"
                                        name="description">{{ old('description', $widget->description ?? '') }}</textarea>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
                                    <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp; Save</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
@endforeach

@endsection

@push('script')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
<script>
    $(document).ready(function () {
        $(".editor").summernote({
            height: 200,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link']],
                ['view', ['codeview']]
            ]
        });

        this.classList.add("summernote-initialized");
    });
</script>
@endpush