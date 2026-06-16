@extends('admin.layout.app')

@section('title', 'Countries & Their Currencies')

@push('style')
    <link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox">
                <div class="ibox-title d-flex justify-content-between align-items-center">
                    <h5>Countries with Currencies</h5>
                    <a href="{{ route('currencies.create') }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-plus"></i> Add Currency
                    </a>
                </div>
                <div class="ibox-content">
                    <div class="table-responsive">
                        <table id="countries-table" class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Country Name</th>
                                    <th>ISO Code</th>
                                    <th>Currency Name</th>
                                    <th>Currency Code</th>
                                    <th>Assigned Date</th>
                                    <th>Edit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($countries as $country)
                                    <tr>
                                        <td>{{ $country->name }}</td>
                                        <td>{{ $country->iso_code }}</td>
                                        <td>{{ $country->currency->name ?? 'N/A' }}</td>
                                        <td>{{ $country->currency->code ?? 'N/A' }}</td>
                                        <td>{{ $country->updated_at->format('Y-m-d') }}</td>
                                        <td>
                                            <a href="{{ route('currencies.edit', $country->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fa fa-edit"></i> Edit
                                            </a>
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
    <script src="{{ asset('/admin/js/plugins/dataTables/datatables.min.js') }}"></script>
    <script>
        $(document).ready(function() {
            $('#countries-table').DataTable({
                pageLength: 10,
                lengthChange: true,
                searching: true,
                ordering: true,
                autoWidth: false
            });
        });
    </script>
@endpush
