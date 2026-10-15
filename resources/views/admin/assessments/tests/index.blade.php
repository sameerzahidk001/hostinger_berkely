@extends(lms_portal_layout())
@section('title', 'Assessments')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Assessments</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Practice / Mock / Tests</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        @if(!empty($canCreate))
            <a href="{{ route('admin.assessments.tests.create', ['kind' => 'practice']) }}" class="btn btn-primary btn-sm">Practice</a>
            <a href="{{ route('admin.assessments.tests.create', ['kind' => 'mock']) }}" class="btn btn-primary btn-sm">Mock</a>
            <a href="{{ route('admin.assessments.tests.create', ['kind' => 'general']) }}" class="btn btn-default btn-sm">General</a>
            @if(!empty($canSkill))
                <a href="{{ route('admin.assessments.tests.create', ['kind' => 'skill']) }}" class="btn btn-warning btn-sm">Skill test</a>
            @endif
        @endif
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="ibox"><div class="ibox-content table-responsive">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-sm-3">
                    <select name="kind" class="form-control">
                        <option value="">All kinds</option>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected($kind === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-5">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search title">
                </div>
                <div class="col-sm-4">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">Clear</a>
                </div>
            </div>
        </form>
        <table class="table table-striped table-bordered">
            <thead>
                <tr><th>ID</th><th>Title</th><th>Kind</th><th>Status</th><th>Questions</th><th>Assignments</th><th>Attempts</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->title }}</td>
                        <td>{{ $row->kindLabel() }}</td>
                        <td>{{ ucfirst($row->status) }}</td>
                        <td>{{ $row->questions_count }}</td>
                        <td>{{ $row->assignments_count }}</td>
                        <td>{{ $row->attempts_count }}</td>
                        <td style="white-space:nowrap;">
                            <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.edit', $row->id) }}">Edit</a>
                            <a class="btn btn-xs btn-default" href="{{ route('admin.assessments.tests.attempts', $row->id) }}">Attempts</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">No assessments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@endsection
