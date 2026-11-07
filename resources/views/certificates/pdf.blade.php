<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { size: A4 portrait; margin: 0; }
  * { box-sizing: border-box; }
  html, body {
    font-family: DejaVu Sans, sans-serif;
    color: #1f1f1f;
    margin: 0;
    padding: 0;
    width: 210mm;
    height: 297mm;
  }
  .page {
    position: relative;
    width: 210mm;
    height: 297mm;
    overflow: hidden;
    page-break-after: avoid;
    page-break-inside: avoid;
  }
  .bg {
    position: absolute;
    top: 0; left: 0;
    width: 210mm;
    height: 297mm;
    z-index: 0;
  }
  .logo {
    position: absolute;
    top: 10mm;
    left: 0;
    width: 210mm;
    text-align: center;
    z-index: 2;
  }
  .logo img {
    width: 70mm;
    height: 22mm;
    display: inline-block;
    background: transparent;
    border: 0;
  }
  .heading, .student, .body-text, .course, .meta, .date, .seal, .signer, .verify, .cert-no {
    z-index: 2;
  }
  .heading {
    position: absolute;
    top: 46mm;
    left: 18mm;
    right: 18mm;
    text-align: center;
    color: {{ optional($template)->primary_color ?: '#6b1d2a' }};
    text-transform: uppercase;
    letter-spacing: 2px;
  }
  .heading .h1 { font-size: 32px; font-weight: bold; margin: 0; }
  .heading .h2 { font-size: 18px; font-weight: bold; margin: 4px 0 0; letter-spacing: 3px; }
  .student {
    position: absolute;
    top: 78mm;
    left: 18mm;
    right: 18mm;
    text-align: center;
    font-size: 24px;
    font-weight: bold;
    color: #111;
  }
  .body-text {
    position: absolute;
    top: 96mm;
    left: 28mm;
    right: 28mm;
    text-align: center;
    font-size: 12px;
    line-height: 1.45;
    color: #4b5563;
  }
  .course {
    position: absolute;
    top: 118mm;
    left: 18mm;
    right: 18mm;
    text-align: center;
    color: #111;
  }
  .course .main { font-size: 16px; font-weight: bold; text-transform: uppercase; margin: 0; }
  .course .sub { font-size: 12px; margin: 4px 0 0; color: #374151; }
  .meta {
    position: absolute;
    top: 148mm;
    left: 18mm;
    right: 18mm;
    text-align: center;
    font-size: 12px;
    color: #374151;
    line-height: 1.5;
  }
  .date {
    position: absolute;
    top: 168mm;
    left: 18mm;
    right: 18mm;
    text-align: center;
    font-size: 13px;
    color: #111;
  }
  .seal {
    position: absolute;
    top: 188mm;
    left: 0;
    width: 210mm;
    text-align: center;
  }
  .seal img { height: 28mm; background: transparent; border: 0; }
  .signer {
    position: absolute;
    top: 196mm;
    width: 62mm;
    text-align: center;
  }
  .signer.left { left: 22mm; }
  .signer.right { right: 22mm; }
  .signer img { height: 16mm; max-width: 50mm; background: transparent; border: 0; }
  .signer .line { border-top: 0.4px solid #c5a46a; margin: 2px 10mm 3px; }
  .signer .name { font-size: 11px; font-style: italic; }
  .signer .role { font-size: 9px; color: #4b5563; }
  .verify {
    position: absolute;
    left: 18mm;
    right: 18mm;
    bottom: 14mm;
    height: 32mm;
  }
  .verify-title {
    font-size: 10px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin: 3mm 0 2mm 6mm;
  }
  .verify-meta {
    font-size: 9px;
    color: #374151;
    margin-left: 6mm;
    line-height: 1.45;
    width: 135mm;
  }
  .qr {
    position: absolute;
    right: 8mm;
    top: 3mm;
    width: 26mm;
    text-align: center;
  }
  .qr img { width: 24mm; height: 24mm; }
</style>
</head>
<body>
@php
  $heading = $template ? $template->headingText() : 'CERTIFICATE OF COMPLETION';
  $headingMain = 'CERTIFICATE';
  $headingSub = 'OF COMPLETION';
  if (stripos($heading, ' of ') !== false) {
      $bits = preg_split('/\s+of\s+/i', $heading, 2);
      $headingMain = $bits[0] ?? $headingMain;
      $headingSub = isset($bits[1]) ? 'OF ' . $bits[1] : $headingSub;
  }
  $awarded = optional($certificate->completion_date)->format('d F Y') ?: optional($certificate->issued_at)->format('d F Y');
@endphp
  <div class="page">
    @if(!empty($bgSrc))
      <img class="bg" src="{{ $bgSrc }}" alt="">
    @endif

    @if(!empty($logoSrc))
      <div class="logo"><img src="{{ $logoSrc }}" alt=""></div>
    @endif

    <div class="heading">
      <p class="h1">{{ $headingMain }}</p>
      <p class="h2">{{ $headingSub }}</p>
    </div>

    <div class="student">{{ $certificate->student_name }}</div>
    <div class="body-text">{{ $template ? $template->bodyText() : 'having attended and successfully completed the training programme, has been awarded this certificate in' }}</div>

    <div class="course">
      <p class="main">{{ $certificate->course_title }}</p>
      @if($certificate->course_subtitle)
        <p class="sub">{{ $certificate->course_subtitle }}</p>
      @endif
    </div>

    <div class="meta">
      @if($certificate->programme_duration)
        Programme Duration: {{ $certificate->programme_duration }}<br>
      @endif
      @if($certificate->cpd_hours)
        CPD Hours: {{ $certificate->cpd_hours }}
      @endif
    </div>

    <div class="date">{{ $awarded }}</div>

    @if(!empty($sealSrc))
      <div class="seal"><img src="{{ $sealSrc }}" alt=""></div>
    @endif

    <div class="signer left">
      @if(!empty($signerLeftSrc))
        <img src="{{ $signerLeftSrc }}" alt="">
      @endif
      <div class="line"></div>
      <div class="name">{{ $template->signer_left_name ?? '' }}</div>
      <div class="role">{{ $template->signer_left_title ?? '' }}</div>
    </div>
    <div class="signer right">
      @if(!empty($signerRightSrc))
        <img src="{{ $signerRightSrc }}" alt="">
      @endif
      <div class="line"></div>
      <div class="name">{{ $template->signer_right_name ?? '' }}</div>
      <div class="role">{{ $template->signer_right_title ?? '' }}</div>
    </div>

    <div class="verify">
      <div class="verify-title">{{ $template->verify_heading ?: 'CERTIFICATE VERIFICATION' }}</div>
      <div class="verify-meta">
        STUDENT ID: {{ $certificate->student_number ?: '—' }}
        &nbsp;&nbsp;&nbsp;
        CERTIFICATE NUMBER: {{ $certificate->certificate_code }}<br>
        {{ $template->footer_org_line ?: 'Berkeley School of Business, Arts & Sciences Ltd. | UKPRN: 10101119' }}<br>
        Email: {{ $template->footer_email ?: 'Registrar@eduberkeley.com' }}
        &nbsp;|&nbsp;
        Web: {{ $template->footer_website ?: 'www.eduberkeley.com' }}<br>
        Scan QR code or visit {{ url('/ecert-verify') }} to authenticate e-Certificate
      </div>
      @if(!empty($qrSrc))
        <div class="qr"><img src="{{ $qrSrc }}" alt="Verify QR"></div>
      @endif
    </div>
  </div>
</body>
</html>
