<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Result PDF</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #333; font-size: 12px; line-height: 1.45; }
        .top { width: 100%; margin-bottom: 14px; border-bottom: 1px solid #e5e5e5; padding-bottom: 10px; }
        .top td { vertical-align: middle; }
        .brand { font-size: 14px; font-weight: bold; color: #1a365d; }
        .doc-title { font-size: 16px; font-weight: bold; margin-top: 2px; }
        .doc-sub { font-size: 10px; color: #777; margin-top: 2px; }
        .logo { height: 48px; max-width: 180px; }
        h1 { font-size: 18px; margin: 10px 0 8px; }
        h2 { font-size: 14px; margin: 18px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta { margin-bottom: 8px; }
        .meta td { padding: 2px 8px 2px 0; vertical-align: top; }
        .q { margin: 16px 0; padding: 12px; border: 1px solid #e5e5e5; page-break-inside: avoid; }
        .q-title { font-weight: bold; margin-bottom: 8px; }
        .q-stem { margin: 0 0 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
        .q-answer { margin-top: 16px; }
        .q-answer .label { font-weight: bold; margin-bottom: 8px; display: block; }
        .q-answer .body { margin: 0 0 10px; padding: 10px 12px; background: #fafafa; border: 1px solid #eee; }
        .muted { color: #777; }
        .score { font-size: 14px; font-weight: bold; }
        .feedback { background: #f7f7f7; padding: 8px; margin-top: 10px; }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 8px;
            margin-top: 18px;
            font-size: 9px;
            color: #777;
            text-align: center;
            line-height: 1.45;
        }
    </style>
</head>
<body>
@php
    $logoSrc = null;
    $logoPath = public_path('frontend/images/pngs/logo-color.png');
    if (is_file($logoPath) && filesize($logoPath) > 0 && filesize($logoPath) < 1500000) {
        $mime = @mime_content_type($logoPath) ?: 'image/png';
        $logoSrc = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoPath));
    } elseif (is_file($logoPath)) {
        $logoSrc = $logoPath;
    }
    $assignment = \App\Models\LmsAssessmentAssignment::query()
        ->where('assessment_id', $attempt->assessment_id)
        ->orderByRaw('CASE WHEN student_id = ? THEN 0 ELSE 1 END', [(int) $attempt->user_id])
        ->orderByDesc('id')
        ->first();
    $fmtTime = function ($dt) use ($assignment) {
        if (! $dt) {
            return '—';
        }
        if ($assignment) {
            return $assignment->formatInTimezone($dt, 'd/m/Y H:i')
                . (filled($assignment->timezone) ? ' (' . $assignment->timezoneLabel() . ')' : '');
        }

        return optional($dt)->format('d/m/Y H:i') ?? '—';
    };
@endphp

    <table class="top">
        <tr>
            <td style="width:62%;">
                <div class="brand">Berkeley School of Business, Arts &amp; Sciences</div>
                <div class="doc-title">Exam Result</div>
                <div class="doc-sub">{{ $attempt->assessment->title ?? 'Assessment attempt' }}</div>
            </td>
            <td style="width:38%; text-align:right;">
                @if($logoSrc)
                    <img class="logo" src="{{ $logoSrc }}" alt="Logo">
                @endif
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr><td class="muted">Test Taker</td><td>{{ $attempt->user->name ?? '—' }} ({{ $attempt->user->email ?? '' }})</td></tr>
        <tr><td class="muted">Course</td><td>{{ $attempt->assessment->course->title ?? '—' }}</td></tr>
        <tr><td class="muted">Type</td><td>{{ $attempt->assessment->kindLabel() }}</td></tr>
        <tr><td class="muted">Status</td><td>{{ $attempt->statusLabel() }}</td></tr>
        <tr><td class="muted">Score</td><td class="score">
            @if($attempt->score_raw !== null)
                {{ lms_format_number($attempt->score_raw) }} / {{ lms_format_number($attempt->score_max) }}
                @if($attempt->percent() !== null) ({{ lms_format_number($attempt->percent()) }}%) @endif
            @else — @endif
        </td></tr>
        <tr><td class="muted">Test ID</td><td>{{ $attempt->assessment->displayCode() }}</td></tr>
        <tr><td class="muted">Duration given</td><td>{{ $attempt->assessment->duration_minutes ? $attempt->assessment->duration_minutes . ' min' : '—' }}</td></tr>
        @if($assignment)
            <tr><td class="muted">Exam appointment</td><td>{{ $assignment->appointmentWindowLabel('d/m/Y H:i') }}</td></tr>
        @endif
        <tr><td class="muted">Start time</td><td>{{ $fmtTime($attempt->started_at) }}</td></tr>
        <tr><td class="muted">End time</td><td>{{ $fmtTime($attempt->submitted_at) }}</td></tr>
        <tr><td class="muted">Spend duration</td><td>{{ ($m = $attempt->spendMinutes()) !== null ? $m . ' min' : '—' }}</td></tr>
        @if($assignment && filled($assignment->timezone))
            <tr><td class="muted">Timezone</td><td>{{ $assignment->timezoneLabel() }}</td></tr>
        @endif
        <tr><td class="muted">Assessor</td><td>{{ $attempt->assessorLabel() }}</td></tr>
        <tr><td class="muted">Published</td><td>{{ $attempt->results_published ? 'Yes' : 'No' }}</td></tr>
    </table>

    @if($attempt->grader_feedback)
        <h2>Overall feedback</h2>
        <div class="feedback">{!! strip_tags($attempt->grader_feedback, '<p><br><strong><em><ul><ol><li><b><i>') !!}</div>
    @endif

    <h2>Questions &amp; answers</h2>
    @foreach($attempt->assessment->questions as $i => $question)
        @php $answer = $attempt->answers->firstWhere('question_id', $question->id); @endphp
        <div class="q">
            <div class="q-title">Q{{ $i + 1 }}. {{ $question->typeLabel() }} · {{ lms_format_number($question->max_marks) }} marks</div>
            <div class="q-stem">{!! strip_tags($question->stem, '<p><br><strong><em><ul><ol><li><b><i>') !!}</div>
            <div class="q-answer">
                @if($question->isMcq())
                    <span class="label">Answer</span>
                    <div class="body">
                        Selected: {{ $answer?->selectedKey() ?? '—' }}
                        @if($answer && $answer->is_correct !== null)
                            · {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                        @endif
                    </div>
                @else
                    <span class="label">Answer</span>
                    <div class="body">{!! nl2br(e($answer?->textAnswer() ?: '—')) !!}</div>
                @endif
            </div>
            <p><strong>Score:</strong>
                @if($answer)
                    {{ lms_format_number($answer->finalScore()) }} / {{ lms_format_number($answer->max_marks) }}
                @else
                    —
                @endif
            </p>
            @if($answer && $answer->feedback)
                <div class="feedback"><strong>Feedback:</strong><br>{!! strip_tags($answer->feedback, '<p><br><strong><em><ul><ol><li><b><i>') !!}</div>
            @endif
        </div>
    @endforeach

    @include('partials.invoice-document-footer-pdf')
</body>
</html>
