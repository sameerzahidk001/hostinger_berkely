@extends('user.layout.app')
@section('title', 'Admission Information')

@push('style')
<style>
    .wrapper-content { padding-right: 20px; }
</style>
@include('user.payments._admission_styles')
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
