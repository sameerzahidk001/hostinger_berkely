<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Certificate — Berkeley</title>
    <style>
        body { margin:0; font-family: Georgia, "Times New Roman", serif; background:#f4f6f8; color:#111; }
        .wrap { max-width:720px; margin:40px auto; padding:0 16px; }
        .card { background:#fff; border:1px solid #e5e7eb; padding:28px 24px; }
        h1 { font-size:28px; margin:0 0 8px; color:#000435; }
        .sub { color:#6b7280; margin:0 0 20px; font-family: Arial, sans-serif; font-size:14px; }
        label { display:block; font-family: Arial, sans-serif; font-size:13px; margin-bottom:6px; }
        input[type=text] { width:100%; padding:10px 12px; border:1px solid #cbd5e1; box-sizing:border-box; font-size:15px; }
        button { margin-top:12px; background:#000435; color:#fff; border:0; padding:10px 16px; cursor:pointer; font-family: Arial, sans-serif; }
        .result { margin-top:22px; padding:16px; border-left:4px solid #000435; background:#f8fafc; }
        .result.valid { border-left-color:#16a34a; }
        .result.revoked { border-left-color:#dc2626; }
        .result.not_found { border-left-color:#f59e0b; }
        .badge { display:inline-block; padding:3px 8px; font-size:12px; font-family: Arial, sans-serif; color:#fff; }
        .badge.valid { background:#16a34a; }
        .badge.revoked { background:#dc2626; }
        .badge.not_found { background:#f59e0b; }
        .meta { font-family: Arial, sans-serif; font-size:14px; line-height:1.55; margin-top:10px; }
        a { color:#000435; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Verify Certificate</h1>
        <p class="sub">Berkeley School of Business, Arts &amp; Sciences — confirm a digital certificate by code or QR link.</p>

        <form method="GET" action="{{ url('/verify-certificate') }}">
            <label for="code">Certificate code</label>
            <input id="code" type="text" name="code" value="{{ $code }}" placeholder="e.g. CERT-2026-000001" required>
            <button type="submit">Verify</button>
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
</body>
</html>
