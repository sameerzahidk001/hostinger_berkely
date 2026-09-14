@extends('user.layout.app')
@section('title', 'Admission Information')

@include('user.partials.rakbank-payment-modal')

@push('style')
<link href="{{ asset('/admin/css/plugins/dataTables/datatables.min.css') }}" rel="stylesheet">
<style>
    .wrapper-content {
        padding-right: 20px;
    }
    .admin-dt-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px 16px;
        margin-bottom: 14px;
        width: 100%;
    }
    .admin-dt-toolbar .dataTables_length,
    .admin-dt-toolbar .dataTables_filter {
        float: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .admin-dt-toolbar .dataTables_length label,
    .admin-dt-toolbar .dataTables_filter label {
        margin-bottom: 0;
        font-weight: normal;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .admin-dt-toolbar .dataTables_filter {
        margin-left: auto !important;
    }
    @media (max-width: 768px) {
        .admin-dt-toolbar .dataTables_filter {
            margin-left: 0 !important;
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row wrapper border-bottom white-bg page-heading">
        <div class="col-lg-10">
            <h2>Admission Information</h2>
            <ol class="breadcrumb">
                <li><a href="{{ route('user.home') }}">Dashboard</a></li>
                <li class="active"><strong>Admission Information</strong></li>
            </ol>
        </div>
    </div>

    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
            <div class="col-lg-12">
                @include('user.payments._admission_table')
            </div>
        </div>
    </div>
@endsection

@push('script')
    @include('user.payments._admission_scripts')
@endpush
