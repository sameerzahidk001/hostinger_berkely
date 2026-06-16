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
        <h2>Header Settings</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li class="active">
                <strong>Header Settings</strong>
            </li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <form action="{{ route('header.setting.update') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label for="site_title">Enter</label>
                     <textarea class="form-control" id="header" name="header" rows="4" required>{{ $settings->header_settings ?? '' }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary mt-2">Update</button>
            </form>

        </div>
    </div>
</div>

@endsection

