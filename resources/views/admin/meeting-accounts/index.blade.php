@extends('admin.layout.app')
@section('title', 'Meeting Accounts')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Meeting Accounts</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Meeting Accounts</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.meeting-accounts.create', ['provider' => 'zoho']) }}" class="btn btn-primary">Add Zoho account</a>
        <a href="{{ route('admin.meeting-accounts.create', ['provider' => 'zoom']) }}" class="btn btn-default">Add Zoom account</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    @php
        $hasZoom = collect($accounts)->contains(fn ($a) => $a->provider === 'zoom' && $a->is_active);
        $hasZoho = collect($accounts)->contains(fn ($a) => $a->provider === 'zoho' && $a->is_active);
    @endphp

    @unless($hasZoom)
        <div class="alert alert-info">
            <strong>Zoom not set up yet.</strong>
            Click <strong>Add Zoom account</strong>, enter a label (credentials optional), then on Class Schedule
            choose that Zoom account and <strong>paste the Zoom Join link manually</strong>.
        </div>
    @endunless
    @unless($hasZoho)
        <div class="alert alert-warning">
            <strong>No active Zoho account.</strong>
            Add a Zoho account (Client ID, Secret, Refresh Token) so schedules can <strong>auto-create</strong> Join links.
        </div>
    @endunless

    <div class="ibox">
        <div class="ibox-title"><h5>Zoho &amp; Zoom accounts for Class Schedule</h5></div>
        <div class="ibox-content table-responsive">
            <p class="help-block">
                On Class Schedule: pick a meeting account.
                <strong>Zoho</strong> — Join link is created automatically.
                <strong>Zoom</strong> — paste the Join link manually (no auto-create).
            </p>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Label</th>
                        <th>Host email</th>
                        <th>Default</th>
                        <th>Active</th>
                        <th>Ready</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        <tr>
                            <td><strong>{{ strtoupper($account->provider) }}</strong></td>
                            <td>{{ $account->label }}</td>
                            <td>{{ $account->host_email ?: '—' }}</td>
                            <td>{{ $account->is_default ? 'Yes' : '—' }}</td>
                            <td>
                                <span class="label {{ $account->is_active ? 'label-primary' : 'label-default' }}">
                                    {{ $account->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                @if($account->isZoom())
                                    <span class="label label-primary">Manual Join link</span>
                                @elseif($account->hasRequiredCredentials())
                                    <span class="label label-primary">Credentials OK</span>
                                @else
                                    <span class="label label-warning">Missing credentials</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.meeting-accounts.edit', $account->id) }}">Edit</a>
                                <form action="{{ route('admin.meeting-accounts.destroy', $account->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this meeting account?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No meeting accounts yet. Add a Zoho or Zoom account.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
