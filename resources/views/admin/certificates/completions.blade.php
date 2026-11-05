@extends('admin.layout.app')
@section('title', 'Completions')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-7">
        <h2>Completions</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Certificates</strong></li>
        </ol>
    </div>
    <div class="col-lg-5 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.certificates.templates.index') }}" class="btn btn-white">Templates</a>
        <a href="{{ route('admin.certificates.index') }}" class="btn btn-white">Issued certificates</a>
        <a href="{{ route('admin.certificates.completions.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Mark complete</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    <div class="ibox">
        <div class="ibox-title"><h5>Student completions</h5></div>
        <div class="ibox-content">
            <form method="GET" class="form-group">
                <div class="row">
                    <div class="col-sm-4">
                        <input type="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Student, course, certificate code">
                    </div>
                    <div class="col-sm-3">
                        <select name="course_id" class="form-control">
                            <option value="">All courses</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected((string)$filters['course_id'] === (string)$course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select name="status" class="form-control">
                            <option value="">All statuses</option>
                            <option value="completed" @selected($filters['status'] === 'completed')>Completed</option>
                            <option value="certificate_issued" @selected($filters['status'] === 'certificate_issued')>Certificate issued</option>
                        </select>
                    </div>
                    <div class="col-sm-2">
                        <button class="btn btn-primary btn-block" type="submit">Filter</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course / Batch</th>
                            <th>Completed</th>
                            <th>Completion</th>
                            <th>Certificate</th>
                            <th style="width:260px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $student = $row->user;
                                $studentLabel = trim((string) ($student->name ?? ''));
                                if ($studentLabel === '') {
                                    $studentLabel = trim(($student->first_name ?? '').' '.($student->last_name ?? ''));
                                }
                                if ($studentLabel === '') {
                                    $studentLabel = '#'.$row->user_id;
                                }
                                $certs = $row->certificates ?? collect();
                                $issued = $certs->where('status', 'issued');
                            @endphp
                            <tr>
                                <td>
                                    {{ $studentLabel }}
                                    <div class="text-muted small">{{ $student->email ?? '' }}</div>
                                </td>
                                <td>
                                    {{ $row->course->title ?? ('#'.$row->course_id) }}
                                    @if($row->batch)
                                        <div class="text-muted small">Batch: {{ $row->batch->code ?: $row->batch->name }}</div>
                                    @endif
                                    @if($row->template)
                                        <div class="text-muted small">Template: {{ $row->template->name }}</div>
                                    @endif
                                </td>
                                <td>{{ optional($row->completed_at)->format('d M Y H:i') ?: '—' }}</td>
                                <td>
                                    <span class="label label-success">Completed</span>
                                    <div class="text-muted small" style="margin-top:4px;">{{ $row->marked_by_name ?: '—' }}</div>
                                </td>
                                <td>
                                    @if($issued->isNotEmpty())
                                        <span class="label label-primary">Issued ({{ $issued->count() }})</span>
                                        <ul class="list-unstyled m-t-sm m-b-none">
                                            @foreach($issued as $cert)
                                                <li style="margin-bottom:6px;">
                                                    <code>{{ $cert->certificate_code }}</code>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @elseif($certs->where('status', 'revoked')->isNotEmpty())
                                        <span class="label label-danger">Revoked only</span>
                                    @else
                                        <span class="label label-warning">Not issued</span>
                                    @endif
                                </td>
                                <td>
                                    @forelse($issued as $cert)
                                        <div style="margin-bottom:6px;">
                                            <a class="btn btn-xs btn-white" href="{{ route('admin.certificates.preview', $cert->id) }}" target="_blank" rel="noopener">Preview</a>
                                            <a class="btn btn-xs btn-info" href="{{ route('admin.certificates.download', $cert->id) }}">Download</a>
                                        </div>
                                    @empty
                                        <form method="POST" action="{{ route('admin.certificates.completions.issue', $row->id) }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="send_email" value="1">
                                            @if($row->template_id)
                                                <input type="hidden" name="template_id" value="{{ $row->template_id }}">
                                            @endif
                                            <button type="submit" class="btn btn-xs btn-primary">Issue + email</button>
                                        </form>
                                    @endforelse
                                    @if($issued->isNotEmpty())
                                        <form method="POST" action="{{ route('admin.certificates.completions.issue', $row->id) }}" style="display:inline;margin-top:4px;">
                                            @csrf
                                            <input type="hidden" name="send_email" value="1">
                                            @if($row->template_id)
                                                <input type="hidden" name="template_id" value="{{ $row->template_id }}">
                                            @endif
                                            <button type="submit" class="btn btn-xs btn-warning" title="Issue another certificate for this completion">Issue another</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No completions yet. Use Mark complete to start.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
