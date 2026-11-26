@extends('admin.layout.app')
@section('title', 'Events Hub')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Events Hub</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
            <li class="active"><strong>Events</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox">
        <div class="ibox-content">
            <p>
                Events uses the same path-group pattern as the Academic Journal:
                logo/menu via <a href="{{ route('admin.path-branding.index') }}">Path Branding</a>,
                signup/login at <code>/events/login</code> and <code>/events/register</code>,
                and CMS pages under <code>/events</code>.
            </p>
            @if($branding)
                <ul>
                    <li>Path prefix: <code>/{{ $branding->path_prefix }}</code></li>
                    <li>Menu group: <code>{{ $branding->menu_group ?: '—' }}</code></li>
                    <li>Status: {{ $branding->is_active ? 'Active' : 'Off' }}</li>
                </ul>
                <p>
                    <a class="btn btn-primary" href="{{ route('admin.path-branding.edit', $branding->id) }}">Edit Events branding</a>
                    <a class="btn btn-white" href="{{ url('/events') }}" target="_blank">Open /events</a>
                </p>
            @else
                <div class="alert alert-warning">Events path branding not found yet. Open Path Branding and add prefix <code>events</code>.</div>
            @endif
            <p class="text-muted">
                Full Events product (listings, registrations, tickets) ships after Journal admin is stable.
                This hub is intentionally lightweight so LMS features are not disrupted.
            </p>
        </div>
    </div>
</div>
@endsection
