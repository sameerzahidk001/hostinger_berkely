@extends('user.layout.app')
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
                    <a href="#">Home</a>
                </li>
                <li>
                    <a>Learner Stories</a>
                </li>
                <li class="active">
                    <strong>All</strong>
                </li>
            </ol>
        </div>
        @if(auth()->user()->hasPermission('testimonial-list'))
            <div class="col-auto">
                <a href="{{ route('user.testimonial.create') }}" class="btn-primary btn btn-md">
                    Create
                </a>
            </div>
        @endif
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
                        <table class="table table-striped table-bordered table-hover dataTables-example">
                            <thead>
                                <tr>
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
                                @php
                                    use App\Models\Payment;
                                    $Payment = Payment::where('user_id', Auth::id())->get();
                                @endphp
                                @forelse($all_testimonial as $key => $data)
                                    @php
                                        // Check if any payment matches the testimonial's course_id
                                        $hasMatchingPayment = $Payment->contains(function ($payment) use ($data) {
                                            return $payment->course_id == $data->course_id;
                                        });
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="media">
                                                <div class="media-left">
                                                    @if($data->image)
                                                        <a href="{{ asset($data->image) }}">
                                                            <img src="{{ asset($data->image) }}" alt="" class="media-object" width="60" height="60">
                                                        </a>
                                                    @else
                                                        <img src="{{ asset('/images/profiles/user.png') }}" alt="No image" class="media-object" width="60" height="60">
                                                    @endif
                                                </div>
                                                <div class="media-body" style="vertical-align: middle;">
                                                    <p style="margin-bottom:0;font-weight:bold;">{{ $data->name }}</p>
                                                    <p style="margin-bottom:0;">{{ $data->city . ', ' . $data->country }}</p>
                                                </div>
                                            </div>
                                            @if($data->asset_path)
                                                <br>
                                                <a href="{{ asset($data->asset_path) }}">Show Video</a>
                                            @endif
                                        </td>
                                        <td>
                                            {!! $data->text !!}
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
                                            <span class="badge {{ $data->status == 'show' ? 'bg-primary' : 'bg-danger' }}">{{ $data->status == 'show' ? 'Active' : 'Disabled' }}</span>
                                        </td>
                                        <td>
                                            {{ date('Y-m-d', strtotime($data->date)) }}
                                        </td>
                                        <td>
                                            @if(auth()->user()->hasPermission('testimonial-update'))
                                                <a href="{{ route('user.testimonial.edit', $data->id) }}"
                                                    class="btn btn-primary btn-xs" target="_blank">Edit</a>
                                            @endif
                                            @if(auth()->user()->hasPermission('testimonial-delete'))
                                                <form id="delete-form-{{ $data->id }}"
                                                    action="{{ route('user.testimonial.destroy', $data->id) }}" method="POST"
                                                    style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn-danger btn btn-xs"
                                                        onclick="confirmDelete({{ $data->id }})">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            @endif
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
            var table = $('.dataTables-example').DataTable({
                pageLength: 10,
                lengthMenu: [10, 20, 50, 100],
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
                const selected = this.value;
                if (selected === 'all') {
                    table.column(5).search('').draw();
                } else {
                    table.column(5).search(selected).draw();
                }
            });

            $('#resetFilter').click(function () {
                $('#courseFilter').val('');
                $('#categoryFilter').val('');
                $('#statusFilter').val('all');
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