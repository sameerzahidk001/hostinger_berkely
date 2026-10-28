<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Test Result PDF</title>
    <style>
        @page { margin: 15mm; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #333; font-size: 12px; line-height: 1.45; }
        .top { width: 100%; margin-bottom: 14px; border-bottom: 1px solid #e5e5e5; padding-bottom: 10px; }
        .top td { vertical-align: middle; }
        .brand { font-size: 14px; font-weight: bold; color: #1a365d; }
        .doc-title { font-size: 16px; font-weight: bold; margin-top: 2px; text-transform: uppercase; }
        .doc-sub { font-size: 11px; color: #444; margin-top: 4px; }
        .doc-sub-muted { font-size: 10px; color: #777; margin-top: 2px; }
        .logo { height: 48px; max-width: 180px; }
        h2 { font-size: 14px; margin: 12px 0 6px; border-bottom: 1px solid #ddd; padding-bottom: 4px; page-break-after: avoid; page-break-inside: avoid; }
        .meta { margin-bottom: 8px; }
        .meta td { padding: 2px 8px 2px 0; vertical-align: top; }
        .q { margin: 10px 0; padding: 10px; border: 1px solid #e5e5e5; page-break-inside: auto; page-break-before: avoid; }
        .q-title { font-weight: bold; margin-bottom: 8px; color: #000; }
        .q-stem { margin: 0 0 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; color: #000; }
        .q-stem * { color: #000; }
        .q-answer { margin-top: 16px; }
        .q-answer .label { font-weight: bold; margin-bottom: 8px; display: block; color: #000; }
        .q-answer .body { margin: 0 0 10px; padding: 10px 12px; background: #fafafa; border: 1px solid #eee; color: #0056b3; }
        .q-answer .body * { color: #0056b3; }
        .muted { color: #777; }
        .score { font-size: 14px; font-weight: bold; }
        .feedback { background: #f7f7f7; padding: 8px; margin-top: 10px; color: #cc0000; }
        .feedback * { color: #cc0000; }
        .footer {
            border-top: 1px solid #eee;
            padding-top: 8px;
            margin-top: 12px;
            font-size: 9px;
            color: #777;
            text-align: center;
            line-height: 1.45;
            page-break-before: auto;
            page-break-inside: avoid;
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
    $assessmentSvc = app(\App\Services\LmsAssessmentService::class);
    $assignments = \App\Models\LmsAssessmentAssignment::query()
        ->where('assessment_id', $attempt->assessment_id)
        ->orderBy('id')
        ->get();
    $assignment = $assignments->first(
        fn ($asg) => $assessmentSvc->attemptBelongsToAssignment($attempt, $asg)
    ) ?: $assignments->sortByDesc('id')->first(
        fn ($asg) => (int) ($asg->student_id ?? 0) === (int) $attempt->user_id
    ) ?: $assignments->sortByDesc('id')->first();
    $attemptMeta = $assessmentSvc->sittingAttemptMeta(
        (int) $attempt->user_id,
        $attempt->assessment,
        $assignment
    );
    $kindLabel = strtoupper((string) ($attempt->assessment->kindLabel() ?? 'EXAM'));
    $fmtTime = function ($dt) use ($assignment) {
        if (! $dt) {
            return '—';
        }
        if ($assignment) {
            return $assignment->formatInTimezone($dt, 'd/m/Y H:i');
        }

        return optional($dt)->format('d/m/Y H:i') ?? '—';
    };
    $spent = $attempt->spendMinutes();
    $publishedOn = $attempt->results_published
        ? ($attempt->results_published_at
            ? $fmtTime($attempt->results_published_at)
            : 'Yes')
        : 'No';
@endphp

    <table class="top">
        <tr>
            <td style="width:62%;">
                <div class="brand">Berkeley School of Business, Arts &amp; Sciences</div>
                <div class="doc-title">{{ $kindLabel }} RESULT</div>
                <div class="doc-sub">{{ $attempt->assessment->course->title ?? '—' }}</div>
                <div class="doc-sub">{{ $attempt->assessment->title ?? 'Assessment attempt' }}</div>
                <div class="doc-sub-muted">Attempt {{ $attemptMeta['number'] }} of {{ $attemptMeta['total'] }}</div>
            </td>
            <td style="width:38%; text-align:right;">
                @if($logoSrc)
                    <img class="logo" src="{{ $logoSrc }}" alt="Logo">
                @endif
            </td>
        </tr>
    </table>

    <table class="meta">
        <tr><td class="muted">Test Taker Name</td><td>{{ $attempt->user->name ?? '—' }} ({{ $attempt->user->email ?? '' }})</td></tr>
        @if(! $attempt->assessment->hidesOverallMarks())
        <tr><td class="muted">Test Result</td><td class="score">
            @if($attempt->score_raw !== null)
                {{ lms_format_number($attempt->score_raw) }} / {{ lms_format_number($attempt->score_max) }}
                @if($attempt->percent() !== null) ({{ lms_format_number($attempt->percent()) }}%) @endif
            @else — @endif
        </td></tr>
        @endif
        <tr><td class="muted">Appointment ID</td><td>{{ $assignment ? $assignment->displayCode() : '—' }}</td></tr>
        <tr><td class="muted">Test Appointment Time</td><td>{{ $assignment ? $assignment->appointmentWindowLabel('d/m/Y H:i') : '—' }}</td></tr>
        <tr><td class="muted">Test ID</td><td>{{ $attempt->assessment->displayCode() }}</td></tr>
        <tr><td class="muted">Test Duration</td><td>{{ ($d = $attempt->assessment->durationMinutes()) ? $d . ' min' : '—' }}</td></tr>
        <tr><td class="muted">Started Time</td><td>{{ $fmtTime($attempt->started_at) }}</td></tr>
        <tr><td class="muted">Ended Time</td><td>{{ $fmtTime($attempt->submitted_at) }}</td></tr>
        <tr><td class="muted">Spent Duration</td><td>{{ $spent !== null ? $spent . ' min' : '—' }}</td></tr>
        @if($attempt->pauseSecondsTotal() > 0)
            <tr><td class="muted">Pause Duration</td><td>{{ $attempt->pauseDurationLabel() }}</td></tr>
            @foreach($attempt->pauseLog() as $pi => $plog)
                @php
                    $pStart = !empty($plog['started_at']) ? \Carbon\Carbon::parse($plog['started_at']) : null;
                    $pEnd = !empty($plog['ended_at']) ? \Carbon\Carbon::parse($plog['ended_at']) : null;
                @endphp
                <tr><td class="muted">Pause {{ $pi + 1 }}</td><td>Started {{ $fmtTime($pStart) }} · Ended {{ $fmtTime($pEnd) }}</td></tr>
            @endforeach
        @endif
        <tr><td class="muted">Assessor Name</td><td>{{ $attempt->assessorLabel($assignment) }}</td></tr>
        <tr><td class="muted">Published on</td><td>{{ $publishedOn }}</td></tr>
    </table>

    <?php
        $pdfAllow = '<p><br><strong><em><ul><ol><li><b><i>';
        $descPlain = trim(strip_tags((string) ($attempt->assessment->description ?? '')));
        if ($descPlain !== '') {
            echo '<h2>Test instructions</h2><div>' . strip_tags((string) $attempt->assessment->description, $pdfAllow) . '</div>';
        }
        if ($attempt->grader_feedback) {
            echo '<h2>Overall feedback</h2><div class="feedback">' . strip_tags((string) $attempt->grader_feedback, $pdfAllow) . '</div>';
        }
    ?>

    <h2 style="page-break-after:avoid;">Questions &amp; answers</h2>
    <?php
        $pdfAllow = '<p><br><strong><em><ul><ol><li><b><i>';
        $pdfHtml = static function ($html) use ($pdfAllow) {
            return strip_tags((string) $html, $pdfAllow);
        };
        foreach ($attempt->assessment->questions as $i => $question) {
            $answer = $attempt->answers->firstWhere('question_id', $question->id);
            $showMarks = $question->isMcq() || ! $attempt->assessment->hidesOverallMarks();
            $subs = $question->hasSubQuestions() ? $question->subQuestions() : [];
            $explPlain = trim(strip_tags((string) ($question->explanation ?? '')));
            $hasFb = $answer && $answer->feedback && count($subs) === 0;
            echo '<div class="q">';
            echo '<div class="q-title">Q' . ($i + 1) . '. ' . e($question->typeLabel());
            if ($showMarks) {
                echo ' · ' . e(lms_format_number($question->max_marks)) . ' marks';
            }
            echo '</div>';
            echo '<div class="q-stem">' . $pdfHtml($question->stem) . '</div>';
            echo '<div class="q-answer">';
            if ($question->isMcq()) {
                echo '<span class="label">Answer</span><div class="body">Selected: ' . e($answer?->selectedKey() ?? '—');
                if ($answer && $answer->is_correct !== null) {
                    echo ' · ' . ($answer->is_correct ? 'Correct' : 'Incorrect');
                }
                echo '</div>';
            } elseif (count($subs) > 0) {
                foreach ($subs as $si => $sub) {
                    $sk = (string) ($sub['key'] ?? $si);
                    $partFb = $answer ? trim(strip_tags((string) $answer->partFeedback($sk))) : '';
                    echo '<span class="label">(' . chr(97 + (int) $si) . ') ' . e((string) ($sub['text'] ?? '')) . '</span>';
                    echo '<div class="body">' . nl2br(e($answer?->partAnswer($sk) ?: '—')) . '</div>';
                    if ($showMarks) {
                        echo '<p><strong>Marks:</strong> ' . e(lms_format_number($answer?->partScore($sk))) . ' / ' . e(lms_format_number($sub['marks'] ?? 0)) . '</p>';
                    }
                    if ($partFb !== '') {
                        echo '<div class="feedback"><strong>Feedback:</strong><br>' . $pdfHtml($answer->partFeedback($sk)) . '</div>';
                    }
                }
            } else {
                echo '<span class="label">Answer</span><div class="body">' . nl2br(e($answer?->textAnswer() ?: '—')) . '</div>';
            }
            echo '</div>';
            if ($showMarks) {
                $scoreText = $answer
                    ? lms_format_number($answer->finalScore()) . ' / ' . lms_format_number($answer->max_marks)
                    : '—';
                echo '<p><strong>Score:</strong> ' . e($scoreText) . '</p>';
            }
            if ($hasFb) {
                echo '<div class="feedback"><strong>Feedback:</strong><br>' . $pdfHtml($answer->feedback) . '</div>';
            }
            if ($explPlain !== '') {
                echo '<div class="feedback"><strong>Answer explanation:</strong><br>' . $pdfHtml($question->explanation) . '</div>';
            }
            echo '</div>';
        }
    ?>

    @include('partials.invoice-document-footer-pdf')
</body>
</html>
