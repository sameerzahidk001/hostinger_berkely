@extends('admin.layout.app')
@section('title', 'Add SEO')
@push('style')
 <style>
    .mb{
        margin-bottom: 1.5rem;
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
            <li>
                <a>SEO</a>
            </li>
            <li class="active">
                <strong>Create</strong>
            </li>
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>SEO</h5>
                    <div class="ibox-tools">
                        <!-- <a data-toggle="modal" href="#AddSyllabusModal" data-item-id="">
                            <i class="fa fa-chevron-circle-right"></i>
                        </a> -->
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>

                        <a class="close-link">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">
                    <form role="form" action="{{ route('pages-seo.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            
                            @if(request()->has('course_name'))
                               
                                <div class="col-lg-12 mb">
                                    <label for="">Course Title</label>
                                    <input type="text" class="form-control" placeholder="{{ request()->get('course_name') }}"  readonly>
                                    <input type="hidden" name="course_id" value="{{ request()->get('course_id') }}">
                                </div>
                            @else
                                <div class="col-lg-12 mb">
                                    <label for="">Choose Page</label>
                                    <select name="page_id" id="" class="form-control">
                                        @foreach($all_pages as $index => $page)
                                            <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-lg-6 mb">
                                <label for="">Title <small>( Less or 60 characters )</small></label>
                                <input type="text" class="form-control" name="title" placeholder="add page title" value="{{ old('page_title') }}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Thumbnail <small>( 1200 627 px )</small></label>
                                <input type="file" class="form-control" name="thumbnail" value="{{ old('thumbnail') }}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="">Meta Description <small>( Max 160 characters )</small></label>
                                <textarea class="form-control" name="meta_description" placeholder="Add meta description">{{ old('meta_description') }}</textarea>
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="">Keywords</label>
                                <textarea class="form-control" name="keywords" placeholder="Add keywords with comma separated">{{ old('keywords') }}</textarea>
                            </div>
                            
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Submit</button>
                                <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
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