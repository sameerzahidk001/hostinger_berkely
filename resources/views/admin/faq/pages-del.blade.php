@extends('admin.layout.app')
@section('title', 'FAQ')
@push('style')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<style>
    
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>FAQ's Pages List</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>FAQ's Pages</a>
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
                    <h5>Add Page to FAQ's List</h5>
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

                    <form role="form" action="{{ route('admin.store-faq-page') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <label for="">Page Name</label>
                                <input class="form-control" placeholder="Add Page Name" type="text" name="page_name" value="{{old('page_name')}}">
                            </div>
                            <div class="col-lg-6">
                                <label class="mb-1">Page URL</label>
                                <input class="form-control" placeholder="Add Page URL" type="text" name="url" value="{{old('url')}}">
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
                <h5>FAQ's Pages List</h5>
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
                    <th style="width: 40px;">SR#</th>
                    <th >Page</th>
                    <th>URL</th>
                    <th>FAQ's Count</th>
                    <th style="width: 110px;">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($pages as $index => $page)
                    
                    <tr>
                        <td style="vertical-align: middle;">{{ ++$index }}</td>
                        
                        <td style="vertical-align: middle;">
                            {{ $page->page_name }}
                        </td>
                        <td style="vertical-align: middle;">
                            {{ $page->url }}
                        </td>
                        <td style="vertical-align: middle;">
                            {{ $page->faqs->count() }}
                        </td>
                        <td style="vertical-align: middle;" class="center">
                            <div class="btn-group">
                                <button type="button" class="btn-primary btn btn-xs"><i class="fa fa-edit"></i> Edit</button>
                                <button type="button" class="btn-danger btn btn-xs "><i class="fa fa-trash"></i> Delete</button>
                            </div>
                        </td>
                    </tr>
                    
                    @empty
                    <tr>
                        <td class="text-align:center;" colspan="4">No Record Found!</td>
                    </tr>
                    
                @endforelse
                
                </tbody>
                <tfoot>
                <tr>
                    <th>SR#</th>
                    <th>Page</th>
                    <th>URL</th>
                    <th>FAQ's Count</th>
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
@endpush