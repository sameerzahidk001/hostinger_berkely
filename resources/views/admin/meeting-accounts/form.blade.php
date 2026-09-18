@extends('admin.layout.app')
@section('title', ($account->exists ? 'Edit' : 'Add') . ' Meeting Account')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>{{ $account->exists ? 'Edit' : 'Add' }} {{ strtoupper($provider) }} Meeting Account</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li><a href="{{ route('admin.meeting-accounts.index') }}">Meeting Accounts</a></li>
            <li class="active"><strong>{{ $account->exists ? 'Edit' : 'Add' }}</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if($errors->any())
        <div class="alert alert-danger"><ul class="m-b-none">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    <div class="ibox">
        <div class="ibox-content">
            <form method="POST" action="{{ $account->exists ? route('admin.meeting-accounts.update', $account->id) : route('admin.meeting-accounts.store') }}">
                @csrf
                @if($account->exists) @method('PUT') @endif
                <input type="hidden" name="provider" value="{{ $provider }}">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>Label *</label>
                        <input type="text" name="label" class="form-control" value="{{ old('label', $account->label) }}" required placeholder="e.g. Berkeley Main Zoho">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Host email</label>
                        <input type="email" name="host_email" class="form-control" value="{{ old('host_email', $account->host_email) }}" placeholder="bdm@berkeleyme.com">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>Timezone</label>
                        <input type="text" name="timezone" class="form-control" value="{{ old('timezone', $account->timezone ?: 'Asia/Dubai') }}">
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Active</label>
                        <select name="is_active" class="form-control">
                            <option value="1" @selected(old('is_active', $account->is_active ? '1' : '0') === '1')>Yes</option>
                            <option value="0" @selected(old('is_active', $account->is_active ? '1' : '0') === '0')>No</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Default</label>
                        <select name="is_default" class="form-control">
                            <option value="0" @selected(! old('is_default', $account->is_default))>No</option>
                            <option value="1" @selected(old('is_default', $account->is_default))>Yes</option>
                        </select>
                    </div>

                    @if($provider === 'zoom')
                        <div class="col-md-12">
                            <hr>
                            <h4>Zoom (manual Join link)</h4>
                            <p class="help-block">
                                Zoom meetings are not created by the LMS. Add a label here so Zoom appears in
                                Class Schedule, then paste the Zoom Join URL on each schedule.
                                OAuth fields below are optional (only if you later want API create).
                            </p>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Account ID</label>
                            <input type="text" name="account_id" class="form-control" value="{{ old('account_id', $credentials['account_id'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Client ID</label>
                            <input type="text" name="client_id" class="form-control" value="{{ old('client_id', $credentials['client_id'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Client Secret</label>
                            <input type="text" name="client_secret" class="form-control" value="{{ old('client_secret', '') }}" placeholder="{{ $account->exists ? 'Leave blank to keep current' : 'Optional' }}">
                        </div>
                    @else
                        <div class="col-md-12"><hr><h4>Zoho OAuth credentials</h4></div>
                        <div class="col-md-4 form-group">
                            <label>Client ID *</label>
                            <input type="text" name="client_id" class="form-control" value="{{ old('client_id', $credentials['client_id'] ?? '') }}" @required(!$account->exists)>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Client Secret *</label>
                            <input type="text" name="client_secret" class="form-control" value="{{ old('client_secret', '') }}" placeholder="{{ $account->exists ? 'Leave blank to keep current' : '' }}" @required(!$account->exists)>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Refresh Token *</label>
                            <input type="text" name="refresh_token" class="form-control" value="{{ old('refresh_token', '') }}" placeholder="{{ $account->exists ? 'Leave blank to keep current' : '' }}" @required(!$account->exists)>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Org ID</label>
                            <input type="text" name="org_id" class="form-control" value="{{ old('org_id', $credentials['org_id'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Presenter ZUID</label>
                            <input type="text" name="presenter_zuid" class="form-control" value="{{ old('presenter_zuid', $credentials['presenter_zuid'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Calendar UID</label>
                            <input type="text" name="calendar_uid" class="form-control" value="{{ old('calendar_uid', $credentials['calendar_uid'] ?? '') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>WorkDrive Folder ID</label>
                            <input type="text" name="workdrive_folder_id" class="form-control" value="{{ old('workdrive_folder_id', $credentials['workdrive_folder_id'] ?? '') }}">
                        </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.meeting-accounts.index') }}" class="btn btn-default">Cancel</a>
            </form>
        </div>
    </div>
</div>
@endsection
