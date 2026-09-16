@extends('admin.layout.app')
@section('title', 'Zoho LMS Settings')

@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10"><h2>Zoho Meeting / Calendar / WorkDrive</h2></div>
</div>

<div class="wrapper wrapper-content">
    @include('admin.layout.partials.flash_messages')

    <div class="ibox">
        <div class="ibox-title"><h5>Connection status</h5></div>
        <div class="ibox-content">
            <p><strong>Host account:</strong> {{ $status['account_email'] ?: 'bdm@berkeleyme.com' }}</p>
            <p><strong>OAuth configured:</strong> {{ !empty($status['configured']) ? 'yes' : 'no' }}</p>
            @if(!empty($status['connected_email']))
                <p><strong>Connected as:</strong> {{ $status['connected_email'] }}</p>
            @endif
            @if(!empty($status['org_id']))
                <p><strong>Meeting org:</strong> {{ $status['org_id'] }}</p>
            @endif
            @if(!empty($status['error']))
                <div class="alert alert-warning">{{ $status['error'] }}</div>
            @elseif(!empty($status['configured']))
                <div class="alert alert-success">Meeting API ready. New class schedules will get a Join Zoho link automatically.</div>
            @else
                <div class="alert alert-danger">OAuth not connected — paste Self Client tokens below (from api-console.zoho.com as bdm@berkeleyme.com).</div>
            @endif
        </div>
    </div>

    <div class="ibox">
        <div class="ibox-title"><h5>Save OAuth credentials</h5></div>
        <div class="ibox-content">
            <form method="POST" action="{{ route('admin.zoho.settings.save') }}">
                @csrf
                @foreach([
                    'ZOHO_ACCOUNT_EMAIL' => 'Account email',
                    'ZOHO_CLIENT_ID' => 'Client ID',
                    'ZOHO_CLIENT_SECRET' => 'Client Secret',
                    'ZOHO_REFRESH_TOKEN' => 'Refresh Token',
                    'ZOHO_ORG_ID' => 'Org ID (zsoid)',
                    'ZOHO_PRESENTER_ZUID' => 'Presenter ZUID',
                    'ZOHO_WORKDRIVE_FOLDER_ID' => 'WorkDrive folder ID',
                    'ZOHO_CALENDAR_UID' => 'Calendar UID',
                    'ZOHO_TIMEZONE' => 'Timezone',
                ] as $key => $label)
                    <div class="form-group">
                        <label>{{ $label }}</label>
                        <input type="text" name="{{ $key }}" class="form-control"
                               value="{{ old($key, $values[$key] ?? '') }}"
                               @if(in_array($key, ['ZOHO_ACCOUNT_EMAIL','ZOHO_CLIENT_ID','ZOHO_CLIENT_SECRET','ZOHO_REFRESH_TOKEN'], true)) required @endif>
                    </div>
                @endforeach
                <button type="submit" class="btn btn-primary">Save &amp; test connection</button>
                <a href="{{ route('admin.class-schedules.index') }}" class="btn btn-default">Back to Class Schedule</a>
            </form>
        </div>
    </div>
</div>
@endsection
