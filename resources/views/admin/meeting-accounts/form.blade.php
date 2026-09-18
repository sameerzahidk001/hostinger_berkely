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
                        @php
                            $timezoneOptions = [
                                'Asia/Dubai' => 'Asia/Dubai (UAE)',
                                'Asia/Karachi' => 'Asia/Karachi (Pakistan)',
                                'Asia/Kolkata' => 'Asia/Kolkata (India)',
                                'Asia/Riyadh' => 'Asia/Riyadh (Saudi Arabia)',
                                'Asia/Qatar' => 'Asia/Qatar',
                                'Asia/Bahrain' => 'Asia/Bahrain',
                                'Asia/Kuwait' => 'Asia/Kuwait',
                                'Asia/Muscat' => 'Asia/Muscat (Oman)',
                                'Europe/London' => 'Europe/London (UK)',
                                'Europe/Paris' => 'Europe/Paris',
                                'Africa/Cairo' => 'Africa/Cairo (Egypt)',
                                'Africa/Johannesburg' => 'Africa/Johannesburg',
                                'America/New_York' => 'America/New_York (US East)',
                                'America/Chicago' => 'America/Chicago (US Central)',
                                'America/Denver' => 'America/Denver (US Mountain)',
                                'America/Los_Angeles' => 'America/Los_Angeles (US West)',
                                'UTC' => 'UTC',
                            ];
                            $selectedTz = old('timezone', $account->timezone ?: 'Asia/Dubai');
                            if ($selectedTz && ! array_key_exists($selectedTz, $timezoneOptions)) {
                                $timezoneOptions = [$selectedTz => $selectedTz] + $timezoneOptions;
                            }
                        @endphp
                        <select name="timezone" class="form-control">
                            @foreach($timezoneOptions as $value => $label)
                                <option value="{{ $value }}" @selected($selectedTz === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <span class="help-block">Pick from the list — no need to type.</span>
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
                                <strong>You do not need Account ID / Client ID / Client Secret.</strong>
                                Only fill Label (+ optional host email), Save, then on Class Schedule pick this Zoom account
                                and paste the Zoom Join URL. OAuth fields below are optional (future API use only).
                            </p>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Account ID <span class="text-muted">(optional)</span></label>
                            <input type="text" name="account_id" class="form-control" value="{{ old('account_id', $credentials['account_id'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Client ID <span class="text-muted">(optional)</span></label>
                            <input type="text" name="client_id" class="form-control" value="{{ old('client_id', $credentials['client_id'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Client Secret <span class="text-muted">(optional)</span></label>
                            <input type="text" name="client_secret" class="form-control" value="{{ old('client_secret', '') }}" placeholder="{{ $account->exists ? 'Leave blank to keep current' : 'Optional' }}">
                        </div>
                    @else
                        <div class="col-md-12">
                            <hr>
                            <h4>Zoho OAuth credentials</h4>
                            <div class="alert alert-info" style="margin-bottom:15px;">
                                <strong>How to get Refresh Token (Self Client)</strong>
                                <ol style="margin:8px 0 0;padding-left:18px;">
                                    <li>Open <a href="https://api-console.zoho.com/" target="_blank" rel="noopener">Zoho API Console</a> → your Self Client.</li>
                                    <li>Generate Code with scopes (example):
                                        <code style="display:block;margin-top:4px;white-space:pre-wrap;">ZohoMeeting.meeting.ALL,ZohoMeeting.recording.READ,ZohoCalendar.calendar.ALL,ZohoCalendar.event.ALL,WorkDrive.files.ALL</code>
                                    </li>
                                    <li>Copy the <em>code</em>, then open a browser or Postman and call:
                                        <code style="display:block;margin-top:4px;white-space:pre-wrap;">POST https://accounts.zoho.com/oauth/v2/token
grant_type=authorization_code
&amp;client_id=YOUR_CLIENT_ID
&amp;client_secret=YOUR_CLIENT_SECRET
&amp;code=THE_CODE</code>
                                    </li>
                                    <li>Response JSON includes <code>refresh_token</code> — paste that here. Keep it secret.</li>
                                </ol>
                                <p style="margin:10px 0 0;">
                                    <strong>Org ID / Presenter ZUID / Calendar UID / WorkDrive Folder ID</strong> are optional for basic Join-link create.
                                    Org ID: Zoho Meeting admin URL or API. Presenter ZUID: user’s Zoho profile id.
                                    Calendar UID: from Zoho Calendar settings. WorkDrive folder: from folder URL/API.
                                </p>
                            </div>
                        </div>
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
                            <input type="text" name="refresh_token" class="form-control" value="{{ old('refresh_token', '') }}" placeholder="{{ $account->exists ? 'Leave blank to keep current' : 'From Self Client code exchange' }}" @required(!$account->exists)>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Org ID <span class="text-muted">(optional)</span></label>
                            <input type="text" name="org_id" class="form-control" value="{{ old('org_id', $credentials['org_id'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Presenter ZUID <span class="text-muted">(optional)</span></label>
                            <input type="text" name="presenter_zuid" class="form-control" value="{{ old('presenter_zuid', $credentials['presenter_zuid'] ?? '') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Calendar UID <span class="text-muted">(optional)</span></label>
                            <input type="text" name="calendar_uid" class="form-control" value="{{ old('calendar_uid', $credentials['calendar_uid'] ?? '') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>WorkDrive Folder ID <span class="text-muted">(optional)</span></label>
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
