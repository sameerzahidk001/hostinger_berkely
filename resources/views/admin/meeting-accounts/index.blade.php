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

    <div class="ibox">
        <div class="ibox-title"><h5>Zoho &amp; Zoom accounts for Class Schedule</h5></div>
        <div class="ibox-content table-responsive">
            <p class="help-block">When creating a Class Schedule, Admin/Instructor picks one of these accounts. The Join link is created with that account.</p>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Label</th>
                        <th>Host email</th>
                        <th>Default</th>
                        <th>Active</th>
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
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.meeting-accounts.edit', $account->id) }}">Edit</a>
                                <form action="{{ route('admin.meeting-accounts.destroy', $account->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this meeting account?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No meeting accounts yet. Add a Zoho or Zoom account.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
