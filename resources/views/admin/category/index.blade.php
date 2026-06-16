@extends('admin.layout.app')
@section('title', 'Subjects')
@push('style')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">

@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Category</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Categories</a>
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
                    <h5>Add Category</h5>
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

                    <form role="form" action="{{ route('category.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-lg-12">
                                <label for="">Name</label>
                                <input class="form-control" placeholder="Add Category Name" type="text" name="name" value="{{old('name')}}">
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
                <h5>Categories</h5>
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
                    <th>id</th>
                    <th>Name</th>
                    <th>Description</th> 
                    <th style="width:130px;">Status</th>
                    @include('admin.layout.partials.audit-columns-head')
                    <th style="width:130px;">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($categories as $index => $data)
                <tr>
                    
                    <td >
                        {{ $loop->iteration }}
                    </td>
                    <td>
                        <p> {{ $data->name }} ({{ $data->courses_count }})
                        </p>
                        
                       
                    </td>
                    <td>
                        <p title="{{ $data->description }}">
                            {{ \Illuminate\Support\Str::limit($data->description, 50, '...') }}
                        </p>
                        
                    </td>
                    <td>
                        <select name="category_status" class="form-control" id="category_status_{{ $data->id }}" onchange="updateCategoryStatus(this.value, {{ $data->id }})">
                            <option value="active" @if(is_null($data->deleted_at)) selected @endif>Active</option>
                            <option value="disable" @if(!is_null($data->deleted_at)) selected @endif>Disable</option>
                        </select>
                    </td>
                    @include('admin.layout.partials.audit-columns-cells', ['model' => $data])
                    <td >
                        <div class="btn-group">
                            <a href="{{ route('category.edit', $data->id) }}" class="btn-primary btn btn-xs">
                                <i class="fa fa-pencil"></i> Edit
                            </a>

                            @include('admin.layout.partials.delete-button', [
                                'id' => $data->id,
                                'action' => route('category.destroy', $data->id),
                            ])
                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td class="text-align:center;" colspan="9">No Record Found!</td>
                </tr>
                @endforelse
                
                </tbody>
                <tfoot>
                <tr>
                    <th>id</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
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

<script>
function updateCategoryStatus(status, categoryId) {
    // AJAX call to update course status
    //alert(categoryId);
    $.ajax({
        url: 'category/' + categoryId + '/update-status-category',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}', // For Laravel CSRF protection
            status: status
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.success);  // Show success notification
            } else {
                toastr.error('Unexpected response received.');
            }
        },
        error: function(xhr, status, error) {
            alert('Error updating course status.');
        }
    });
}
</script>

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