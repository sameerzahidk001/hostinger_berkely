@extends(lms_portal_layout())
@section('title', 'Assigned Tests')
@section('content')
{{-- Legacy Test Results report retired; controller redirects here as a safety net. --}}
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>Moved to Assigned Tests</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.assessments.tests.index') }}">ALL TESTS</a></li>
            <li class="active"><strong>Assigned Tests</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox"><div class="ibox-content">
        <p>Test Results now live under <strong>Assigned Tests</strong> (same columns as Results: Appointment ID, Attempt under Test, Timing, Status).</p>
        <a href="{{ route('admin.assessments.tests.assigned-users', request()->query()) }}" class="btn btn-primary">Go to Assigned Tests</a>
        <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">ALL TESTS</a>
    </div></div>
</div>
@endsection
