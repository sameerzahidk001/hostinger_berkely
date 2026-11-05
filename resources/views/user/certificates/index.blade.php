@extends('user.layout.app')
@section('title', 'My Certificates')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-10">
        <h2>My Certificates</h2>
        <ol class="breadcrumb">
            <li><a href="{{ route('user.home') }}">Home</a></li>
            <li class="active"><strong>My Certificates</strong></li>
        </ol>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif

    <div class="ibox">
        <div class="ibox-title"><h5>Issued certificates</h5></div>
        <div class="ibox-content">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Course</th>
                            <th>Issued</th>
                            <th>Status</th>
                            <th style="width:220px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($certificates as $row)
                            <tr>
                                <td><code>{{ $row->certificate_code }}</code></td>
                                <td>{{ $row->course_title }}</td>
                                <td>{{ optional($row->issued_at)->format('d M Y') ?: '—' }}</td>
                                <td>
                                    @if($row->isRevoked())
                                        <span class="label label-danger">Revoked</span>
                                    @else
                                        <span class="label label-primary">Issued</span>
                                    @endif
                                </td>
                                <td>
                                    @if(! $row->isRevoked())
                                        <a class="btn btn-xs btn-info" href="{{ route('user.certificates.download', $row->id) }}"><i class="fa fa-download"></i> PDF</a>
                                        <a class="btn btn-xs btn-white" href="{{ $row->verifyUrl() }}" target="_blank" rel="noopener">Verify</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No certificates issued yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $certificates->links() }}
        </div>
    </div>

    @if($pending->isNotEmpty())
    <div class="ibox">
        <div class="ibox-title"><h5>Pending certificate</h5></div>
        <div class="ibox-content">
            <p class="text-muted">These completions are marked, but the certificate PDF is not issued yet.</p>
            <ul class="m-b-none">
                @foreach($pending as $row)
                    <li>{{ $row->course->title ?? ('Course #'.$row->course_id) }} — completed {{ optional($row->completed_at)->format('d M Y') ?: '—' }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
</div>
@endsection
