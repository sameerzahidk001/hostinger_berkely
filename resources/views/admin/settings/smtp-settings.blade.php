@extends('admin.layout.app')
@section('title', 'Subjects')
@push('style')
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
        <h2>Email SMTP Settings</h2>
        <ol class="breadcrumb">
            <li>
                <a href="{{ route('admin.home') }}">Home</a>
            </li>
            <li class="active">
                <strong>Email SMTP Settings</strong>
            </li>
        </ol>
    </div>
</div>

<div class="wrapper wrapper-content animated fadeInRight" style="padding-bottom:0px;">
    <div class="row">
        <div class="col-lg-12">
            <div class="ibox float-e-margins" style="margin-bottom: 0;">
                <div class="ibox-title">
                    <h5>Email SMTP Settings</h5>
                </div>

                <div class="ibox-content">

                    <form role="form" action="{{ route('admin.smtpSettings.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label>SMTP Host:</label>
                                <input type="text" class="form-control" name="MAIL_HOST" value="{{ $smtp['MAIL_HOST'] }}" required>
                                @error('MAIL_HOST')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label>SMTP Port:</label>
                                <input type="text" class="form-control" name="MAIL_PORT" value="{{ $smtp['MAIL_PORT'] }}" required>
                                @error('MAIL_PORT')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label>Username:</label>
                                <input type="text" class="form-control" name="MAIL_USERNAME" value="{{ $smtp['MAIL_USERNAME'] }}" required>
                                @error('MAIL_USERNAME')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label>Password:</label>
                                <input type="text" class="form-control" name="MAIL_PASSWORD" value="{{ $smtp['MAIL_PASSWORD'] }}" required>
                                @error('MAIL_PASSWORD')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label>Encryption (ssl/tls/null):</label>
                                <input type="text" class="form-control" name="MAIL_ENCRYPTION" value="{{ $smtp['MAIL_ENCRYPTION'] }}" required>
                                @error('MAIL_ENCRYPTION')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label>From Email:</label>
                                <input type="text" class="form-control" name="MAIL_FROM_ADDRESS" value="{{ $smtp['MAIL_FROM_ADDRESS'] }}" required>
                                @error('MAIL_FROM_ADDRESS')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="col-md-6" style="margin-bottom: 15px;">
                                <label>From Name:</label>
                                <input type="text" class="form-control" name="MAIL_FROM_NAME" value="{{ $smtp['MAIL_FROM_NAME'] }}" required>
                                @error('MAIL_FROM_NAME')
                                    <p class="text-danger text-xs italic">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12" style="margin-top: 16px;text-align:right;"> 
                                <button class="btn btn-primary " type="submit"><i class="fa fa-check"></i>&nbsp;Submit</button>
                                <a href="{{ route('admin.rolesPermissions.index') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i>&nbsp;Cancel</a>
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