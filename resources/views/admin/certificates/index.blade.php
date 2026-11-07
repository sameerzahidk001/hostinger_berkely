@extends('admin.layout.app')
@section('title', 'Certificates')
@section('content')
<div class="row wrapper border-bottom white-bg page-heading">
    <div class="col-lg-7">
        <h2>Certificates</h2>
        <ol class="breadcrumb">
            <li><a href="{{ lms_portal_home() }}">Home</a></li>
            <li class="active"><strong>Certificates</strong></li>
        </ol>
    </div>
    <div class="col-lg-5 text-right" style="padding-top:20px;">
        <a href="{{ route('admin.certificates.verify-logs') }}" class="btn btn-white">Verify log</a>
        <a href="{{ route('admin.certificates.templates.index') }}" class="btn btn-white">Templates</a>
        <a href="{{ route('admin.certificates.completions.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Issue Certificate</a>
    </div>
</div>
<div class="wrapper wrapper-content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('fail'))<div class="alert alert-danger">{{ session('fail') }}</div>@endif
    <div class="ibox">
        <div class="ibox-title"><h5>Certificates</h5></div>
        <div class="ibox-content">
            <form method="GET" class="form-group">
                <div class="row">
                    <div class="col-sm-4">
                        <input type="search" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Certification ID, student, course">
                    </div>
                    <div class="col-sm-3">
                        <select name="course_id" class="form-control js-search-select" data-placeholder="Type to find course">
                            <option value="">All courses</option>
                            @foreach($courses ?? [] as $course)
                                <option value="{{ $course->id }}" @selected((string)($filters['course_id'] ?? '') === (string)$course->id)>{{ $course->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-3">
                        <select name="status" class="form-control">
                            <option value="">All statuses</option>
                            <option value="issued" @selected(in_array($filters['status'], ['issued', 'certificate_issued'], true))>Issued</option>
                            <option value="not_issued" @selected(in_array($filters['status'], ['not_issued', 'completed'], true))>Not issued</option>
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
                            <th>Certificate details</th>
                            <th>Student Details</th>
                            <th>Course details</th>
                            <th>Awarded</th>
                            <th>Issued</th>
                            <th>Date of Revoke</th>
                            <th>Date of Reissued</th>
                            <th>Status</th>
                            <th style="width:280px;">Action</th>
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
                                $sid = $student && method_exists($student, 'studentIdValue') ? $student->studentIdValue() : ($row->student_number ?: ($student->student_id ?? $student->student_number ?? ''));
                                $certs = $row->certificates ?? collect();
                                $issued = $certs->where('status', 'issued');
                                $latest = $certs->first();
                            @endphp
                            <tr>
                                <td>
                                    @forelse($certs as $cert)
                                        @php
                                            $tpl = $cert->template ?: $row->template;
                                            $tplId = $cert->template_id ?: $row->template_id;
                                            $tplName = $tpl->name ?? '—';
                                        @endphp
                                        <div class="small" style="margin-bottom:8px;line-height:1.5;">
                                            <div><strong>Certification ID:</strong> <code>{{ $cert->certificate_code }}</code></div>
                                            <div><strong>Template ID:</strong> {{ $tplId ?: '—' }}</div>
                                            <div><strong>Template Name:</strong> {{ $tplName }}</div>
                                        </div>
                                    @empty
                                        @php
                                            $tpl = $row->template;
                                        @endphp
                                        <div class="small" style="line-height:1.5;">
                                            <div><strong>Certification ID:</strong> —</div>
                                            <div><strong>Template ID:</strong> {{ $row->template_id ?: '—' }}</div>
                                            <div><strong>Template Name:</strong> {{ $tpl->name ?? '—' }}</div>
                                        </div>
                                    @endforelse
                                </td>
                                <td>
                                    @php
                                        $country = '—';
                                        if ($student && method_exists($student, 'displayCountryName')) {
                                            $country = $student->displayCountryName();
                                        } elseif (! empty($student->country)) {
                                            $country = $student->country;
                                        }
                                        $batchLabel = $row->batch ? trim(($row->batch->code ? $row->batch->code.' — ' : '').($row->batch->name ?: '')) : '';
                                    @endphp
                                    <div class="small" style="line-height:1.5;">
                                        <div><strong>Student ID:</strong> <code>{{ $sid ?: ($latest->student_number ?? '—') }}</code></div>
                                        <div><strong>Student Name:</strong> {{ $latest->student_name ?? $studentLabel }}</div>
                                        <div><strong>Email address:</strong> {{ $student->email ?? '—' }}</div>
                                        <div><strong>Batch:</strong> {{ $batchLabel !== '' ? $batchLabel : '—' }}</div>
                                        <div><strong>Country:</strong> {{ $country }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small" style="line-height:1.5;">
                                        <div><strong>Course Name:</strong> {{ $latest->course_title ?? ($row->course->title ?? '—') }}</div>
                                        <div><strong>Sub Title:</strong> {{ $latest->course_subtitle ?? $row->course_subtitle ?: '—' }}</div>
                                        <div><strong>Duration:</strong> {{ $latest->programme_duration ?? $row->programme_duration ?: '—' }}</div>
                                        <div><strong>CPD:</strong> {{ $latest->cpd_hours ?? $row->cpd_hours ?: '—' }}</div>
                                    </div>
                                </td>
                                <td>{{ optional($latest->completion_date ?? $row->completed_at)->format('d M Y') ?: '—' }}</td>
                                <td>{{ optional($latest->issued_at)->format('d M Y H:i') ?: '—' }}</td>
                                <td>
                                    @php $revokedAt = $certs->pluck('revoked_at')->filter()->sortDesc()->first(); @endphp
                                    {{ $revokedAt ? $revokedAt->format('d M Y H:i') : '—' }}
                                </td>
                                <td>
                                    @php $reissuedAt = $certs->pluck('reissued_at')->filter()->sortDesc()->first(); @endphp
                                    {{ $reissuedAt ? $reissuedAt->format('d M Y H:i') : '—' }}
                                </td>
                                <td>
                                    @if($issued->isNotEmpty())
                                        <span class="label label-primary">Issued{{ $issued->count() > 1 ? ' ('.$issued->count().')' : '' }}</span>
                                    @elseif($certs->where('status', 'revoked')->isNotEmpty())
                                        <span class="label label-danger">Revoked</span>
                                    @else
                                        <span class="label label-warning">Not issued</span>
                                    @endif
                                    <div class="text-muted small" style="margin-top:4px;">{{ $row->marked_by_name ?: '—' }}</div>
                                </td>
                                <td>
                                    @forelse($certs as $cert)
                                        <div style="margin-bottom:8px;">
                                            <a class="btn btn-xs btn-white" href="{{ route('admin.certificates.edit', $cert->id) }}" title="Change name, course, dates, duration, CPD and template, then rebuild the PDF. Same Certification ID.">Edit</a>
                                            <a class="btn btn-xs btn-white" href="{{ route('admin.certificates.preview', $cert->id) }}" target="_blank" rel="noopener" title="Open the current PDF in the browser.">Preview</a>
                                            <a class="btn btn-xs btn-info" href="{{ route('admin.certificates.download', $cert->id) }}" title="Download the PDF file."><i class="fa fa-download"></i> PDF</a>
                                            @if(! $cert->isRevoked())
                                                <form method="POST" action="{{ route('admin.certificates.email', $cert->id) }}" style="display:inline;">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-primary" title="{{ $cert->emailed_at ? 'Last emailed '.$cert->emailed_at->format('d M Y H:i') : 'Not emailed yet' }}">Email</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.certificates.reissue', $cert->id) }}" style="display:inline;" onsubmit="return confirm('Regenerate the PDF for this certificate?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-warning" title="Keep the same Certification ID and rebuild the PDF from the template (background, logo, text).">Re-issue PDF</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.certificates.revoke', $cert->id) }}" style="display:inline;" onsubmit="return confirm('Revoke this certificate?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-danger">Revoke</button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.certificates.reissue', $cert->id) }}" style="display:inline;" onsubmit="return confirm('Re-issue this certificate and regenerate PDF?');">
                                                    @csrf
                                                    <input type="hidden" name="send_email" value="1">
                                                    <button type="submit" class="btn btn-xs btn-warning">Re-issue + email</button>
                                                </form>
                                            @endif
                                            @if($cert->emailed_at)
                                                <div class="text-muted small" style="margin-top:4px;">Emailed {{ $cert->emailed_at->format('d M Y') }}</div>
                                            @endif
                                        </div>
                                    @empty
                                        <a class="btn btn-xs btn-white" href="{{ route('admin.certificates.completions.edit', $row->id) }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.certificates.completions.issue', $row->id) }}" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="send_email" value="1">
                                            @if($row->template_id)
                                                <input type="hidden" name="template_id" value="{{ $row->template_id }}">
                                            @endif
                                            <button type="submit" class="btn btn-xs btn-primary">Issue + email</button>
                                        </form>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9">No certificates yet. Use Issue Certificate to start.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        </div>
    </div>
</div>
@endsection
@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>.select2-container { width: 100% !important; }</style>
@endpush
@push('script')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    $('.js-search-select').select2({ placeholder: 'Type to find course', allowClear: true, width: '100%' });
});
</script>
@endpush
