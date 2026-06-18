@extends('admin.layout.app')
@section('title', 'Roles & Permissions')
@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
@endpush
@push('style')
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
            <h2>{{ $type === 'librarian' ? 'Content Writers' : ucfirst(Str::plural($type)) }}</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                </li>
                <li class="active">
                    <a>{{ ucfirst(Str::plural($type)) }}</a>
                </li>
            </ol>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>{{ ucfirst(Str::plural($type)) }}</h5>
                    </div>
                    <div class="ibox-content">

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover dataTables-example">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Image</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Country</th>
                                        <th>IP Address</th>
                                        <th>Created On</th>
                                        <th>Verified On</th>
                                        <th style="width:130px;">Status</th>
                                        <th>Show on Web</th>
                                        <th style="width:130px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users as $index => $data)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td style="white-space: nowrap;">{{ $data->name }}</td>
                                            <td><img src="{{ asset($data->image) }}" width="30" height="30"
                                                    alt="user image"></td>
                                            <td>{{ $data->email }}</td>
                                            <td>{{ $data->mobile_number }}</td>
                                            <td>{{ $data->country }}</td>
                                            <td>{{ $data->ip_address }}</td>
                                            <td data-order="{{ \Carbon\Carbon::parse($data->created_at)->timestamp }}" style="white-space: nowrap;">
                                                {{ \Carbon\Carbon::parse($data->created_at)->format('d M Y') }} <br>
                                                {{ \Carbon\Carbon::parse($data->created_at)->format('h:i A') }}
                                            </td>
                                            <td>
                                                @if ($data->email_verified_at)
                                                    <span class="text-success">
                                                        {{ \Carbon\Carbon::parse($data->email_verified_at)->format('d M Y') }}
                                                        <br>
                                                        {{ \Carbon\Carbon::parse($data->email_verified_at)->format('h:i A') }}
                                                    </span>
                                                @else
                                                    <a href="{{ route('users.emailVerification', ['id' => $data->id]) }}"
                                                        class="btn btn-danger">Send Email</a>
                                                @endif
                                            </td>
                                            <td>
                                                <select name="status" class="form-control"
                                                    id="user_status_{{ $data->id }}"
                                                    onchange="updateUserStatus(this.value, {{ $data->id }})">
                                                    <option value="1" {{ $data->approved == 1 ? 'selected' : '' }}>
                                                        Active</option>
                                                    <option value="0" {{ $data->approved == 0 ? 'selected' : '' }}>
                                                        Disable</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select class="form-control"
                                                    id="user_web_show_{{ $data->id }}"
                                                    onchange="updateShowOnWeb(this.value, {{ $data->id }})">
                                                <option value="1" {{ $data->is_on_web == 1 ? 'selected' : '' }}>Yes</option>
                                                <option value="0" {{ $data->is_on_web == 0 ? 'selected' : '' }}>No</option>
                                            </select>
                                            </td>

                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('users.edit', $data->id) }}"
                                                        class="btn-primary btn btn-xs editFaqs">
                                                        <i class="fa fa-edit"></i>Edit</a>
                                                    <form id="delete-form-{{ $data->id }}"
                                                        action="{{ route('users.destroy', $data->id) }}" method="POST"
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
                                    @empty
                                        <tr>
                                            <td class="text-center" colspan="5">No Record Found!</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Mobile</th>
                                        <th>Country</th>
                                        <th>IP Address</th>
                                        <th>Created On</th>
                                        <th>Verified On</th>
                                        <th style="width:130px;">Status</th>
                                        <th style="width:130px;">Action</th>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
    <!-- Page-Level Scripts -->
    <script>
        function updateShowOnWeb(status, userId) {
            $.ajax({
                url: '{{ route('users.updateShow') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: userId,
                    status: status,
                },
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error('Unexpected response received.');
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", xhr.responseText);
                    toastr.error('Error updating course status.');
                }
            });
        }

        function updateUserStatus(status, userId) {
            $.ajax({
                url: '{{ route('users.status') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    id: userId,
                    status: status,
                },
                success: function(response) {
                    console.log(response);
                    if (response.success) {
                        toastr.success(response.success);
                    } else {
                        toastr.error('Unexpected response received.');
                    }
                },
                error: function(xhr, status, error) {
                    console.log("AJAX Error:", xhr.responseText);
                    toastr.error('Error updating course status.');
                }
            });
        }
        $(document).ready(function() {
            $('.dataTables-example').DataTable({
                pageLength: 10,
                searching: true,
                lengthChange: true,
                paging: true,
                info: false,
                ordering: true,
                responsive: true,
                dom: 'lftip',
                order: [[7, 'desc']]
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
    </script>
@endpush