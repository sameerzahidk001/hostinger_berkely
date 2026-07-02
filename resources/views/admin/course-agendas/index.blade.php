@extends('admin.layout.app')
@section('title', 'Training Calendar')
@push('style')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<style>
    .page-heading{
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
    }
    .col{
        flex: 1;
    }
</style>
@endpush
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col">
        <h2>Training Calendar</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li class="active">
                <a>Training Calendar</a>
            </li>
        </ol>
    </div>
    <div class="col-auto">
        <a class="btn btn-primary" href="{{ route('admin.course-agendas.create') }}">Create</a>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Training Calendar</h5>
                </div>
                <div class="ibox-content">

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover dataTables-example">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Course</th>
                                    <th>Subject</th>
                                    <th>Delivery Option</th>
                                    <th>Location</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Created</th>
                                    <th>Created By</th>
                                    <th>Last Modified</th>
                                    <th>Modified By</th>
                                    <th style="width:130px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($course_agendas as $index => $data)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $data->course?->title ?? 'N/A' }}</td>
                                        <td>{{ $data->subject }}</td>
                                        <td>{{ $data->delivery_type ? $data->delivery_type : 'Virtual & Classroom' }}</td>
                                        <td>{{ $data->country ? $data->country->name : 'International' }}<br><span class="text-muted">{{ $data->city }}</span></td>
                                        <td>{{ Carbon\Carbon::parse($data->from)->format('M d, Y') }}</td>
                                        <td>{{ Carbon\Carbon::parse($data->to)->format('M d, Y') }}</td>
                                        <td>{{ $data->created_at ? Carbon\Carbon::parse($data->created_at)->format('M d, Y H:i') : '-' }}</td>
                                        <td>{{ audit_user_name($data->createdBy, $data->created_by) }}</td>
                                        <td>{{ $data->updated_at ? Carbon\Carbon::parse($data->updated_at)->format('M d, Y H:i') : '-' }}</td>
                                        <td>{{ audit_user_name($data->updatedBy, $data->updated_by) }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.course-agendas.edit', ['training_calendar' => $data->id]) }}"
                                                    class="btn-primary btn btn-xs">
                                                    <i class="fa fa-pencil"></i> Edit
                                                </a>

                                                @include('admin.layout.partials.delete-button', [
                                                    'id' => $data->id,
                                                    'action' => route('admin.course-agendas.destroy', ['training_calendar' => $data->id]),
                                                ])
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-center" colspan="12">No Record Found!</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>#</th>
                                    <th>Course</th>
                                    <th>Subject</th>
                                    <th>Delivery Option</th>
                                    <th>Location</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Created</th>
                                    <th>Created By</th>
                                    <th>Last Modified</th>
                                    <th>Modified By</th>
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
<!-- Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" role="dialog" aria-labelledby="inquiryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Submit an Inquiry</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="inquiry-form-container">
        <!-- Form will be injected here -->
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

    </script>
@endpush