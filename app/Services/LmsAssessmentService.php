<?php

namespace App\Services;

use App\Models\ClassBatch;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAssignment;
use App\Models\LmsAssessmentAttempt;
use App\Models\LmsAttemptAnswer;
use App\Models\LmsQuestion;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class LmsAssessmentService
{
    public function __construct(protected StudyMaterialService $lms)
    {
    }

    public function ensure(): bool
    {
        return LmsAssessmentSchema::ensure();
    }

    public function canManageAssessments(): bool
    {
        return $this->canManageQuestionBank()
            || $this->canManageStudentAssessments()
            || $this->canManageSkillTests();
    }

    /** Question Bank create/edit (instructors need Admin LMS tick). */
    public function canManageQuestionBank(): bool
    {
        if ($this->lms->isAdminActor() || panel_sees_all_dashboards()) {
            return true;
        }

        return $this->lms->isInstructorActor()
            && $this->lms->actorHasLmsPermission('manage_question_bank');
    }

    /** Practice / Mock create & assign for instructor’s assigned courses. */
    public function canManageStudentAssessments(): bool
    {
        if ($this->lms->isAdminActor() || panel_sees_all_dashboards()) {
            return true;
        }

        return $this->lms->isInstructorActor()
            && $this->lms->actorHasLmsPermission('manage_student_assessments');
    }

    public function canManageSkillTests(): bool
    {
        return $this->lms->isAdminActor() || panel_sees_all_dashboards();
    }

    /** Staff roles that can be assigned assessments (CW, Accountant, Instructor). */
    public function staffRoleNames(): array
    {
        return ['instructor', 'accountant', 'content_writer', 'content-writer'];
    }

    /**
     * Assessments visible to a user (assigned via batch or direct, or skill for staff).
     *
     * @return Collection<int, array{assessment: LmsAssessment, assignment: ?LmsAssessmentAssignment}>
     */
    public function availableAssessmentsForUser(User $user, ?string $kind = null): Collection
    {
        $this->ensure();
        $userId = (int) $user->id;
        $isStaff = $user->roles()->whereIn('name', $this->staffRoleNames())->exists();

        $batchIds = [];
        if (Schema::hasTable('class_batch_student')) {
            $batchIds = ClassBatch::query()
                ->whereHas('students', fn ($q) => $q->where('users.id', $userId))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $query = LmsAssessment::query()
            ->with(['assignments.batch', 'assignments.student', 'questions'])
            ->where('status', 'published');

        if ($kind) {
            $query->where('kind', $kind);
        }

        $assessments = $query->orderByDesc('id')->get();
        $out = collect();

        foreach ($assessments as $assessment) {
            $assignment = $assessment->assignments->first(function (LmsAssessmentAssignment $a) use ($userId, $batchIds) {
                if ($a->student_id && (int) $a->student_id === $userId) {
                    return true;
                }
                if ($a->class_batch_id && in_array((int) $a->class_batch_id, $batchIds, true)) {
                    return true;
                }

                return false;
            });

            if ($assessment->isSkill() || $assessment->audience === LmsAssessment::AUDIENCE_STAFF) {
                if (! $isStaff) {
                    continue;
                }
                // Unassigned skill tests are available to all staff; assigned ones must match.
                if ($assessment->assignments->isNotEmpty() && ! $assignment) {
                    continue;
                }
                $out->push(['assessment' => $assessment, 'assignment' => $assignment]);
                continue;
            }

            // Practice / Mock / General: assignable to students and staff (CW, Accountant, Instructor).
            if (! $assignment) {
                continue;
            }

            $out->push(['assessment' => $assessment, 'assignment' => $assignment]);
        }

        return $out;
    }

    public function userCanAccessAssessment(User $user, LmsAssessment $assessment): bool
    {
        return $this->availableAssessmentsForUser($user, $assessment->kind)
            ->contains(fn ($row) => (int) $row['assessment']->id === (int) $assessment->id);
    }

    public function startAttempt(User $user, LmsAssessment $assessment): LmsAssessmentAttempt
    {
        $this->ensure();
        abort_unless($assessment->isPublished(), 403);
        abort_unless($this->userCanAccessAssessment($user, $assessment), 403);

        $access = $this->availableAssessmentsForUser($user, $assessment->kind)
            ->first(fn ($row) => (int) $row['assessment']->id === (int) $assessment->id);
        $assignment = $access['assignment'] ?? null;
        if ($assignment && ! $assignment->isAvailableNow()) {
            abort(403, $assignment->isNotYetOpen()
                ? 'This exam is not open yet.'
                : 'The availability window for this exam has ended.');
        }

        $open = LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($open) {
            if ($open->isTimedOut()) {
                $this->submitAttempt($open, true);
            } else {
                return $open;
            }
        }

        if ($assessment->max_attempts) {
            $count = LmsAssessmentAttempt::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $user->id)
                ->count();
            abort_unless($count < (int) $assessment->max_attempts, 403, 'Maximum attempts reached.');
        }

        $attempt = new LmsAssessmentAttempt();
        $attempt->assessment_id = $assessment->id;
        $attempt->user_id = $user->id;
        $attempt->status = 'in_progress';
        $attempt->started_at = now();
        if ($assessment->duration_minutes) {
            $attempt->expires_at = now()->addMinutes((int) $assessment->duration_minutes);
        }
        $attempt->save();

        return $attempt;
    }

    public function saveAnswers(LmsAssessmentAttempt $attempt, array $answersByQuestionId): void
    {
        abort_unless($attempt->isInProgress(), 403);
        if ($attempt->isTimedOut()) {
            $this->submitAttempt($attempt, true);

            return;
        }

        $assessment = $attempt->assessment()->with('questions')->first();
        $questions = $assessment->questions->keyBy('id');

        foreach ($answersByQuestionId as $questionId => $payload) {
            $qid = (int) $questionId;
            if (! $questions->has($qid)) {
                continue;
            }
            /** @var LmsQuestion $question */
            $question = $questions->get($qid);
            $marks = (float) ($question->pivot->marks_override ?? $question->max_marks);

            $answer = LmsAttemptAnswer::query()->firstOrNew([
                'attempt_id' => $attempt->id,
                'question_id' => $qid,
            ]);
            $answer->max_marks = $marks;

            if ($question->isMcq()) {
                $selected = isset($payload['selected']) ? (string) $payload['selected'] : null;
                $answer->answer_json = ['selected' => $selected];
            } else {
                $answer->answer_json = ['text' => trim((string) ($payload['text'] ?? ''))];
            }
            $answer->save();
        }
    }

    public function submitAttempt(LmsAssessmentAttempt $attempt, bool $expired = false): LmsAssessmentAttempt
    {
        if (! in_array($attempt->status, ['in_progress'], true)) {
            return $attempt;
        }

        $assessment = $attempt->assessment()->with('questions')->first();
        $this->autoMarkMcqs($attempt, $assessment);

        $attempt->status = $expired ? 'expired' : 'submitted';
        $attempt->submitted_at = now();
        $this->recalculateScores($attempt);

        $needsManual = $assessment->questions->contains(fn (LmsQuestion $q) => $q->needsManualMark());
        if (! $needsManual) {
            $attempt->status = 'graded';
            $attempt->graded_at = now();
            if ($assessment->results_mode === 'instant_mcq') {
                $attempt->results_published = true;
                $attempt->results_published_at = now();
            }
        }
        $attempt->save();

        return $attempt->fresh(['assessment', 'answers.question']);
    }

    public function autoMarkMcqs(LmsAssessmentAttempt $attempt, LmsAssessment $assessment): void
    {
        foreach ($assessment->questions as $question) {
            if (! $question->isMcq()) {
                continue;
            }
            $answer = LmsAttemptAnswer::query()->firstOrNew([
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ]);
            $marks = (float) ($question->pivot->marks_override ?? $question->max_marks);
            $answer->max_marks = $marks;
            $selected = $answer->selectedKey();
            $correct = $question->correctOptionKeys();
            $isCorrect = $selected !== null && in_array($selected, $correct, true);
            $answer->is_correct = $isCorrect;
            $answer->auto_score = $isCorrect ? $marks : 0;
            $answer->save();
        }
    }

    public function recalculateScores(LmsAssessmentAttempt $attempt): void
    {
        $answers = LmsAttemptAnswer::query()->where('attempt_id', $attempt->id)->get();
        $raw = 0.0;
        $max = 0.0;
        foreach ($answers as $answer) {
            $raw += $answer->finalScore();
            $max += (float) $answer->max_marks;
        }

        // Include unanswered attached questions in max
        $assessment = $attempt->assessment()->with('questions')->first();
        if ($assessment) {
            $answeredIds = $answers->pluck('question_id')->map(fn ($id) => (int) $id)->all();
            foreach ($assessment->questions as $q) {
                if (! in_array((int) $q->id, $answeredIds, true)) {
                    $max += (float) ($q->pivot->marks_override ?? $q->max_marks);
                }
            }
        }

        $attempt->score_raw = round($raw, 2);
        $attempt->score_max = round($max, 2);
        $passPercent = $assessment?->pass_percent;
        if ($passPercent !== null && $max > 0) {
            $attempt->passed = (($raw / $max) * 100) >= (float) $passPercent;
        } else {
            $attempt->passed = null;
        }
    }

    public function publishResults(LmsAssessmentAttempt $attempt, ?string $feedback = null): void
    {
        $this->recalculateScores($attempt);
        if ($feedback !== null) {
            $attempt->grader_feedback = $feedback;
        }
        $attempt->results_published = true;
        $attempt->results_published_at = now();
        if ($attempt->status === 'submitted' || $attempt->status === 'expired') {
            $attempt->status = 'graded';
            $attempt->graded_at = now();
        }
        if ($this->lms->isAdminActor()) {
            $attempt->graded_by_admin_id = Auth::guard('admin')->id();
        } else {
            $attempt->graded_by_user_id = Auth::id();
        }
        $attempt->save();
    }

    public function markManualAnswer(LmsAttemptAnswer $answer, float $score, ?string $feedback = null): void
    {
        $answer->manual_score = max(0, min($score, (float) $answer->max_marks));
        $answer->feedback = $feedback;
        $answer->is_correct = $answer->manual_score >= ((float) $answer->max_marks * 0.5);
        $answer->marked_at = now();
        if ($this->lms->isAdminActor()) {
            $answer->marked_by_admin_id = Auth::guard('admin')->id();
        } else {
            $answer->marked_by_user_id = Auth::id();
        }
        $answer->save();

        $attempt = $answer->attempt;
        $this->recalculateScores($attempt);
        $attempt->status = 'graded';
        $attempt->graded_at = now();
        $attempt->save();
    }

    /**
     * @return array{pass_rate: ?float, attempts: int, passed: int, avg_percent: ?float}
     */
    public function passRateReport(?int $assessmentId = null, ?int $batchId = null): array
    {
        $this->ensure();
        $query = LmsAssessmentAttempt::query()
            ->whereIn('status', ['submitted', 'graded', 'expired'])
            ->whereNotNull('score_max')
            ->where('score_max', '>', 0);

        if ($assessmentId) {
            $query->where('assessment_id', $assessmentId);
        }
        if ($batchId) {
            $studentIds = ClassBatch::find($batchId)?->students()->pluck('users.id')->all() ?? [];
            $query->whereIn('user_id', $studentIds ?: [0]);
        }

        $rows = $query->get();
        $attempts = $rows->count();
        $passed = $rows->where('passed', true)->count();
        $avg = $attempts
            ? round($rows->avg(fn ($r) => ((float) $r->score_raw / (float) $r->score_max) * 100), 1)
            : null;

        return [
            'pass_rate' => $attempts ? round(($passed / $attempts) * 100, 1) : null,
            'attempts' => $attempts,
            'passed' => $passed,
            'avg_percent' => $avg,
        ];
    }

    /**
     * MCQ item analysis: % correct per question.
     *
     * @return Collection<int, array{question: LmsQuestion, attempts: int, correct: int, percent: ?float}>
     */
    public function mcqItemAnalysis(int $assessmentId): Collection
    {
        $this->ensure();
        $assessment = LmsAssessment::with('questions')->findOrFail($assessmentId);
        $attemptIds = LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessmentId)
            ->whereIn('status', ['submitted', 'graded', 'expired'])
            ->pluck('id');

        $out = collect();
        foreach ($assessment->questions->where('type', LmsQuestion::TYPE_MCQ) as $question) {
            $answers = LmsAttemptAnswer::query()
                ->whereIn('attempt_id', $attemptIds)
                ->where('question_id', $question->id)
                ->get();
            $n = $answers->count();
            $correct = $answers->where('is_correct', true)->count();
            $out->push([
                'question' => $question,
                'attempts' => $n,
                'correct' => $correct,
                'percent' => $n ? round(($correct / $n) * 100, 1) : null,
            ]);
        }

        return $out;
    }

    /**
     * Skill test report rows for staff.
     */
    public function skillReport(): Collection
    {
        $this->ensure();

        return LmsAssessmentAttempt::query()
            ->with(['user:id,name,email', 'assessment'])
            ->whereHas('assessment', fn ($q) => $q->where('kind', LmsAssessment::KIND_SKILL)
                ->orWhere('audience', LmsAssessment::AUDIENCE_STAFF))
            ->orderByDesc('updated_at')
            ->get();
    }

    public function bucketForUser(User $user, ?string $kind = null): array
    {
        $items = $this->availableAssessmentsForUser($user, $kind);
        $available = collect();
        $upcoming = collect();
        $inProgress = collect();
        $submitted = collect();
        $results = collect();
        $missed = collect();

        foreach ($items as $row) {
            /** @var LmsAssessment $assessment */
            $assessment = $row['assessment'];
            /** @var LmsAssessmentAssignment|null $assignment */
            $assignment = $row['assignment'];

            $attempts = LmsAssessmentAttempt::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->get();

            $open = $attempts->firstWhere('status', 'in_progress');
            if ($open) {
                if ($open->isTimedOut()) {
                    $this->submitAttempt($open, true);
                    $open = $open->fresh();
                } else {
                    $inProgress->push(['assessment' => $assessment, 'assignment' => $assignment, 'attempt' => $open]);
                    continue;
                }
            }

            $done = $attempts->first(fn ($a) => in_array($a->status, ['submitted', 'graded', 'expired'], true));
            if ($done && $done->results_published) {
                $results->push(['assessment' => $assessment, 'assignment' => $assignment, 'attempt' => $done]);
            } elseif ($done) {
                $submitted->push(['assessment' => $assessment, 'assignment' => $assignment, 'attempt' => $done]);
            } elseif ($assignment && $assignment->isExpired()) {
                $missed->push(['assessment' => $assessment, 'assignment' => $assignment, 'attempt' => null]);
            } elseif ($assignment && $assignment->isNotYetOpen()) {
                $upcoming->push(['assessment' => $assessment, 'assignment' => $assignment, 'attempt' => null]);
            } else {
                // Open window (or no schedule) — ready to start, not "upcoming".
                $available->push(['assessment' => $assessment, 'assignment' => $assignment, 'attempt' => null]);
            }
        }

        return compact('available', 'upcoming', 'inProgress', 'submitted', 'results', 'missed');
    }
}
