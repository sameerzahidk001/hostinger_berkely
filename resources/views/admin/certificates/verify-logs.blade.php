@extends('admin.layout.app')
@section('title', 'Certificate verify log')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-8">
        <h2>Certificate verify log</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li><a href="{{ route('admin.certificates.index') }}">Certificates</a></li>
            <li class="active"><strong>Verify log</strong></li>
        </ol>
    </div>
    <div class="col-lg-4 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.certificates.index') }}" class="btn btn-white">Certificates</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    <div class="ibox">
        <div class="ibox-title"><h5>Public verification attempts</h5></div>
        <div class="ibox-content">
            <form method="GET" class="form-group">
                <div class="row">
                    <div class="col-sm-5">
                        <input type="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Certificate code">
                    </div>
                    <div class="col-sm-3">
                        <select name="result" class="form-control">
                            <option value="">All results</option>
                            <option value="valid" @selected($filters['result'] === 'valid')>Valid</option>
                            <option value="revoked" @selected($filters['result'] === 'revoked')>Revoked</option>
                            <option value="not_found" @selected($filters['result'] === 'not_found')>Not found</option>
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
                            <th>When</th>
                            <th>Code</th>
                            <th>Result</th>
                            <th>Verifier</th>
                            <th>Organisation</th>
                            <th>Reason</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ optional($row->created_at)->format('d M Y H:i') ?: '—' }}</td>
                                <td><code>{{ $row->certificate_code ?: '—' }}</code></td>
                                <td>
                                    @if($row->result === 'valid')
                                        <span class="label label-primary">Valid</span>
                                    @elseif($row->result === 'revoked')
                                        <span class="label label-danger">Revoked</span>
                                    @elseif($row->result === 'not_found')
                                        <span class="label label-warning">Not found</span>
                                    @else
                                        {{ $row->result ?: '—' }}
                                    @endif
                                </td>
                                <td>
                                    {{ $row->verifier_name ?: '—' }}
                                    <div class="text-muted small">{{ $row->verifier_email ?: '' }}</div>
                                    <div class="text-muted small">{{ $row->verifier_phone ?: '' }}</div>
                                </td>
                                <td>
                                    {{ $row->verifier_organization ?: '—' }}
                                    <div class="text-muted small">{{ $row->verifier_position ?: '' }}</div>
                                </td>
                                <td class="small">{{ $row->verifier_reason ?: '—' }}</td>
                                <td>
                                    {{ $row->ip ?: '—' }}
                                    @php
                                        $city = trim((string) ($row->ip_city ?? ''));
                                        $country = trim((string) ($row->ip_country ?? ''));
                                        $place = trim(implode(', ', array_filter([$city, $country])));
                                    @endphp
                                    @if($place !== '')
                                        <div class="text-muted small">{{ $place }}</div>
                                    @else
                                        <div class="text-muted small">City / country from IP not available</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No verification attempts logged yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
