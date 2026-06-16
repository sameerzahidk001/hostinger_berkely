@extends('admin.layout.app')
@section('title', 'FAQ')
@push('style')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<style>
    .mb{
        margin-bottom: 1.5rem;
    }
 </style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>FAQ's List</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>FAQ's</a>
            </li>
            <li class="active">
                <strong>All</strong>
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
                    <h5>Add FAQ</h5>
                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-down"></i>
                        </a>
                        
                        <a class="close-link">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content" style="display:none;">
                    <form role="form" action="{{ route('faq.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-lg-12 mb">
                                <label for="">Choose Page</label>
                                <select name="page_id" id="" class="form-control">
                                    @foreach($all_pages as $index => $page)
                                        <option value="{{ $page->id }}">{{ $page->page_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row" >
                            <div class="col-lg-12 mb">
                                <label for="">Title/Question</label>
                                <textarea class="form-control text-editor" placeholder="Add FAQ Title"  name="question"></textarea>
                            </div>
                            <div class="col-lg-12 mb">
                                <label class="mb-1">Description/Answer</label>
                                <textarea class="form-control text-editor" placeholder="Add FAQ Description" name="answer"></textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
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

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
        <div class="ibox float-e-margins" style="margin-bottom: 0;">
            <div class="ibox-title">
                <h5>FAQ's List</h5>
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

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover dataTables-example" >
                <thead>
                <tr>
                    <!-- <th style="width: 40px;">id</th> -->
                    <th>Page</th>
                    <th>FAQ</th>
                    @include('admin.layout.partials.audit-columns-head')
                    <th style="width:130px;">Action</th>
                </tr>
                </thead>
                <tbody>
                
                    @foreach($faqs as $key => $faq)
                    <tr>
                       
                        <td>
                            <a href="{{ url($faq->pages->url) }}" target="_blank">
                            {{ $faq->pages->page_name ?? NULL }}
                            </a>
                        </td>
                        <td style="vertical-align: middle;">
                            {!! $faq->question !!}
                            
                            {!! $faq->answer !!}
                        </td>
                        @include('admin.layout.partials.audit-columns-cells', ['model' => $faq])
                        <td style="vertical-align: middle;" class="center">
                            <div class="btn-group">
                                <a href="{{ route('faq.edit', $faq->id) }}" class="btn-primary btn btn-xs">
                                    <i class="fa fa-pencil"></i> Edit
                                </a>

                                @include('admin.layout.partials.delete-button', [
                                    'id' => $faq->id,
                                    'action' => route('faq.destroy', $faq->id),
                                ])
                            </div>
                        </td>
                    </tr>
                    @endforeach

                </tbody>
                <tfoot>
                    <tr>
                        
                        <th>FAQ</th>
                        <th>Action</th>
                    </tr>
                </tfoot>
                </table>
            </div>

            </div>
        </div>
    </div>
    </div>
</div>
@endsection

@push('script')
<script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
<!-- Page-Level Scripts -->
<script>
    $(document).ready(function(){
        $('.dataTables-example').DataTable({
            pageLength: 10,
            searching: true,
            lengthChange: true,
            paging: true,
            info: false,
            ordering: true,
            responsive: true,
            dom: 'lftip'
        });
    });
</script>
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