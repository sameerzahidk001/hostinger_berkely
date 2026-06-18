@extends('admin.layout.app')
@section('title', 'Learner Stories')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
@push('style')
    <style>
        td {
            vertical-align: middle !important;
        }

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
            <h2>Learner Stories</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                </li>
                <li>
                    <a>Learner Stories</a>
                </li>
                <li class="active">
                    <strong>All</strong>
                </li>
            </ol>
        </div>
        <div class="col-auto">
            <a href="{{ route('testimonial.create') }}" class="btn-primary btn btn-md">
                Create
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="wrapper wrapper-content">
                <div class="ibox float-e-margins" style="margin-bottom: 0px;">
                    <div class="ibox-title">
                        <h5>Learner Stories</h5>
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
                        <div class="row" style="margin-bottom: 20px;">
                            <div class="col-lg-3">
                                <select class="form-control" id="courseFilter">
                                    <option value="">Select Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course?->short_name }}">
                                            {{ $course->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <select class="form-control" id="categoryFilter">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->name }}">
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-3">
                                <select class="form-control" id="statusFilter">
                                    <option value="active">Active</option>
                                    <option value="disabled">Disabled</option>
                                    <option value="all" selected>All Testimonial</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-danger btn-md" style="width: 100%;"
                                    id="resetFilter">Reset</button>
                            </div>
                        </div>
                        <table class="table table-striped table-bordered table-hover dataTables-example">
                            <thead>
                                <tr>
                                    <th style="width: 120px;">Priority</th>
                                    <th style="width: 130px;">Alumni Detail</th>
                                    <th>Details</th>
                                    <th>Course</th>
                                    <th style="width: 160px;">Category</th>
                                    <th style="width: 120px;">Status</th>
                                    <th>Date</th>
                                    <th style="width: 120px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($all_testimonial as $key => $data)
                                    <tr data-status="{{ $data->status === 'hide' ? 'disabled' : 'active' }}">
                                        <td>
                                            <select name="testimonial_priority" class="form-control"
                                                id="testimonial_priority_{{ $data->id }}"
                                                onchange="updateTestimonialPriority(this.value, {{ $data->id }})">
                                                <option value="" selected>-- Select --</option>
                                                @for($i = 1; $i <= 100; $i++)
                                                                                <option value="{{ $i }}" {{ ($i == $data->priority)
                                                    ? 'selected' : '' }}>
                                                                                    {{ $i }}
                                                                                </option>
                                                @endfor
                                            </select>

                                        </td>
                                        <td>
                                            <p style="margin-bottom:0;">{{ $data->name }}</p>
                                            <p style="margin-bottom:0;">{{ $data->city . ', ' . $data->country }}</p>
                                            <a href="{{ asset($data->image) }}">
                                                <img src="{{ asset($data->image) }}" alt="" width="60" height="60">
                                            </a>
                                            @if($data->asset_path)
                                                <br>
                                                <a href="{{ asset($data->asset_path) }}">Show Video</a>
                                            @endif
                                        </td>
                                        <td>
                                            <textarea rows="2" class="form-control" readonly>{{ $data->text }}</textarea>
                                        </td>
                                        <td>
                                            {{ $data->course?->short_name }}
                                        </td>
                                        <td>
                                            @if($data->course)
                                                <ul style="padding-left: 20px;">
                                                    @foreach($data->course->categories as $categoryIndex => $category)
                                                        <li>{{ $category->name }}@if(!$loop->last), @endif</li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </td>

                                        <td>
                                            <select name="course_status" class="form-control" id="course_status_{{ $data->id }}"
                                                onchange="updateTestimonialStatus(this.value, {{ $data->id }})">
                                                <option value="show" {{ $data->status === 'show' ? 'selected' : '' }}>Active
                                                </option>
                                                <option value="hide" {{ $data->status === 'hide' ? 'selected' : '' }}>Disable
                                                </option>
                                            </select>
                                        </td>
                                        <td>
                                            {{ date('Y-m-d', strtotime($data->date)) }}
                                        </td>
                                        <td>
                                            <a href="{{ route('testimonial.edit', $data->id) }}" class="btn btn-primary btn-xs"
                                                target="_blank">Edit</a>
                                            <form id="delete-form-{{ $data->id }}"
                                                action="{{ route('course.del-testimonail', $data->id) }}" method="POST"
                                                style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn-danger btn btn-xs"
                                                    onclick="confirmDelete({{ $data->id }})">
                                                    <i class="fa fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
    <!-- Page-Level Scripts -->
    <script>
        $(document).ready(function () {
            var statusFilterValue = 'all';

            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                if (statusFilterValue === 'all') {
                    return true;
                }

                var row = settings.aoData[dataIndex]?.nTr;
                if (!row) {
                    return true;
                }

                return $(row).data('status') === statusFilterValue;
            });

            var table = $('.dataTables-example').DataTable({
                pageLength: 10,
                searching: true,
                lengthChange: true,
                paging: true,
                info: false,
                ordering: true,
                responsive: true,
                dom: 'lftip'
            });

            $('#courseFilter').on('change', function () {
                table.column(3).search(this.value).draw();
            });

            $('#categoryFilter').on('change', function () {
                table.column(4).search(this.value).draw();
            });

            $('#statusFilter').on('change', function () {
                statusFilterValue = this.value;
                table.draw();
            });

            $('#resetFilter').click(function () {
                $('#courseFilter').val('');
                $('#categoryFilter').val('');
                $('#statusFilter').val('all');
                statusFilterValue = 'all';
                table.columns().search('').draw();
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

        function updateTestimonialStatus(status, testimonial) {
            // AJAX call to update course status
            $.ajax({
                url: '/admin/course/' + testimonial + '/update-testimonail-status',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // For Laravel CSRF protection
                    status: status
                },
                success: function (response) {
                    console.log(response);
                    if (response.success) {
                        toastr.success(response.success);  // Show success notification
                    } else {
                        toastr.error('Unexpected response received.');
                    }
                },
                error: function (xhr, status, error) {
                    alert('Error updating course status.');
                }
            });
        }

        function updateTestimonialPriority(status, priority) {
            // AJAX call to update course status
            //alert(status);
            $.ajax({
                url: '/admin/course/' + priority + '/update-testimonail-priority',
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
                    alert('Error updating course status.');
                }
            });
        }
    </script>
@endpush