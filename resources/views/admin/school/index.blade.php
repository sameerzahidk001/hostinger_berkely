@extends('admin.layout.app')
@section('title', 'Subjects')
@push('style')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<!-- <style>
    .custom-dropdown-menu {
        right: 0; 
        left: auto; 
        max-width: 250px; 
        white-space: nowrap; 
        overflow-x: auto; 
    }
</style> -->
<style>
    td>p {
        margin: 0;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Schools</h2>
        <ol class="breadcrumb">
            <li>
                <a href="index.html">Home</a>
            </li>
            <li>
                <a>Schools</a>
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
                    <h5>Add School</h5>
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

                    <form role="form" action="{{ route('school.store') }}" enctype="multipart/form-data" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-lg-4">
                                <label for="name">Name</label>
                                <input class="form-control @error('name') is-invalid @enderror" placeholder="Add School Name" type="text" name="name" value="{{ old('name') }}">
                                @error('name')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4">
                                <label for="icon">Icon</label>
                                <input class="form-control @error('icon') is-invalid @enderror" type="file" name="icon">
                                @error('icon')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-4">
                                <label for="image">Image</label>
                                <input class="form-control @error('image') is-invalid @enderror" type="file" name="image">
                                @error('image')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-lg-12">
                                <label for="short_description">Short Description</label>
                                <textarea class="form-control @error('short_description') is-invalid @enderror" name="short_description">{{ old('short_description') }}</textarea>
                                @error('short_description')
                                <span class="text-danger">{{ $message }}</span>
                                @enderror
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
                    <h5>Schools</h5>
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
                        <table class="table table-striped table-bordered table-hover dataTables-example">
                            <thead>
                                <tr>
                                    <th>id</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th style="width:130px;">Status</th>
                                    <th style="width:130px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schools as $index => $data)
                                <tr>

                                    <td>
                                        {{ $loop->iteration }}
                                    </td>

                                    <td>
                                        <p>{{ $data->name }}
                                             ({{ $data->category_count }})
                                        </p>
                                        @php
                                        // Count the total number of courses across all categories for the current school
                                        $totalCourses = $data->categories->sum(function($category) {
                                        return $category->courses->count();
                                        });
                                        @endphp

                                    </td>
                                    <td>
                                        <p title="{{ $data->short_description }}">
                                            {{ \Illuminate\Support\Str::limit($data->short_description, 50, '...') }}
                                        </p>

                                    </td>

                                    <td>
                                        <select name="course_school" class="form-control" id="course_school_{{ $data->id }}" onchange="updateSchoolStatus(this.value, {{ $data->id }})">
                                            <option value="active" @if(is_null($data->deleted_at)) selected @endif>Active</option>
                                            <option value="disable" @if(!is_null($data->deleted_at)) selected @endif>Disable</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('school.edit', $data->id) }}" class="btn-primary btn btn-xs">
                                                <i class="fa fa-pencil"></i> Edit
                                            </a>

                                            <form action="{{ route('school.destroy', $data->id) }}" method="POST" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger btn btn-xs">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                </tr>
                                @empty
                                <tr>
                                    <td class="text-align:center;" colspan="3">No Record Found!</td>
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
    function updateSchoolStatus(status, schoolId) {
        // AJAX call to update course status
        //alert(courseId);
        $.ajax({
            url: 'school/' + schoolId + '/update-status-school',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}', // For Laravel CSRF protection
                status: status
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.success); // Show success notification
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
    $(document).ready(function() {
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