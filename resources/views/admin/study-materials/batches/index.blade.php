@extends(lms_portal_layout())
@section('title', 'Batches')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Batches</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.class-schedules.index') }}">Class Schedule</a></li>
            <li class="active"><strong>Batches</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.class-schedules.index') }}" class="btn btn-default">Schedules</a>
        @if($isAdmin)
            <form action="{{ route('admin.class-batches.backfill') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-default" onclick="return confirm('Link old schedules (by batch name + course) into Batches?');">
                    Link legacy schedules
                </button>
            </form>
        @endif
        @if($isAdmin || ($lms ?? app(\App\Services\StudyMaterialService::class))->canManageBatch())
            <a href="{{ route('admin.class-batches.create') }}" class="btn btn-primary">Create Batch</a>
        @endif
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>All batches</h5></div>
        <div class="ibox-content table-responsive">
            <form method="GET" action="{{ route('admin.class-batches.index') }}" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-sm-8 col-md-6">
                        <input type="text" name="search" class="form-control" value="{{ $search ?? '' }}" placeholder="Search batch code, name, course or instructor">
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <button type="submit" class="btn btn-primary">Search</button>
                        @if(($search ?? '') !== '')
                            <a href="{{ route('admin.class-batches.index') }}" class="btn btn-default">Clear</a>
                        @endif
                    </div>
                </div>
            </form>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Batch</th>
                        <th>Course</th>
                        <th>Instructor</th>
                        <th>Head of Faculty</th>
                        <th>Students</th>
                        <th>Sessions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td><strong>{{ $batch->code }}</strong></td>
                            <td>
                                <a href="{{ route('admin.class-schedules.batch', $batch->id) }}" style="color:#1c84c6;text-decoration:underline;"><strong>{{ $batch->name }}</strong></a>
                            </td>
                            <td>
                                @if($batch->course)
                                    @include('partials.course_link', ['course' => $batch->course ?? null])
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @forelse($batch->trainerInstructors() as $ins)
                                    <a href="{{ url('/instructor/' . $ins->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $ins->name }}</a>@if(! $loop->last), @endif
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td>
                                @if($batch->headOfFaculty)
                                    <a href="{{ url('/instructor/' . $batch->headOfFaculty->id) }}" target="_blank" rel="noopener" style="color:#1c84c6;text-decoration:underline;">{{ $batch->headOfFaculty->name }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $batch->students_count }}</td>
                            <td>{{ $batch->schedules_count }}</td>
                            <td>
                                <span class="label {{ $batch->status === 'active' ? 'label-primary' : 'label-default' }}">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td>
                                <a class="btn btn-xs btn-default" href="{{ route('admin.class-batches.edit', $batch->id) }}">
                                    {{ ($isAdmin || ($lms ?? app(\App\Services\StudyMaterialService::class))->canManageBatch()) ? 'Edit' : 'View' }}
                                </a>
                                @if($isAdmin || ($lms ?? app(\App\Services\StudyMaterialService::class))->canManageSchedule())
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.class-schedules.create', ['batch_id' => $batch->id]) }}">Add schedule</a>
                                @endif
                                @if($isAdmin)
                                    <form action="{{ route('admin.class-batches.destroy', $batch->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this batch?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No batches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $batches->links() }}
        </div>
    </div>
</div>
@endsection
