<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
  @page { margin: 28px; }
  body {
    font-family: DejaVu Sans, sans-serif;
    color: #111827;
    margin: 0;
  }
  .frame {
    border: 10px solid #000435;
    padding: 18px;
    height: 520px;
    position: relative;
  }
  .inner {
    border: 2px solid #f8961f;
    height: 100%;
    box-sizing: border-box;
    padding: 28px 36px;
    text-align: center;
  }
  .eyebrow {
    letter-spacing: 3px;
    text-transform: uppercase;
    font-size: 12px;
    color: #6b7280;
    margin: 0 0 10px;
  }
  h1 {
    font-size: 34px;
    color: #000435;
    margin: 0 0 8px;
  }
  .school {
    font-size: 14px;
    color: #374151;
    margin: 0 0 22px;
  }
  .line {
    font-size: 13px;
    color: #4b5563;
    margin: 0 0 8px;
  }
  .name {
    font-size: 28px;
    color: #111827;
    margin: 10px 0 14px;
    font-weight: bold;
  }
  .course {
    font-size: 18px;
    color: #000435;
    margin: 0 0 18px;
  }
  .meta {
    font-size: 12px;
    color: #4b5563;
    margin-top: 18px;
  }
  .code {
    font-size: 12px;
    letter-spacing: 1px;
    margin-top: 8px;
  }
  .qr {
    position: absolute;
    right: 42px;
    bottom: 42px;
    width: 96px;
    text-align: center;
  }
  .qr img { width: 90px; height: 90px; }
  .qr span { display: block; font-size: 8px; color: #6b7280; margin-top: 3px; }
</style>
</head>
<body>
  <div class="frame">
    <div class="inner">
      <p class="eyebrow">Certificate of Completion</p>
      <h1>Digital Certificate</h1>
      <p class="school">{{ $schoolName }}</p>
      <p class="line">This is to certify that</p>
      <p class="name">{{ $certificate->student_name }}</p>
      <p class="line">has successfully completed</p>
      <p class="course">{{ $certificate->course_title }}</p>
      <p class="meta">
        Completion date:
        {{ optional($certificate->completion_date)->format('d M Y') ?: '—' }}
        &nbsp;·&nbsp;
        Issued:
        {{ optional($certificate->issued_at)->format('d M Y') ?: '—' }}
      </p>
      <p class="code">Certificate code: {{ $certificate->certificate_code }}</p>
      @if(!empty($qrSrc))
        <div class="qr">
          <img src="{{ $qrSrc }}" alt="Verify QR">
          <span>Scan to verify</span>
        </div>
      @endif
    </div>
  </div>
</body>
</html>
