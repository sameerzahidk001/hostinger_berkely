@extends('admin.layout.app')
@section('title', 'Labels')
@push('style')
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Label List</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Labels</a>
            </li>
            <li class="active">
                <strong>List</strong>
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
                <h5>Labels</h5>
                <div class="ibox-tools">
                    <a class="btn-primary btn btn-xs" href="{{ route('course-labels.create') }}">
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
                <table class="table table-striped table-bordered table-hover dataTables-example" >
                <thead>
                <tr>
                    <th>id</th>
                    <th>Course</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach($labels as $index => $data)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $data->course->title }}</td>
                            <td>
                                <a href="{{ route('course-labels.edit', $data->id) }}" class="btn-primary btn btn-xs">
                                    <i class="fa fa-pencil"></i> Edit
                                </a>
                                <a href="{{ route('course-labels.destroy', $data->id) }}" class="btn btn-danger btn-xs" 
                                onclick="event.preventDefault(); document.getElementById('delete-form-{{ $data->id }}').submit();">
                                    <i class="fa fa-trash"></i> Remove
                                </a>

                                <form id="delete-form-{{ $data->id }}" action="{{ route('course-labels.destroy', $data->id) }}" method="POST" style="display: none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
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