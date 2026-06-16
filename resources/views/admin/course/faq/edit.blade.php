@extends('admin.layout.app')
@section('title', 'Update FAQs')
@push('style')
<style>
    .ibox-title-cust{
        background-color: #ffffff;
        color: inherit;
        margin-bottom: 0;
        min-height: 48px;
        display: flex;
        justify-content: space-between;
    }

    form > .row > [class^="col-"] {
        margin-bottom: 16px;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Course</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Courses</a>
            </li>
            {{--<li>
                <a>{{ $course->title }}</a>
            </li>--}}
            <li>
                <a>FAQs</a>
            </li>
            
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<div class="row">
    <div class="wrapper wrapper-content">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0px;">
                <div class="ibox-title">
                    <h5>Update FAQ</h5>
                    <div class="ibox-tools">
                        
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>

                        <a class="close-link">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                
                <div class="ibox-content">
                    <form action="{{ route('course.update-faq', ['course_id' => $course->id, 'faq_id' => $course_faq->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <div class="col-lg-12">
                                <label class="mb-1">Question</label>
                                <textarea name="title" id="" rows="1" class="form-control" class placeholder="Add FAQ Question">{{ old('title', $course_faq->title) }}</textarea>
                            </div>
                            <div class="col-lg-12">
                                <label class="mb-1">Answer</label>
                                <textarea name="short_description" id="" rows="2" class="form-control text-editor" placeholder="Add FAQ Answer" value="{{ old('short_description') }}">{{ $course_faq->short_description }}</textarea>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Update</button>
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

<!-- <script src="https://cdn.ckeditor.com/ckeditor5/23.0.0/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('.text-editor').forEach((editorElement) => {
            ClassicEditor.create(editorElement)
                .catch(error => {
                    console.error(error);
                });
        });
    });
</script> -->


@endpush