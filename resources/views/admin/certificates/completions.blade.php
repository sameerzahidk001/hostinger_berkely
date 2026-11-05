@extends('admin.layout.app')
@section('title', 'Completions')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Completions</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Certificates</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
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
                        <input type="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Student, course, notes">
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
                            <th>Course</th>
                            <th>Completed</th>
                            <th>Status</th>
                            <th>Marked by</th>
                            <th style="width:160px;">Action</th>
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
                            @endphp
                            <tr>
                                <td>
                                    {{ $studentLabel }}
                                    <div class="text-muted small">{{ $student->email ?? '' }}</div>
                                </td>
                                <td>{{ $row->course->title ?? ('#'.$row->course_id) }}</td>
                                <td>{{ optional($row->completed_at)->format('d M Y H:i') ?: '—' }}</td>
                                <td>
                                    @if($row->status === 'certificate_issued')
                                        <span class="label label-primary">Certificate issued</span>
                                    @else
                                        <span class="label label-success">Completed</span>
                                    @endif
                                </td>
                                <td>{{ $row->marked_by_name ?: '—' }}</td>
                                <td>
                                    @if($row->certificate)
                                        <a class="btn btn-xs btn-white" href="{{ route('admin.certificates.download', $row->certificate->id) }}">PDF</a>
                                    @else
                                        <form method="POST" action="{{ route('admin.certificates.completions.issue', $row->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-primary">Issue certificate</button>
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
