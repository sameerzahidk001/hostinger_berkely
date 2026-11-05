@extends('admin.layout.app')
@section('title', 'Certificates')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Certificates</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Certificates</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.certificates.completions.index') }}" class="btn btn-white">Completions</a>
        <a href="{{ route('admin.certificates.completions.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Mark complete</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    <div class="ibox">
        <div class="ibox-title"><h5>Issued certificates</h5></div>
        <div class="ibox-content">
            <p class="text-muted">Public verify page: <a href="{{ url('/verify-certificate') }}" target="_blank" rel="noopener">/verify-certificate</a></p>
            <form method="GET" class="form-group">
                <div class="row">
                    <div class="col-sm-5">
                        <input type="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Code, student, course">
                    </div>
                    <div class="col-sm-3">
                        <select name="status" class="form-control">
                            <option value="">All statuses</option>
                            <option value="issued" @selected($filters['status'] === 'issued')>Issued</option>
                            <option value="revoked" @selected($filters['status'] === 'revoked')>Revoked</option>
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
                            <th>Code</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Issued</th>
                            <th>Status</th>
                            <th style="width:220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>
                                    <code>{{ $row->certificate_code }}</code>
                                    <div class="text-muted small"><a href="{{ $row->verifyUrl() }}" target="_blank" rel="noopener">Verify link</a></div>
                                </td>
                                <td>{{ $row->student_name }}</td>
                                <td>{{ $row->course_title }}</td>
                                <td>{{ optional($row->issued_at)->format('d M Y H:i') ?: '—' }}</td>
                                <td>
                                    @if($row->isRevoked())
                                        <span class="label label-danger">Revoked</span>
                                    @else
                                        <span class="label label-primary">Issued</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn btn-xs btn-info" href="{{ route('admin.certificates.download', $row->id) }}"><i class="fa fa-download"></i> PDF</a>
                                    @if(! $row->isRevoked())
                                        <form method="POST" action="{{ route('admin.certificates.revoke', $row->id) }}" style="display:inline;" onsubmit="return confirm('Revoke this certificate?');">
                                            @csrf
                                            <button type="submit" class="btn btn-xs btn-danger">Revoke</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No certificates issued yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
