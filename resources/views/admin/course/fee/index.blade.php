@extends('admin.layout.app')
@section('title', 'Fee')
@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
    <style>
        .page-heading {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
        }
        .col {
            flex: 1;
        }
    </style>
@endpush
@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col">
            <h2>Fee Structure</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="index.html">Home</a>
                </li>
                <li>
                    <a>Courses</a>
                </li>
                <li class="active">
                    <strong>{{ $course->title }}</strong>
                </li>
            </ol>
        </div>
        <div class="col-auto">
            <a class="btn btn-primary" href="{{ route('course.create-fee', ['id' => $course->id]) }}">Create</a>
        </div>
    </div>

    <div class="row">
        <div class="wrapper wrapper-content">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom:0;">
                    <div class="ibox-title">
                        <h5>Label & Section Status</h5>
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
                        <form action="{{ route('courses.update-fee-status', ['id' => $course->id]) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <div class="row">

                                <div class="col-lg-12 mb">

                                    <label class="mb-1">Status</label>
                                    <select name="fee_visibility" class="form-control">
                                        <option value="1" {{ $course->fee_visibility == 1 ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ $course->fee_visibility == 0 ? 'selected' : '' }}>Disabled
                                        </option>
                                    </select>
                                </div>

                                <div class="col-lg-12 mb">
                                    <label class="mb-1">Section Heading Label (Fee structure)</label>
                                    <input type="text" name="label[fee_strucutre]"
                                        value="{{ old('label[fee_strucutre]', $course->dynamicLabel?->fee_strucutre) }}"
                                        class="form-control" placeholder="add section heading lable">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12" style="margin-top: 0px;text-align:right;">
                                    <button class="btn btn-primary " type="submit"><i
                                            class="fa fa-check"></i>&nbsp;Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="wrapper wrapper-content">
                <!-- <div class="col-lg-12"> -->
                <div class="ibox float-e-margins" style="margin-bottom:0;">
                    <div class="ibox-title">
                        <h5>{{ $course->title }}</h5>
                        <div class="ibox-tools">

                            <a class="collapse-link">
                                <i class="fa fa-chevron-down"></i>
                            </a>

                            <a class="close-link">
                                <i class="fa fa-times"></i>
                            </a>
                        </div>
                    </div>
                    <div class="ibox-content">

                        <table class="table table-striped table-bordered table-hover dataTables-example">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Package Name</th>
                                    <th>Price</th>
                                    <th>Short Description</th>
                                    <!-- <th>Short Description</th> -->
                                    <th>Discount Amount</th>
                                    <th>Currency</th>
                                    <th style="width:130px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($course->courseFeePackages as $key => $data)
                                    <tr>
                                        <td>{{ ++$key}}</td>
                                        <td>{{ $data->package_name }}</td>
                                        <td>{{ $data->price }}</td>
                                        <td>{{ $data->short_description }}</td>
                                        <td>{{ $data->discount_amount }}</td>
                                        <td>{{ $data->currency }}</td>
                                        <td style="vertical-align: middle;" class="center">
                                            <div class="btn-group">
                                                <a href="{{ route('course.edit-fee', ['id' => $data->id]) }}"
                                                    class="btn-primary btn btn-xs">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </a>
                                                <form id="delete-form-{{ $data->id }}"
                                                    action="{{ route('course.delete-fee', $data->id) }}" method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn-danger btn btn-xs"
                                                        onclick="confirmDelete({{ $data->id }})">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
                <!-- </div> -->
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
    <script>
        $(document).ready(function () {
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
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }

        function updateCourseFeeStatus(status, courseId) {
            // AJAX call to update course status
            $.ajax({
                url: '/admin/course/' + courseId + '/update-fee-status',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // For Laravel CSRF protection
                    status: status
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.success);  // Show success notification
                    } else {
                        toastr.error('Unexpected response received.');
                    }
                },
                error: function (xhr, status, error) {
                    toastr.error('Error updating course status.');
                }
            });
        }
    </script>
@endpush