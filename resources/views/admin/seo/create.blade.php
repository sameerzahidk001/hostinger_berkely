@extends('admin.layout.app')
@section('title', 'Add SEO')
@push('style')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.css">
    <style>
        .mb { margin-bottom: 1.5rem; }
        .tags-field .tagsinput,
        #keywords_tagsinput,
        #additional_keywords_tagsinput {
            width: 100% !important;
            height: auto !important;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-height: auto !important;
        }
    </style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>SEO</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a>SEO</a></li>
            <li class="active"><strong>Create</strong></li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>SEO</h5>
                </div>
                <div class="ibox-content">
                    <form role="form" action="{{ route('pages-seo.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            @if(request()->has('course_name'))
                                <div class="col-lg-12 mb">
                                    <label for="">Course Title</label>
                                    <input type="text" class="form-control" placeholder="{{ request()->get('course_name') }}" readonly>
                                    <input type="hidden" name="course_id" value="{{ request()->get('course_id') }}">
                                </div>
                            @elseif(request()->has('page_name'))
                                <div class="col-lg-12 mb">
                                    <label for="">Page Title</label>
                                    <input type="text" class="form-control" value="{{ request()->get('page_name') }}" readonly>
                                    <input type="hidden" name="page_id" value="{{ request()->get('page_id') }}">
                                </div>
                            @else
                                <div class="col-lg-12 mb">
                                    <label for="">Choose Page</label>
                                    <select name="page_id" class="form-control">
                                        @foreach($all_pages as $page)
                                            <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-lg-12 mb">
                                <label for="">Title <small>( Max {{ seo_field_limits()['title_max'] }} characters )</small></label>
                                <input type="text" class="form-control" name="title" placeholder="Add page title"
                                    value="{{ old('title') }}" data-maxlength="{{ seo_field_limits()['title_max'] }}" maxlength="{{ seo_field_limits()['title_max'] }}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="">Meta Description <small>( Max {{ seo_field_limits()['meta_description_max'] }} characters )</small></label>
                                <textarea class="form-control" name="meta_description" placeholder="Add meta description"
                                    data-maxlength="{{ seo_field_limits()['meta_description_max'] }}" maxlength="{{ seo_field_limits()['meta_description_max'] }}">{{ old('meta_description') }}</textarea>
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="focus_keyword">Focus Keyword <small>(max {{ seo_field_limits()['focus_keyword_max'] }} characters)</small></label>
                                <input type="text" class="form-control" name="focus_keyword" id="focus_keyword"
                                    placeholder="e.g. USMLE Preparation Programme"
                                    value="{{ old('focus_keyword') }}"
                                    maxlength="{{ seo_field_limits()['focus_keyword_max'] }}">
                                <p class="help-block text-muted" style="margin-top:4px;">Primary phrase for SEO analysis. Priority keywords are supporting terms.</p>
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="">Priority Keywords <small>( Max {{ seo_field_limits()['priority_keywords_max_tags'] }} keywords )</small></label>
                                <input class="form-control" name="keywords" id="keywords" value="{{ old('keywords') }}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label for="">Additional Keywords <small>( Max {{ seo_field_limits()['additional_keywords_max_tags'] }} keywords )</small></label>
                                <input class="form-control" name="additional_keywords" id="additional_keywords" value="{{ old('additional_keywords') }}">
                            </div>
                            <div class="col-lg-6 mb">
                                <label for="">Thumbnail <small>( 1200 x 627 px )</small></label>
                                <input type="file" class="form-control" name="thumbnail" accept="image/*">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;">
                                <button class="btn btn-primary" type="submit"><i class="fa fa-check"></i>&nbsp;Submit</button>
                                <button class="btn btn-warning" type="reset"><i class="fa fa-paste"></i> Reset</button>
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
    <script src="https://cdn.jsdelivr.net/npm/jquery-tags-input@1.3.5/dist/jquery.tagsinput.min.js"></script>
    @include('admin.seo.partials.tags-limits-script')
@endpush
