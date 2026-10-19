@extends(lms_portal_layout())
@section('title', 'ALL TESTS')
@include('admin.assessments.partials.searchable-select-assets')
@include('admin.assessments.partials.sortable-table-assets')
@section('content')
<style>
    .wrapper.wrapper-content {
        padding-left: 10px;
        padding-right: 10px;
    }
    .ibox-content.lms-tests-wrap {
        width: 100%;
        overflow-x: hidden;
    }
    .lms-create-tests-row {
        display: flex;
        flex-wrap: nowrap;
        gap: 8px;
        align-items: stretch;
        width: 100%;
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
    .lms-tests-wrap { overflow-x: hidden; width: 100%; }
    .lms-tests-table {
        width: 100% !important;
        max-width: 100%;
        table-layout: fixed;
        margin-bottom: 0;
        font-size: 12px;
    }
    .lms-tests-table > thead > tr > th,
    .lms-tests-table > tbody > tr > td {
        vertical-align: middle;
        padding: 6px 4px;
        word-wrap: break-word;
        overflow-wrap: anywhere;
        white-space: normal;
    }
    .lms-tests-table .col-date { width: 7%; }
    .lms-tests-table .col-id { width: 5%; }
    .lms-tests-table .col-title { width: 12%; }
    .lms-tests-table .col-course { width: 11%; }
    .lms-tests-table .col-type { width: 7%; }
    .lms-tests-table .col-num { width: 4%; text-align: center; }
    .lms-tests-table .col-status { width: 7%; }
    .lms-tests-table .col-assessor { width: 10%; }
    .lms-tests-table .col-action {
        width: 25%;
        white-space: normal;
    }
    .lms-tests-table .col-action .btn,
    .lms-tests-table .col-action form {
        display: inline-block;
        margin: 1px 1px 2px 0;
        vertical-align: middle;
    }
    .lms-tests-table .col-action .btn {
        white-space: nowrap;
        font-weight: 700;
        font-size: 11px;
        padding: 3px 6px;
    }
</style>
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>ALL TESTS</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>ALL TESTS</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        @if(!empty($canAssign))
            <a href="{{ route('admin.assessments.tests.assigned-users') }}" class="btn btn-default btn-sm">Assigned Tests</a>
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
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <p class="text-muted m-b-sm">This list is for creating and editing tests only. Assign users/batches on the Assign page; marking instructor is optional there.</p>
    <div class="ibox"><div class="ibox-content lms-tests-wrap">
        <form method="GET" class="m-b-md" style="margin-bottom:15px;">
            <div class="row">
                <div class="col-sm-3">
                    <select name="kind" class="form-control lms-searchable" data-placeholder="Type to find type">
                        <option value="">All types</option>
                        @foreach($kinds as $k => $label)
                            <option value="{{ $k }}" @selected($kind === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-5">
                    <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Search ID / Test Name">
                </div>
                <div class="col-sm-4">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <a href="{{ route('admin.assessments.tests.index') }}" class="btn btn-default">Clear</a>
                </div>
            </div>
        </form>
        <table class="table table-striped table-bordered lms-tests-table js-sortable-table">
            <thead>
                <tr>
                    <th class="col-date" data-sort-col="0">Date <span class="sort-ind"></span></th>
                    <th class="col-id" data-sort-col="1">Test ID <span class="sort-ind"></span></th>
                    <th class="col-title" data-sort-col="2">Test Name <span class="sort-ind"></span></th>
                    <th class="col-course" data-sort-col="3">Course / Test for <span class="sort-ind"></span></th>
                    <th class="col-type" data-sort-col="4">Test Type <span class="sort-ind"></span></th>
                    <th class="col-num" data-sort-col="5">Qs <span class="sort-ind"></span></th>
                    <th class="col-num" data-sort-col="6">Assigned <span class="sort-ind"></span></th>
                    <th class="col-num" data-sort-col="7" title="How many sittings students have started or submitted">Taken <span class="sort-ind"></span></th>
                    <th class="col-status" data-sort-col="8">Status <span class="sort-ind"></span></th>
                    <th class="col-assessor" data-sort-col="9">Assessor <span class="sort-ind"></span></th>
                    <th class="col-action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $markers = $row->assignments->pluck('marker')->filter()->unique('id');
                    @endphp
                    <tr>
                        <td class="col-date" data-sort="{{ optional($row->created_at)->timestamp ?? 0 }}">{{ optional($row->created_at)->format('d/m/y H:i') ?? '—' }}</td>
                        <td class="col-id" data-sort="{{ $row->id }}">{{ $row->displayCode() }}</td>
                        <td class="col-title" data-sort="{{ $row->title }}">{{ $row->title }}</td>
                        <td class="col-course" data-sort="{{ $row->course->title ?? $row->targetRoleLabel() }}">
                            @if($row->course_id && $row->course)
                                @if(!empty($row->course->slug))
                                    <a href="{{ route('course.details', ['course' => $row->course->slug]) }}" target="_blank" rel="noopener noreferrer">{{ $row->course->title }}</a>
                                @else
                                    <a href="{{ route('course.show', $row->course_id) }}" target="_blank" rel="noopener noreferrer">{{ $row->course->title }}</a>
                                @endif
                            @else
                                {{ $row->targetRoleLabel() }}
                            @endif
                        </td>
                        <td class="col-type" data-sort="{{ $row->kindLabel() }}">{{ $row->kindLabel() }}</td>
                        <td class="col-num" data-sort="{{ $row->questions_count }}">{{ $row->questions_count }}</td>
                        <td class="col-num" data-sort="{{ $row->assignments_count }}">{{ $row->assignments_count }}</td>
                        <td class="col-num" data-sort="{{ $row->attempts_count }}">{{ $row->attempts_count }}</td>
                        <td class="col-status" data-sort="{{ $row->status }}">
                            @php
                                $st = (string) ($row->status ?? '');
                                $stLabel = match ($st) {
                                    'published' => 'Published',
                                    'disabled' => 'Disabled',
                                    'draft' => 'Draft',
                                    default => ucfirst($st ?: '—'),
                                };
                                $stClass = match ($st) {
                                    'published' => 'text-navy',
                                    'disabled' => 'text-danger',
                                    default => 'text-muted',
                                };
                            @endphp
                            <span class="{{ $stClass }}" style="font-weight:700;">{{ $stLabel }}</span>
                        </td>
                        <td class="col-assessor" data-sort="{{ $markers->isNotEmpty() ? $markers->pluck('name')->implode(', ') : 'Admin' }}">
                            @if($markers->isNotEmpty())
                                @foreach($markers as $marker)
                                    @if(!$loop->first), @endif
                                    <a href="#" class="lms-user-preview" data-user-id="{{ $marker->id }}">{{ $marker->name }}</a>
                                @endforeach
                            @else
                                <span class="text-muted">Admin / course instructors</span>
                            @endif
                        </td>
                        <td class="col-action">
                            @php
                                $canManageRow = app(\App\Services\LmsAssessmentService::class)->canManageSittingsFor($row);
                            @endphp
                            @if($canManageRow)
                                <a class="btn btn-xs btn-primary" href="{{ route('admin.assessments.tests.edit', $row->id) }}">Edit</a>
                                <a class="btn btn-xs btn-info" href="{{ route('admin.assessments.tests.assign', $row->id) }}">Assign Test</a>
                                <a class="btn btn-xs btn-default" href="{{ route('admin.assessments.tests.assign', ['id' => $row->id, 'mode' => 'self']) }}">Assign Self test</a>
                            @endif
                            <a class="btn btn-xs btn-warning" href="{{ route('admin.assessments.tests.attempts', $row->id) }}">Results</a>
                            @if(!empty($canDelete))
                                <form action="{{ route('admin.assessments.tests.destroy', $row->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this test?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="text-center">No assessments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $rows->links() }}
    </div></div>
</div>
@include('admin.assessments.partials.user-preview-modal')
@endsection
