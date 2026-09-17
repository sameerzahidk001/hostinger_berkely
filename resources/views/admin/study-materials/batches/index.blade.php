@extends('admin.layout.app')
@section('title', 'Batches')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Batches</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('admin.home') }}">Home</a></li>
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
            <p class="help-block">
                Admin creates batches (unique code), assigns Head of Faculty + instructors, and students.
                Instructors create schedules using an assigned batch from the dropdown.
                Use <strong>Link legacy schedules</strong> once if older sessions still have only a batch name.
            </p>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Batch</th>
                        <th>Course</th>
                        <th>Head of Faculty</th>
                        <th>Instructors</th>
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
                            <td>{{ $batch->name }}</td>
                            <td>{{ $batch->course->title ?? '—' }}</td>
                            <td>{{ $batch->headOfFaculty->name ?? '—' }}</td>
                            <td>{{ $batch->instructors->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td>{{ $batch->students_count }}</td>
                            <td>{{ $batch->schedules_count }}</td>
                            <td>{{ ucfirst($batch->status) }}</td>
                            <td>
                                <a class="btn btn-xs btn-default" href="{{ route('admin.class-batches.edit', $batch->id) }}">
                                    {{ $isAdmin ? 'Edit' : 'View' }}
                                </a>
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.class-schedules.create', ['batch_id' => $batch->id]) }}">Add schedule</a>
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
