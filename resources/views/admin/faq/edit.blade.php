@extends('admin.layout.app')
@section('title', 'FAQ')
@push('style')

@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>FAQ</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>FAQ's</a>
            </li>
            
        </ol>
    </div>
    <div class="col-lg-2">

    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Edit FAQ</h5>
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
                    <form role="form" action="{{ route('faq.update', $faq->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-lg-12">
                                <label for="">Choose Page</label>
                                <select name="page_id" id="" class="form-control">
                                    @foreach($all_pages as $index => $page)
                                        <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 8px;">
                            <div class="col-lg-12">
                                <label for="">Title/Question</label>
                                <textarea class="form-control text-editor" placeholder="Add FAQ Title"  name="question" >{!! old('question',  $faq->question) !!}</textarea>
                            </div>
                            <div class="col-lg-12">
                                <label class="mb-1">Description/Answer</label>
                                <textarea class="form-control text-editor" placeholder="Add FAQ Description" name="answer" > {!! old('answer',  $faq->answer) !!}</textarea>
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
