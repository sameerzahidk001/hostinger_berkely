@extends(lms_portal_layout())
@section('title', 'Assessments')
@section('content')
<style>
    .lms-create-tests-row {
        display: flex;
        flex-wrap: nowrap;
        gap: 8px;
        align-items: stretch;
        margin: 8px 0 4px;
        overflow-x: auto;
    }
    .lms-create-tests-row .btn {
        flex: 1 1 0;
        white-space: nowrap;
        font-size: 12px;
        padding: 8px 10px;
        border: none;
        color: #fff !important;
    }
    .lms-btn-practice { background: #d9534f; }
    .lms-btn-practice:hover, .lms-btn-practice:focus { background: #c9302c; color: #fff !important; }
    .lms-btn-mock { background: #337ab7; }
    .lms-btn-mock:hover, .lms-btn-mock:focus { background: #286090; color: #fff !important; }
    .lms-btn-general { background: #f0ad4e; }
    .lms-btn-general:hover, .lms-btn-general:focus { background: #ec971f; color: #fff !important; }
    .lms-btn-skill { background: #5cb85c; }
    .lms-btn-skill:hover, .lms-btn-skill:focus { background: #449d44; color: #fff !important; }
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Assessments</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Practice / Mock / Tests</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        @if(!empty($canAssign))
            <a href="{{ route('admin.assessments.tests.assigned-users') }}" class="btn btn-default btn-sm">Assigned users</a>
        @endif
    </div>
    @if(!empty($canCreate))
    <div class="col-lg-12">
        <div class="lms-create-tests-row">
            <a href="{{ route('admin.assessments.tests.create', ['kind' => 'practice']) }}" class="btn btn-sm lms-btn-practice">Create New Practice Test</a>
            <a href="{{ route('admin.assessments.tests.create', ['kind' => 'mock']) }}" class="btn btn-sm lms-btn-mock">Create New Mock Test</a>
            <a href="{{ route('admin.assessments.tests.create', ['kind' => 'general']) }}" class="btn btn-sm lms-btn-general">Create New General Test</a>
            @if(!empty($canSkill))
                <a href="{{ route('admin.assessments.tests.create', ['kind' => 'skill']) }}" class="btn btn-sm lms-btn-skill">Create New Skill Test</a>
            @endif
        </div>
    </div>
    @endif
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <p class="text-muted m-b-sm">This list is for creating and editing tests only. Assign users/batches on the Assign page; marking instructor is optional there.</p>
    <div class="ibox"><div class="ibox-content table-responsive">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-sm-3">
                    <select name="kind" class="form-control">
                        <option value="">All types</option>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected($kind === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-5">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search ID / title">
                </div>
                <div class="col-sm-4">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">Clear</a>
                </div>
            </div>
        </form>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Test ID</th>
                    <th>Title</th>
                    <th>Course / Test for</th>
                    <th>Test Type</th>
                    <th>Questions</th>
                    <th>Assignments</th>
                    <th title="How many times students have started or submitted this test">Attempts</th>
                    <th>Assessor</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $markers = $row->assignments->pluck('marker')->filter()->unique('id');
                    @endphp
                    <tr>
                        <td>{{ optional($row->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $row->displayCode() }}</td>
                        <td>{{ $row->title }}</td>
                        <td>{{ $row->targetRoleLabel() }}</td>
                        <td>{{ $row->kindLabel() }}</td>
                        <td>{{ $row->questions_count }}</td>
                        <td>{{ $row->assignments_count }}</td>
                        <td>{{ $row->attempts_count }}</td>
                        <td>
                            @if($markers->isNotEmpty())
                                {{ $markers->pluck('name')->implode(', ') }}
                            @else
                                <span class="text-muted">Admin / course instructors</span>
                            @endif
                        </td>
                        <td style="white-space:nowrap;">
                            <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.edit', $row->id) }}">Edit</a>
                            @if(!empty($canAssign))
                                <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.assign', $row->id) }}">Assign</a>
                            @endif
                            <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.attempts', $row->id) }}">Attempts</a>
                            @if(!empty($canDelete))
                                <form action="{{ route('admin.assessments.tests.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this test?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger" title="Delete"><i class="fa fa-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center">No assessments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@endsection
