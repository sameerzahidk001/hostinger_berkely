@extends('layouts.app')

@push('style')
<style>
    .ecert-verify { background:#f4f6f8; color:#111; padding: 96px 16px 48px; }
    .ecert-verify .wrap { max-width:1100px; margin:0 auto; }
    .ecert-verify .card { background:#fff; border:1px solid #e5e7eb; padding:28px 24px; }
    .ecert-verify h1 { font-family: Georgia, "Times New Roman", serif; font-size:28px; margin:0 0 8px; color:#000435; }
    .ecert-verify h2 { font-size:22px; margin:0 0 16px; color:#111; font-weight:700; }
    .ecert-verify h3 { font-size:16px; margin:0 0 8px; color:#000435; }
    .ecert-verify .sub { color:#6b7280; margin:0 0 20px; font-size:14px; }
    .ecert-verify .cms-html { color:#6b7280; margin:0 0 20px; font-size:14px; line-height:1.55; }
    .ecert-verify .cms-html p { margin:0 0 10px; }
    .ecert-verify label { display:block; font-size:13px; font-weight:600; margin:0 0 6px; }
    .ecert-verify .req { color:#c0392b; }
    .ecert-verify input, .ecert-verify textarea { width:100%; padding:10px 12px; border:1px solid #cbd5e1; box-sizing:border-box; font-size:15px; background:#fff; }
    .ecert-verify textarea { min-height:72px; resize:vertical; }
    .ecert-verify .cols { display:grid; grid-template-columns: 1fr 1fr; border:1px solid #111; }
    .ecert-verify .panel { padding:20px 22px; }
    .ecert-verify .panel + .panel { border-left:1px solid #111; }
    .ecert-verify .row { display:grid; grid-template-columns: 1fr 1fr; gap:12px 16px; margin-bottom:14px; }
    .ecert-verify .row > div { min-width:0; }
    .ecert-verify .span-2 { grid-column: 1 / -1; }
        @media (max-width: 800px) {
        .ecert-verify .cols, .ecert-verify .row, .ecert-verify .otp-row { grid-template-columns: 1fr; }
        .ecert-verify .panel + .panel { border-left:0; border-top:1px solid #111; }
        .ecert-verify .span-2 { grid-column: auto; }
    }
    .ecert-verify button[type="submit"] { margin-top:8px; background:#000435; color:#fff; border:0; padding:11px 18px; cursor:pointer; font-size:15px; }
    .ecert-verify button[disabled] { opacity:.45; cursor:not-allowed; pointer-events:none; }
    .ecert-verify .otp-row { display:grid; grid-template-columns: minmax(0,1fr) auto minmax(0,1fr); gap:12px; align-items:end; margin-bottom:8px; }
    .ecert-verify .otp-row input { height:42px; }
    .ecert-verify button.btn-otp {
        margin-top:0;
        background:#000435;
        color:#fff;
        border:0;
        padding:0 16px;
        cursor:pointer;
        font-size:15px;
        white-space:nowrap;
        height:42px;
        width:auto;
    }
    .ecert-verify .otp-spacer { display:block; font-size:13px; font-weight:600; margin:0 0 6px; visibility:hidden; }
    .ecert-verify .otp-ok { font-size:13px; color:#166534; margin:0 0 14px; }
    .ecert-verify .errors, .ecert-verify .flash-fail { background:#fdecea; color:#9b1c1c; padding:10px 12px; margin-bottom:16px; font-size:13px; }
    .ecert-verify .flash-ok { background:#e8f8ee; color:#166534; padding:10px 12px; margin-bottom:16px; font-size:13px; }
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
    .ecert-verify .locked input,
    .ecert-verify .locked textarea { background:#f1f5f9; color:#64748b; cursor:not-allowed; pointer-events:none; }
    .ecert-verify .hint { font-size:13px; color:#6b7280; margin:0 0 12px; }
</style>
@endpush

@section('content')
<section class="ecert-verify">
<div class="wrap">
    <div class="card">
        <h1>{{ $copy['title'] ?? 'Verify Certificate' }}</h1>
        @if(trim(strip_tags((string) ($copy['html'] ?? ''))) !== '')
            <div class="cms-html">{!! render_cms_html($copy['html']) !!}</div>
        @endif
        @if(session('success'))
            <div class="flash-ok">{{ session('success') }}</div>
        @endif
        @if(session('fail'))
            <div class="flash-fail">{{ session('fail') }}</div>
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

        <div class="cols">
            <div class="panel">
                <h2>Your details:</h2>
                <form method="POST" action="{{ url('/ecert-verify/verifier') }}" id="ecert-verifier-form">
                    @csrf
                    <div class="row">
                        <div>
                            <label for="verifier_name">Your Name <span class="req">*</span></label>
                            <input id="verifier_name" type="text" name="verifier_name" value="{{ old('verifier_name', $old['verifier_name'] ?? '') }}" required>
                        </div>
                        <div>
                            <label for="verifier_phone">Contact Number <span class="req">*</span></label>
                            <input id="verifier_phone" type="text" name="verifier_phone" value="{{ old('verifier_phone', $old['verifier_phone'] ?? '') }}" required>
                        </div>
                        <div>
                            <label for="verifier_organization">Organization/ Company Name <span class="req">*</span></label>
                            <input id="verifier_organization" type="text" name="verifier_organization" value="{{ old('verifier_organization', $old['verifier_organization'] ?? '') }}" required>
                        </div>
                        <div>
                            <label for="verifier_position">Position <span class="req">*</span></label>
                            <input id="verifier_position" type="text" name="verifier_position" value="{{ old('verifier_position', $old['verifier_position'] ?? '') }}" required>
                        </div>
                    </div>
                    <div class="otp-row">
                        <div>
                            <label for="verifier_email">Email Address <span class="req">*</span></label>
                            <input id="verifier_email" type="email" name="verifier_email" value="{{ old('verifier_email', $old['verifier_email'] ?? '') }}" required>
                        </div>
                        <div>
                            <span class="otp-spacer">&nbsp;</span>
                            <button type="submit" class="btn-otp" formaction="{{ url('/ecert-verify/otp') }}" formnovalidate>Send OTP</button>
                        </div>
                        <div>
                            <label for="otp">Type OTP here @unless($otpVerified)<span class="req">*</span>@endunless</label>
                            <input id="otp" type="text" name="otp" value="" inputmode="numeric" autocomplete="one-time-code" maxlength="6" @unless($otpVerified) required @endunless>
                        </div>
                    </div>
                    @if($otpVerified)
                        <p class="otp-ok">Email verified.</p>
                    @elseif($otpPending)
                        <p class="hint">OTP sent. Enter it here, then submit your details.</p>
                    @endif
                    <div class="row">
                        <div class="span-2">
                            <label for="verifier_reason">Reason for Certificate Verification <span class="req">*</span></label>
                            <textarea id="verifier_reason" name="verifier_reason" required>{{ old('verifier_reason', $old['verifier_reason'] ?? '') }}</textarea>
                        </div>
                    </div>
                    <button type="submit">Submit your details</button>
                </form>
            </div>
            <div class="panel">
                <h2>Certificate Details to Verify:</h2>
                @if($verifierReady)
                    <p class="hint">Enter the Certification ID and Student ID, then check the certificate details.</p>
                @else
                    <p class="hint">Complete your details and email OTP first. These fields stay empty until then.</p>
                @endif
                <form method="POST" action="{{ url('/ecert-verify') }}" id="ecert-check-form" class="{{ $verifierReady ? '' : 'locked' }}">
                    @csrf
                    <div class="row">
                        <div>
                            <label for="student_number">Student ID <span class="req">*</span></label>
                            <input id="student_number" type="text" name="student_number" value="{{ $verifierReady ? old('student_number', $old['student_number'] ?? '') : '' }}" @if($verifierReady) required @else disabled @endif autocomplete="off">
                        </div>
                        <div>
                            <label for="code">Certification ID <span class="req">*</span></label>
                            <input id="code" type="text" name="code" value="{{ $verifierReady ? old('code', $code) : '' }}" @if($verifierReady) required @else disabled @endif autocomplete="off">
                        </div>
                    </div>
                    <button type="submit" @unless($verifierReady) disabled @endunless>Check Certificate</button>
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
                                <div><strong>Certification ID:</strong> {{ $certificate->certificate_code }}</div>
                                <p style="margin:12px 0 0;">This certificate was issued by Berkeley School of Business, Arts &amp; Sciences.</p>
                            </div>
                        @elseif($result === 'revoked')
                            <span class="badge revoked">Revoked</span>
                            <div class="meta">
                                <div><strong>Certification ID:</strong> {{ $certificate->certificate_code }}</div>
                                <div><strong>Student:</strong> {{ $certificate->student_name }}</div>
                                <div><strong>Student ID:</strong> {{ $certificate->student_number ?: '—' }}</div>
                                <div><strong>Course:</strong> {{ $certificate->course_title }}</div>
                                <p style="margin:12px 0 0;">This certificate is no longer valid.</p>
                            </div>
                        @else
                            <span class="badge not_found">Not found</span>
                            <div class="meta">No certificate matches that Student ID and Certification ID. Check both values and try again.</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
</section>
@endsection

@push('script')
<script>
(function () {
    var locked = document.querySelectorAll('.ecert-verify .locked input, .ecert-verify button[disabled]');
    locked.forEach(function (el) {
        el.addEventListener('click', function (e) { e.preventDefault(); e.stopPropagation(); }, true);
        el.addEventListener('keydown', function (e) { e.preventDefault(); e.stopPropagation(); }, true);
        el.addEventListener('paste', function (e) { e.preventDefault(); }, true);
        el.addEventListener('focus', function () { this.blur(); }, true);
    });
    var email = document.getElementById('verifier_email');
    var otp = document.getElementById('otp');
    var verifiedEmail = @json($otpVerified ? strtolower(trim((string) ($old['verifier_email'] ?? ''))) : '');
    if (email && otp) {
        email.addEventListener('input', function () {
            var current = (email.value || '').trim().toLowerCase();
            otp.required = verifiedEmail === '' || current !== verifiedEmail;
        });
    }
})();
</script>
@endpush
