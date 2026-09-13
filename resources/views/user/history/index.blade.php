@extends('user.layout.app')
@section('title', 'History')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>History</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Dashboard</a></li>
            <li class="active"><strong>History</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox">
        <div class="ibox-title"><h5>Your activity</h5></div>
        <div class="ibox-content">
            <form method="GET" action="{{ route('user.history') }}" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-sm-4 col-md-3">
                        <label>From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <label>To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-sm-4 col-md-3" style="padding-top:24px;">
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="{{ route('user.history') }}" class="btn btn-default">Clear</a>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Date &amp; Time</th>
                            <th>Activity</th>
                            <th>Item</th>
                            <th>Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            <tr>
                                <td>{{ $activity->created_at?->timezone(config('app.timezone'))->format('d M Y g:i A') }}</td>
                                <td>{{ str_replace(' Logout', ' Log out', (string) $activity->action) }}</td>
                                <td>{{ $activity->item ?: '—' }}</td>
                                <td>
                                    @if(!empty($activity->url))
                                        <a href="{{ $activity->url }}" target="_blank" rel="noopener">Open</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">No activity recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $activities->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
