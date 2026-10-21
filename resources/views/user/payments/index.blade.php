@extends('user.layout.app')
@section('title', 'Admission Information')

@push('style')
<style>
    .wrapper-content { padding-right: 20px; }
    .lms-admission-wrap {
        overflow-x: hidden;
        width: 100%;
    }
    .lms-admission-wrap,
    .lms-admission-wrap table,
    .lms-admission-table {
        min-width: 0 !important;
        width: 100% !important;
    }
    .lms-admission-table th,
    .lms-admission-table td {
        white-space: normal;
        word-break: break-word;
        vertical-align: top;
    }
    @media (max-width: 991px) {
        .lms-admission-table thead { display: none; }
        .lms-admission-table,
        .lms-admission-table tbody,
        .lms-admission-table tr,
        .lms-admission-table td {
            display: block;
            width: 100%;
        }
        .lms-admission-table tr {
            margin-bottom: 12px;
            border: 1px solid #e7eaec;
            background: #fff;
        }
        .lms-admission-table td {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 12px;
            border: 0;
            border-bottom: 1px solid #f1f1f1;
        }
        .lms-admission-table td:last-child { border-bottom: 0; }
        .lms-admission-table td::before {
            content: attr(data-label);
            font-weight: 600;
            flex: 0 0 42%;
            color: #676a6c;
        }
        .table-responsive,
        .ibox-content,
        .wrapper-content {
            overflow-x: hidden !important;
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
