@extends('admin.layout.app')
@section('title', 'Profile')
@push('style')
<style>
    .mb{
        margin-bottom: 1.5rem;
    }
 </style>
@endpush

@section('content')

<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-12">
        <h2>Profile</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li>
                <a href="#">Users</a>
            </li>
            <li class="active">
                <strong>Profile</strong>
            </li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content">
    <div class="row animated fadeInRight">
        <div class="col-md-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>Account Details</h5>
                    <div class="ibox-tools">
                        <a class="collapse-link">
                            <i class="fa fa-chevron-up"></i>
                        </a>
                        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                            <i class="fa fa-wrench"></i>
                        </a>
                       
                        <a class="close-link">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                </div>
                <div class="ibox-content">

                    {{-- <div>
                    <form role="form">
                        <div class="form-group"><label>User Name</label> <input type="email" value="{{ auth('admin')->user()->username }}" class="form-control"></div>
                        <div class="form-group"><label>Email</label> <input type="email" value="{{ auth('admin')->user()->email }}" class="form-control"></div>
                        <div class="form-group"><label>Password</label> <input type="email" value="{{ auth('admin')->user()->email }}" class="form-control"></div>
                       
                    </form>
                        

                        <!-- <button class="btn btn-primary btn-md m"><i class="fa fa-arrow-down"></i> Show More</button> -->

                    </div> --}}
                    <form role="form" action="{{route('admin.profile.update')}}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-lg-12 mb">
                                <label for="">User Name</label>
                                <input class="form-control" type="text" name="username" value="{{ old('username', auth('admin')->user()->username ) }}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label class="mb-1">Email</label>
                                <input class="form-control" type="text" name="email" value="{{ old('email', auth('admin')->user()->email ) }}">
                            </div>
                            <div class="col-lg-12 mb">
                                <label class="mb-1">Password</label>
                                <input class="form-control" type="password" name="password" >
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Update</button>
                                <button class="btn btn-warning" type="rest"><i class="fa fa-paste"></i> Reset</button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
        
@endsection

@push('script')

@endpush