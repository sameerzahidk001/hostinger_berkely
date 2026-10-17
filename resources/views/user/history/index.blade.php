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
        <div class="ibox-title"><h5>{{ !empty($seeAll) ? 'Activity history' : 'Your activity' }}</h5></div>
        <div class="ibox-content">
            <p class="text-muted" style="margin:0 0 10px;">
                <strong>Time zone:</strong>
                {{ function_exists('viewer_timezone') ? viewer_timezone() : config('app.timezone') }}
                @if(function_exists('viewer_timezone_label'))
                    ({{ viewer_timezone_label() }})
                @endif
                — based on your location. Date &amp; Time values below use this zone.
            </p>
            <form method="GET" action="{{ route('user.history') }}" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    @if(!empty($seeAll))
                        <div class="col-sm-4 col-md-3">
                            <label>User</label>
                            <select name="user_id" class="form-control">
                                <option value="">All users</option>
                                @foreach($filterUsers ?? [] as $u)
                                    <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>
                                        {{ $u->name }} ({{ $u->email }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
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
                            <th>User</th>
                            <th>Activity</th>
                            <th>Item</th>
                            <th>Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                            @php
                                $actorUser = $activity->user;
                                $actorAdmin = $activity->admin ?? null;
                                $actorName = panel_actor_display_name(
                                    $actorUser->name ?? $actorAdmin->name ?? Auth::user()->name ?? null,
                                    $actorUser->email ?? $actorAdmin->email ?? Auth::user()->email ?? null,
                                    $actorUser->username ?? $actorAdmin->username ?? Auth::user()->username ?? null
                                );
                                $actionLabel = activity_action_label($activity->action);
                            @endphp
                            <tr>
                                <td>{{ function_exists('format_in_viewer_tz') ? format_in_viewer_tz($activity->created_at) : $activity->created_at?->format('d M Y g:i A') }}</td>
                                <td>{{ $actorName }}</td>
                                <td>
                                    @if(!empty($activity->url))
                                        <a href="{{ $activity->url }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $actionLabel }}</a>
                                    @else
                                        {{ $actionLabel }}
                                    @endif
                                </td>
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
                                <td colspan="5" class="text-center text-muted">No activity recorded yet.</td>
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
