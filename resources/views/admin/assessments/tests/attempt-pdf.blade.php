<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attempt PDF</title>
    <style>
        @page { margin: 14mm; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; color: #333; font-size: 12px; line-height: 1.45; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 18px 0 8px; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .meta td { padding: 2px 8px 2px 0; vertical-align: top; }
        .q { margin: 12px 0; padding: 10px; border: 1px solid #e5e5e5; page-break-inside: avoid; }
        .q-title { font-weight: bold; margin-bottom: 6px; }
        .muted { color: #777; }
        .score { font-size: 14px; font-weight: bold; }
        .feedback { background: #f7f7f7; padding: 8px; margin-top: 6px; }
    </style>
</head>
<body>
    <h1>{{ $attempt->assessment->title ?? 'Assessment attempt' }}</h1>
    <table class="meta">
        <tr><td class="muted">Test Taker</td><td>{{ $attempt->user->name ?? '—' }} ({{ $attempt->user->email ?? '' }})</td></tr>
        <tr><td class="muted">Course</td><td>{{ $attempt->assessment->course->title ?? '—' }}</td></tr>
        <tr><td class="muted">Type</td><td>{{ $attempt->assessment->kindLabel() }}</td></tr>
        <tr><td class="muted">Status</td><td>{{ $attempt->statusLabel() }}</td></tr>
        <tr><td class="muted">Score</td><td class="score">
            @if($attempt->score_raw !== null)
                {{ number_format((float) $attempt->score_raw, 2) }} / {{ number_format((float) $attempt->score_max, 2) }}
                @if($attempt->percent() !== null) ({{ number_format((float) $attempt->percent(), 2) }}%) @endif
            @else — @endif
        </td></tr>
        <tr><td class="muted">Duration given</td><td>{{ $attempt->assessment->duration_minutes ? $attempt->assessment->duration_minutes . ' min' : '—' }}</td></tr>
        <tr><td class="muted">Started</td><td>{{ optional($attempt->started_at)->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="muted">Submitted</td><td>{{ optional($attempt->submitted_at)->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><td class="muted">Published</td><td>{{ $attempt->results_published ? 'Yes' : 'No' }}</td></tr>
        <tr><td class="muted">Generated</td><td>{{ now()->format('d/m/Y H:i') }}</td></tr>
    </table>

    @if($attempt->grader_feedback)
        <h2>Overall feedback</h2>
        <div class="feedback">{!! strip_tags($attempt->grader_feedback, '<p><br><strong><em><ul><ol><li><b><i>') !!}</div>
    @endif

    <h2>Questions &amp; answers</h2>
    @foreach($attempt->assessment->questions as $i => $question)
        @php $answer = $attempt->answers->firstWhere('question_id', $question->id); @endphp
        <div class="q">
            <div class="q-title">Q{{ $i + 1 }}. {{ $question->typeLabel() }} · {{ number_format((float) $question->max_marks, 2) }} marks</div>
            <div>{!! strip_tags($question->stem, '<p><br><strong><em><ul><ol><li><b><i>') !!}</div>
            @if($question->isMcq())
                <p><strong>Selected:</strong> {{ $answer?->selectedKey() ?? '—' }}
                    @if($answer && $answer->is_correct !== null)
                        · {{ $answer->is_correct ? 'Correct' : 'Incorrect' }}
                    @endif
                </p>
            @else
                <p><strong>Answer:</strong></p>
                <div>{!! nl2br(e($answer?->textAnswer() ?: '—')) !!}</div>
            @endif
            <p><strong>Score:</strong>
                @if($answer)
                    {{ number_format($answer->finalScore(), 2) }} / {{ number_format((float) $answer->max_marks, 2) }}
                @else
                    —
                @endif
            </p>
            @if($answer && $answer->feedback)
                <div class="feedback"><strong>Feedback:</strong><br>{!! strip_tags($answer->feedback, '<p><br><strong><em><ul><ol><li><b><i>') !!}</div>
            @endif
        </div>
    @endforeach
</body>
</html>
