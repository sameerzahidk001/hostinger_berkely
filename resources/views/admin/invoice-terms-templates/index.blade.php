@extends('admin.layout.app')
@section('title', 'Invoice T&C Templates')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Invoice T&amp;C Templates</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.payments.index') }}">Payments</a></li>
            <li class="active"><strong>Invoice T&amp;C Templates</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.invoice-terms-templates.create') }}" class="btn btn-primary">Create template</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="ibox">
        <div class="ibox-content">
            <p class="text-muted m-b-md">
                Admin / Accountant create templates here (IDs <strong>PAYTC1</strong>, <strong>PAYTC2</strong>, …).
                Select one on each fee package. New invoices load that package’s template automatically (no T&amp;C editor on Create Invoice).
            </p>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Created By</th>
                            <th>Modified Date</th>
                            <th>Modified By</th>
                            <th style="width:120px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $row)
                            <tr>
                                <td><strong>{{ $row->displayCode() }}</strong></td>
                                <td>{{ $row->name }}</td>
                                <td>
                                    @if($row->is_active)
                                        <span class="text-navy">Active</span>
                                    @else
                                        <span class="text-muted">Inactive</span>
                                    @endif
                                </td>
                                @include('admin.layout.partials.audit-columns-cells', ['model' => $row])
                                <td>
                                    <a href="{{ route('admin.invoice-terms-templates.edit', $row->id) }}" class="btn btn-xs btn-primary"><i class="fa fa-pencil"></i> Edit</a>
                                    <form action="{{ route('admin.invoice-terms-templates.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete {{ $row->displayCode() }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted">No templates yet. Create PAYTC1 first.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
