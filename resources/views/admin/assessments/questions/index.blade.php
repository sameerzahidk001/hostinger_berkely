@extends(lms_portal_layout())
@section('title', 'Question Bank')
@include('admin.assessments.partials.searchable-select-assets')
@include('admin.assessments.partials.sortable-table-assets')
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
        <a href="{{ route('admin.assessments.questions.import') }}" class="btn btn-success">Import MCQ (Excel/CSV)</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    <div class="ibox">
        <div class="ibox-content table-responsive">
            <form method="GET" class="m-b-md" style="margin-bottom:15px;">
                <div class="row">
                    <div class="col-sm-3">
                        <select name="type" class="form-control lms-searchable" data-placeholder="Type to find type">
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
            <table class="table table-striped table-bordered js-sortable-table">
                <thead>
                    <tr>
                        <th data-sort-col="0">Date <span class="sort-ind"></span></th>
                        <th data-sort-col="1">ID <span class="sort-ind"></span></th>
                        <th data-sort-col="2">Created by <span class="sort-ind"></span></th>
                        <th data-sort-col="3">Course <span class="sort-ind"></span></th>
                        <th data-sort-col="4">Question for <span class="sort-ind"></span></th>
                        <th data-sort-col="5">Type <span class="sort-ind"></span></th>
                        <th data-sort-col="6">Topic <span class="sort-ind"></span></th>
                        <th data-sort-col="7">Stem <span class="sort-ind"></span></th>
                        <th data-sort-col="8">Marks <span class="sort-ind"></span></th>
                        <th data-sort-col="9">Status <span class="sort-ind"></span></th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td data-sort="{{ optional($row->created_at)->timestamp ?? 0 }}">{{ optional($row->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                            <td data-sort="{{ $row->id }}">{{ $row->displayCode() }}</td>
                            <td data-sort="{{ $row->createdByLabel() }}">
                                @if($row->created_by_user_id)
                                    <a href="{{ route('users.edit', $row->created_by_user_id) }}">{{ $row->createdByLabel() }}</a>
                                @else
                                    {{ $row->createdByLabel() }}
                                @endif
                            </td>
                            <td data-sort="{{ $row->course->title ?? '' }}">
                                @if($row->course_id && $row->course)
                                    <a href="{{ route('course.edit', $row->course_id) }}">{{ $row->course->title }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td data-sort="{{ $row->target_role ?: '' }}">{{ $row->targetRoleLabel() }}</td>
                            <td data-sort="{{ $row->typeLabel() }}">{{ $row->typeLabel() }}</td>
                            <td data-sort="{{ $row->topic ?: '' }}">{{ $row->topic ?: '—' }}</td>
                            <td data-sort="{{ strip_tags($row->stem) }}">{{ \Illuminate\Support\Str::limit(strip_tags($row->stem), 100) }}</td>
                            <td data-sort="{{ $row->max_marks }}">{{ $row->max_marks }}</td>
                            <td data-sort="{{ $row->status }}">{{ ucfirst($row->status) }}</td>
                            <td>
                                <a class="btn btn-xs btn-primary" style="font-weight:700;" href="{{ route('admin.assessments.questions.edit', $row->id) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center">No questions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
