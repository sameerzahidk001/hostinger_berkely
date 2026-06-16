@extends('admin.layout.app')
@section('title', 'Clients')
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
        .rounded-circle {
            border-radius: 50%;
        }
    </style>
@endpush

@section('content')
    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col">
            <h2>Clients</h2>
            <ol class="breadcrumb">
                <li>
                    <a href="{{ route('admin.home') }}">Home</a>
                </li>
                <li class="active">
                    <a>Clients</a>
                </li>
            </ol>
        </div>
        <div class="col-auto">
            <a class="btn btn-primary" href="{{ route('admin.clients.create') }}">Create</a>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                <div class="ibox float-e-margins" style="margin-bottom: 0;">
                    <div class="ibox-title">
                        <h5>Clients</h5>
                    </div>
                    <div class="ibox-content">

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-hover dataTables-example">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Active</th>
                                        @include('admin.layout.partials.audit-columns-head')
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($clients as $index => $client)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <img src="{{ asset('images/clients/'.$client->image) }}" alt="Client Image"
                                                    class="rounded-circle" height="40" width="40">
                                            </td>
                                            <td>{{ $client->title }}</td>
                                            <td>{{ $client->description }}</td>
                                            <td><span
                                                    class="badge {{ $client->active == 1 ? 'bg-success' : 'bg-danger' }}">{{ $client->active == 1 ? 'Active' : 'Disabled' }}</span>
                                            </td>
                                            @include('admin.layout.partials.audit-columns-cells', ['model' => $client])
                                            <td>
                                                <div class="btn-group">
                                                    <a href="{{ route('admin.clients.edit', $client->id) }}"
                                                        class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a>
                                                    @include('admin.layout.partials.delete-button', [
                                                        'id' => $client->id,
                                                        'action' => route('admin.clients.destroy', $client->id),
                                                    ])
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center">No Record Found!</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>#</th>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Showing On Homepage</th>
                                        <th>Actions</th>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
</script>
@endpush