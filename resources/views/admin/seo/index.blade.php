@extends('admin.layout.app')
@section('title', 'Pages SEO')
@push('style')
<style>
    td > p {
        margin: 0;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>SEO</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            
            <li class="active">
                <strong>SEO</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
        <div class="ibox float-e-margins" style="margin-bottom: 0;">
            <div class="ibox-title">
                <h5>SEO</h5>
                <div class="ibox-tools">
                    <a class="btn-primary btn btn-xs" href="{{ route('pages-seo.create') }}">
                        <i class="fa fa-plus-square"></i> &nbsp;Add
                    </a>
                    <a class="collapse-link">
                        <i class="fa fa-chevron-up"></i>
                    </a>
                    
                    <a class="close-link">
                        <i class="fa fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="ibox-content">

            <div class="table-responsive">
                <form action="{{ route('pages-seo.index') }}" method="GET">
                    
                    <div class="row" style="margin-bottom: 6px;">
                        

                        <!-- Category Dropdown -->
                        <div class="col-lg-4">
                            <select name="type" class="form-control">
                                <option value="">Select Type</option>
                                <option value="page" {{ request('type') == 'page' ? 'selected' : '' }}>Page</option>
                                <option value="course" {{ request('type') == 'course' ? 'selected' : '' }}>Course</option>
                            </select>
                        </div>
                        <!-- Course Name Input -->
                        <div class="col-lg-6">
                            <input type="text" name="name" class="form-control" placeholder="Search page/title"
                                value="{{ request('name') }}"> <!-- Retain course name -->
                        </div>

                        <div class="col-lg-2">
                            <button type="submit" class="btn-primary btn btn-md" style="width:100%;">Filter</button>
                        </div>

                    </div>
                    @csrf
                </form>
                <table class="table table-striped table-bordered table-hover dataTables-example" >
                <thead>
                <tr>
                    <th>id</th>
                    <th style="width:160px;">Page</th>
                    
                    <th>Thumbnail</th>
                    <th>Keyowrds</th>
                    <th>Meta Description</th>
                    @include('admin.layout.partials.audit-columns-head')
                    <th style="width:130px;">Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach($pages_seo as $key => $page_seo)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <a href="{{ url($page_seo->page->url ?? '') }}" target="_blank">
                                {{ $page_seo->page->page_name ?? NULL }}
                            </a>
                            <p>Title: {{ $page_seo->page->url ?? NULL }}</p>
                            <p>URL: {{ $page_seo->page->url ?? NULL }}</p>
                        </td>
                        <td>
                            @if($page_seo->thumbnail)
                                <a href="{{ asset($page_seo->thumbnail) }}" target="_blank">
                                    <img src="{{ asset($page_seo->thumbnail) }}" alt="" style="width:80px; height:40px;">
                                </a>
                            @endif
                        </td>
                        <td>{{ $page_seo->keywords ?? '' }}</td>
                        <td>{{ $page_seo->meta_description ?? '' }}</td>
                        @include('admin.layout.partials.audit-columns-cells', ['model' => $page_seo])
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('pages-seo.edit', $page_seo->id) }}" class="btn-primary btn btn-xs">
                                    <i class="fa fa-pencil"></i> Edit
                                </a>

                                @include('admin.layout.partials.delete-button', [
                                    'id' => $page_seo->id,
                                    'action' => route('pages-seo.destroy', $page_seo->id),
                                ])
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                
                </table>
            </div>

            </div>
        </div>
    </div>
    </div>
</div>




@endsection

@push('script')

@endpush