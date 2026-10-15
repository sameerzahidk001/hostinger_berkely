@extends(lms_portal_layout())
@section('title', 'Question Bank')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Question Bank</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>QBANK</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.assessments.questions.create') }}" class="btn btn-primary">Add question</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="ibox">
        <div class="ibox-content table-responsive">
            <form method="GET" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-sm-3">
                        <select name="type" class="form-control">
                            <option value="">All types</option>
                            @foreach($types as $k => $label)
                                <option value="{{ $k }}" @selected($type === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-5">
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search stem or topic">
                    </div>
                    <div class="col-sm-4">
                        <button class="btn btn-primary" type="submit">Filter</button>
                        <a href="{{ route('admin.assessments.questions.index') }}" class="btn btn-default">Clear</a>
                    </div>
                </div>
            </form>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Course / For</th>
                        <th>Type</th>
                        <th>Topic</th>
                        <th>Stem</th>
                        <th>Marks</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->id }}</td>
                            <td>{{ optional($row->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>{{ $row->targetRoleLabel() }}</td>
                            <td>{{ $row->typeLabel() }}</td>
                            <td>{{ $row->topic ?: '—' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit(strip_tags($row->stem), 100) }}</td>
                            <td>{{ $row->max_marks }}</td>
                            <td>{{ ucfirst($row->status) }}</td>
                            <td>
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.questions.edit', $row->id) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center">No questions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
