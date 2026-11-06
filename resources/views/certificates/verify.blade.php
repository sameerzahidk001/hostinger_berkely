@extends('layouts.app')

@push('style')
<style>
    .ecert-verify { background:#f4f6f8; color:#111; padding: 96px 16px 48px; }
    .ecert-verify .wrap { max-width:760px; margin:0 auto; }
    .ecert-verify .card { background:#fff; border:1px solid #e5e7eb; padding:28px 24px; }
    .ecert-verify h1 { font-family: Georgia, "Times New Roman", serif; font-size:28px; margin:0 0 8px; color:#000435; }
    .ecert-verify .sub { color:#6b7280; margin:0 0 20px; font-size:14px; }
    .ecert-verify .cms-html { color:#6b7280; margin:0 0 20px; font-size:14px; line-height:1.55; }
    .ecert-verify .cms-html p { margin:0 0 10px; }
    .ecert-verify .cms-html p:last-child { margin-bottom:0; }
    .ecert-verify .cms-media { max-width:100%; height:auto; margin:0 0 16px; }
    .ecert-verify label { display:block; font-size:13px; font-weight:600; margin:0 0 6px; }
    .ecert-verify .req { color:#c0392b; }
    .ecert-verify input, .ecert-verify textarea { width:100%; padding:10px 12px; border:1px solid #cbd5e1; box-sizing:border-box; font-size:15px; }
    .ecert-verify textarea { min-height:72px; resize:vertical; }
    .ecert-verify .row { display:flex; gap:12px; flex-wrap:wrap; }
    .ecert-verify .row > div { flex:1 1 240px; margin-bottom:14px; }
    .ecert-verify button[type="submit"] { margin-top:8px; background:#000435; color:#fff; border:0; padding:11px 18px; cursor:pointer; font-size:15px; }
    .ecert-verify .errors { background:#fdecea; color:#9b1c1c; padding:10px 12px; margin-bottom:16px; font-size:13px; }
    .ecert-verify .result { margin-top:22px; padding:16px; border-left:4px solid #000435; background:#f8fafc; }
    .ecert-verify .result.valid { border-left-color:#16a34a; }
    .ecert-verify .result.revoked { border-left-color:#dc2626; }
    .ecert-verify .result.not_found { border-left-color:#f59e0b; }
    .ecert-verify .badge { display:inline-block; padding:3px 8px; font-size:12px; color:#fff; }
    .ecert-verify .badge.valid { background:#16a34a; }
    .ecert-verify .badge.revoked { background:#dc2626; }
    .ecert-verify .badge.not_found { background:#f59e0b; }
    .ecert-verify .meta { font-size:14px; line-height:1.55; margin-top:10px; }
    .ecert-verify a { color:#000435; }
</style>
@endpush

@section('content')
<section class="ecert-verify">
<div class="wrap">
    <div class="card">
        @php
            $cmsSections = collect(optional($cmsPage ?? null)->sections);
            $hasCmsCopy = $cmsSections->contains(function ($section) {
                $data = is_array($section->data ?? null) ? $section->data : [];
                return trim((string) (($data['title'] ?? '') . strip_tags((string) ($data['description'] ?? '')))) !== '';
            });
        @endphp
        @if($hasCmsCopy)
            @foreach($cmsSections as $section)
                @php $data = is_array($section->data ?? null) ? $section->data : []; @endphp
                @if(!empty($data['image']))
                    <img class="cms-media" src="{{ media_url($data['image']) }}" alt="{{ image_alt($data['image_alt'] ?? null, $data['title'] ?? 'Verify Certificate') }}">
                @endif
                @if(!empty($data['title']))
                    <h1>{{ $data['title'] }}</h1>
                @endif
                @if(!empty($data['description']))
                    <div class="cms-html">{!! render_cms_html($data['description']) !!}</div>
                @endif
            @endforeach
        @else
            <h1>Verify Certificate</h1>
            <p class="sub">Anyone can verify a Berkeley e-certificate. All fields below are required. Enter the certificate ID / number from the document or QR link.</p>
        @endif

        @if($errors->any())
            <div class="errors">
                <ul style="margin:0; padding-left:18px;">
                    @foreach($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ url('/ecert-verify') }}">
            @csrf
            <div class="row">
                <div style="flex-basis:100%;">
                    <label for="code">Certificate ID / number <span class="req">*</span></label>
                    <input id="code" type="text" name="code" value="{{ old('code', $code) }}" required>
                </div>
                <div>
                    <label for="verifier_name">Name <span class="req">*</span></label>
                    <input id="verifier_name" type="text" name="verifier_name" value="{{ old('verifier_name', $old['verifier_name'] ?? '') }}" required>
                </div>
                <div>
                    <label for="verifier_email">Email address <span class="req">*</span></label>
                    <input id="verifier_email" type="email" name="verifier_email" value="{{ old('verifier_email', $old['verifier_email'] ?? '') }}" required>
                </div>
                <div>
                    <label for="verifier_phone">Contact number <span class="req">*</span></label>
                    <input id="verifier_phone" type="text" name="verifier_phone" value="{{ old('verifier_phone', $old['verifier_phone'] ?? '') }}" required>
                </div>
                <div>
                    <label for="verifier_organization">Organization / company name <span class="req">*</span></label>
                    <input id="verifier_organization" type="text" name="verifier_organization" value="{{ old('verifier_organization', $old['verifier_organization'] ?? '') }}" required>
                </div>
                <div>
                    <label for="verifier_position">Position <span class="req">*</span></label>
                    <input id="verifier_position" type="text" name="verifier_position" value="{{ old('verifier_position', $old['verifier_position'] ?? '') }}" required>
                </div>
                <div style="flex-basis:100%;">
                    <label for="verifier_reason">Reason for verification <span class="req">*</span></label>
                    <textarea id="verifier_reason" name="verifier_reason" required>{{ old('verifier_reason', $old['verifier_reason'] ?? '') }}</textarea>
                </div>
            </div>
            <button type="submit">Verify certificate</button>
        </form>

        @if($result)
            <div class="result {{ $result }}">
                @if($result === 'valid')
                    <span class="badge valid">Valid</span>
                    <div class="meta">
                        <div><strong>Student:</strong> {{ $certificate->student_name }}</div>
                        <div><strong>Student ID:</strong> {{ $certificate->student_number ?: '—' }}</div>
                        <div><strong>Course:</strong> {{ $certificate->course_title }}</div>
                        @if($certificate->course_subtitle)
                            <div><strong>Subtitle:</strong> {{ $certificate->course_subtitle }}</div>
                        @endif
                        @if($certificate->programme_duration)
                            <div><strong>Programme duration:</strong> {{ $certificate->programme_duration }}</div>
                        @endif
                        @if($certificate->cpd_hours)
                            <div><strong>CPD hours:</strong> {{ $certificate->cpd_hours }}</div>
                        @endif
                        <div><strong>Completion date:</strong> {{ optional($certificate->completion_date)->format('d M Y') ?: '—' }}</div>
                        <div><strong>Issued:</strong> {{ optional($certificate->issued_at)->format('d M Y') ?: '—' }}</div>
                        <div><strong>Certificate number:</strong> {{ $certificate->certificate_code }}</div>
                        <p style="margin:12px 0 0;">This certificate was issued by Berkeley School of Business, Arts &amp; Sciences.</p>
                    </div>
                @elseif($result === 'revoked')
                    <span class="badge revoked">Revoked</span>
                    <div class="meta">
                        <div><strong>Code:</strong> {{ $certificate->certificate_code }}</div>
                        <div><strong>Student:</strong> {{ $certificate->student_name }}</div>
                        <div><strong>Course:</strong> {{ $certificate->course_title }}</div>
                        <p style="margin:12px 0 0;">This certificate is no longer valid.</p>
                    </div>
                @else
                    <span class="badge not_found">Not found</span>
                    <div class="meta">No certificate matches <strong>{{ $code }}</strong>. Check the code and try again.</div>
                @endif
            </div>
        @endif
    </div>
</div>
</section>
@endsection
