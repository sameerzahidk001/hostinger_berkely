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

    /** Practice / Mock list, mark, reports (create and/or assign). */
    public function canManageStudentAssessments(): bool
    {
        return $this->canCreateStudentAssessments() || $this->canAssignStudentAssessments();
    }

    /** Create / edit assessment content for instructor’s assigned courses. */
    public function canCreateStudentAssessments(): bool
    {
        if ($this->lms->isAdminActor() || panel_sees_all_dashboards()) {
            return true;
        }

        return $this->lms->isInstructorActor()
            && $this->lms->actorHasLmsPermission('create_student_assessments');
    }

    /** Assign assessments to batches / users for instructor’s assigned courses. */
    public function canAssignStudentAssessments(): bool
    {
        if ($this->lms->isAdminActor() || panel_sees_all_dashboards()) {
            return true;
        }

        return $this->lms->isInstructorActor()
            && $this->lms->actorHasLmsPermission('assign_student_assessments');
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
     * One row per assignment sitting — re-assigning the same test creates another row.
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
            ->with(['course:id,title,slug', 'assignments.batch', 'assignments.student', 'assignments.marker:id,name,email', 'questions'])
            ->where('status', 'published');

        if ($kind) {
            $query->where('kind', $kind);
        }

        $assessments = $query->orderByDesc('id')->get();
        $out = collect();

        foreach ($assessments as $assessment) {
            $matches = $assessment->assignments
                ->filter(function (LmsAssessmentAssignment $a) use ($userId, $batchIds) {
                    if ($a->student_id && (int) $a->student_id === $userId) {
                        return true;
                    }
                    if ($a->class_batch_id && in_array((int) $a->class_batch_id, $batchIds, true)) {
                        return true;
                    }

                    return false;
                })
                ->sortByDesc(fn (LmsAssessmentAssignment $a) => (int) $a->id)
                ->values();
            $matches->each(fn (LmsAssessmentAssignment $a) => $a->setRelation('assessment', $assessment));

            if ($assessment->isSkill() || $assessment->audience === LmsAssessment::AUDIENCE_STAFF) {
                if (! $isStaff) {
                    continue;
                }
                // Unassigned skill tests are available to all staff; assigned ones must match.
                if ($assessment->assignments->isNotEmpty() && $matches->isEmpty()) {
                    continue;
                }
                if ($matches->isEmpty()) {
                    $out->push(['assessment' => $assessment, 'assignment' => null]);
                    continue;
                }
                foreach ($matches as $assignment) {
                    $out->push(['assessment' => $assessment, 'assignment' => $assignment]);
                }
                continue;
            }

            // Practice / Mock / General: one portal row per sitting of this same test.
            foreach ($matches as $assignment) {
                $out->push(['assessment' => $assessment, 'assignment' => $assignment]);
            }
        }

        return $out;
    }

    /** Whether an attempt belongs to this assignment sitting (window, or created_at if open-ended). */
    public function attemptBelongsToAssignment(LmsAssessmentAttempt $att, ?LmsAssessmentAssignment $asg): bool
    {
        if (! $asg || ! $att->started_at) {
            return false;
        }
        if ($asg->available_from) {
            $end = $asg->appointmentEnd() ?: $asg->available_from->copy()->addDays(7);

            return $att->started_at->gte($asg->available_from->copy()->subMinutes(5))
                && $att->started_at->lte($end->copy()->addDay());
        }
        if ($asg->created_at) {
            return $att->started_at->gte($asg->created_at->copy()->subMinutes(5));
        }

        return false;
    }

    /**
     * Attempt N of T for this user's sittings of a test (oldest assignment = 1).
     *
     * @return array{number:int,total:int}
     */
    public function sittingAttemptMeta(int $userId, LmsAssessment $assessment, ?LmsAssessmentAssignment $assignment): array
    {
        if (! $assignment) {
            return ['number' => 1, 'total' => 1];
        }

        $batchIds = [];
        if (Schema::hasTable('class_batch_student')) {
            $batchIds = ClassBatch::query()
                ->whereHas('students', fn ($q) => $q->where('users.id', $userId))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $sittings = LmsAssessmentAssignment::query()
            ->where('assessment_id', $assessment->id)
            ->where(function ($q) use ($userId, $batchIds) {
                $q->where('student_id', $userId);
                if ($batchIds !== []) {
                    $q->orWhereIn('class_batch_id', $batchIds);
                }
            })
            ->orderBy('id')
            ->get(['id']);

        $total = max(1, $sittings->count());
        $number = 1;
        foreach ($sittings->values() as $i => $asg) {
            if ((int) $asg->id === (int) $assignment->id) {
                $number = $i + 1;
                break;
            }
        }

        return ['number' => $number, 'total' => $total];
    }

    /**
     * Open assignment sittings for this user/test that still have no attempt.
     * Retake = admin Assigns a new sitting; student may start anytime inside that window.
     *
     * @return Collection<int, LmsAssessmentAssignment>
     */
    public function openUnusedAssignmentsFor(User $user, LmsAssessment $assessment): Collection
    {
        $attempts = LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();

        return $this->availableAssessmentsForUser($user, $assessment->kind)
            ->filter(fn ($row) => (int) $row['assessment']->id === (int) $assessment->id)
            ->map(fn ($row) => $row['assignment'])
            ->filter()
            ->filter(function (LmsAssessmentAssignment $asg) use ($attempts) {
                if (! $asg->isAvailableNow()) {
                    return false;
                }

                return ! $attempts->contains(fn ($att) => $this->attemptBelongsToAssignment($att, $asg));
            })
            ->values();
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

        $accessRows = $this->availableAssessmentsForUser($user, $assessment->kind)
            ->filter(fn ($row) => (int) $row['assessment']->id === (int) $assessment->id)
            ->values();
        abort_unless($accessRows->isNotEmpty(), 403);

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

        // Must have an open assigned sitting with no attempt yet (retake = new Assign).
        // Exception: unassigned skill tests stay open to staff without a sitting row.
        $assignment = $this->openUnusedAssignmentsFor($user, $assessment)->first();
        $hasExplicitAssign = $accessRows->contains(fn ($row) => $row['assignment'] !== null);
        if (! $assignment) {
            if ($assessment->isSkill() && ! $hasExplicitAssign) {
                // open skill — no sitting required
            } else {
                $anyAsg = $accessRows->map(fn ($row) => $row['assignment'])->filter()->first();
                if ($anyAsg && $anyAsg->isNotYetOpen()) {
                    abort(403, 'This exam is not open yet.');
                }
                if ($anyAsg && $anyAsg->isExpired()) {
                    abort(403, 'The availability window for this exam has ended.');
                }
                abort(403, 'No open assigned sitting. Ask admin to Assign a retake for this test.');
            }
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
        // No Pass/Fail — marking criteria varies by course; score and % only.
        $attempt->passed = null;
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

    /** How many sittings this user has used for the test. */
    public function attemptsUsed(User $user, LmsAssessment $assessment): int
    {
        return (int) LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->count();
    }

    /**
     * Retake = admin created a new Assign sitting that is open and unused.
     * Not based on Max attempts alone.
     */
    public function canRetake(User $user, LmsAssessment $assessment, ?LmsAssessmentAssignment $assignment = null): bool
    {
        if (! $assessment->isPublished()) {
            return false;
        }
        if (LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->exists()) {
            return false;
        }
        if ($this->attemptsUsed($user, $assessment) < 1) {
            return false;
        }

        return $this->openUnusedAssignmentsFor($user, $assessment)->isNotEmpty();
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

        $assessmentIds = $items->pluck('assessment.id')->unique()->filter()->values()->all();
        $attemptsByAssessment = $assessmentIds === []
            ? collect()
            : LmsAssessmentAttempt::query()
                ->where('user_id', $user->id)
                ->whereIn('assessment_id', $assessmentIds)
                ->orderByDesc('id')
                ->get()
                ->groupBy(fn ($a) => (int) $a->assessment_id);

        // Attempt N of T per assessment (oldest assigned sitting = attempt 1).
        $sittingMeta = [];
        foreach ($items->groupBy(fn ($r) => (int) $r['assessment']->id) as $aid => $group) {
            $ordered = $group->sortBy(fn ($r) => (int) ($r['assignment']->id ?? 0))->values();
            $total = $ordered->count();
            foreach ($ordered as $i => $r) {
                $asgId = (int) ($r['assignment']->id ?? 0);
                $sittingMeta[$aid . ':' . $asgId] = [
                    'number' => $i + 1,
                    'total' => $total,
                ];
            }
        }

        $claimedAttemptIds = [];
        $inProgressShown = [];

        foreach ($items as $row) {
            /** @var LmsAssessment $assessment */
            $assessment = $row['assessment'];
            /** @var LmsAssessmentAssignment|null $assignment */
            $assignment = $row['assignment'];
            $aid = (int) $assessment->id;
            $asgId = (int) ($assignment->id ?? 0);
            $meta = $sittingMeta[$aid . ':' . $asgId] ?? ['number' => 1, 'total' => 1];
            $attemptNumber = (int) $meta['number'];
            $attemptTotal = (int) $meta['total'];

            $attempts = $attemptsByAssessment->get($aid) ?? collect();
            $used = $attempts->count();
            $max = $assessment->max_attempts;
            // 2nd+ assigned sitting = retake path (even if attempt 1 was only missed).
            $isRetakeSitting = $attemptNumber > 1;

            $matched = $attempts->first(function ($att) use ($assignment, $claimedAttemptIds) {
                return ! isset($claimedAttemptIds[(int) $att->id])
                    && $this->attemptBelongsToAssignment($att, $assignment);
            });
            if ($matched) {
                $claimedAttemptIds[(int) $matched->id] = true;
            }

            $base = [
                'assessment' => $assessment,
                'assignment' => $assignment,
                'attempts_used' => $used,
                'max_attempts' => $max,
                'attempt_number' => $attemptNumber,
                'attempt_total' => $attemptTotal,
            ];

            if ($matched && $matched->status === 'in_progress') {
                if ($matched->isTimedOut()) {
                    $this->submitAttempt($matched, true);
                    $matched = $matched->fresh();
                } else {
                    if (! empty($inProgressShown[$aid])) {
                        continue;
                    }
                    $inProgressShown[$aid] = true;
                    $inProgress->push($base + [
                        'attempt' => $matched,
                        'can_retake' => false,
                    ]);
                    continue;
                }
            }

            $done = $matched && in_array($matched->status, ['submitted', 'graded', 'expired'], true)
                ? $matched
                : null;

            // Finished sittings stay in Results/Submitted — next open sitting appears under Available.
            if ($done && $done->results_published) {
                $results->push($base + [
                    'attempt' => $done,
                    'can_retake' => false,
                ]);
                continue;
            }
            if ($done) {
                $submitted->push($base + [
                    'attempt' => $done,
                    'can_retake' => false,
                ]);
                continue;
            }

            if ($assignment && $assignment->isExpired()) {
                $missed->push($base + [
                    'attempt' => null,
                    'can_retake' => false,
                ]);
            } elseif ($assignment && $assignment->isNotYetOpen()) {
                $upcoming->push($base + [
                    'attempt' => null,
                    'can_retake' => false,
                ]);
            } else {
                // Open unused sitting → Available (attempt 2+ labeled as retake).
                $available->push($base + [
                    'attempt' => null,
                    'can_retake' => $isRetakeSitting,
                ]);
            }
        }

        // Result/Submitted rows: Retake only if a separate open unused assigned sitting exists.
        $openRetakeByAssessment = [];
        foreach ($available as $av) {
            if (! empty($av['can_retake'])) {
                $openRetakeByAssessment[(int) $av['assessment']->id] = true;
            }
        }
        $results = $results->map(function ($row) use ($openRetakeByAssessment) {
            $row['can_retake'] = ! empty($openRetakeByAssessment[(int) $row['assessment']->id]);

            return $row;
        });
        $submitted = $submitted->map(function ($row) use ($openRetakeByAssessment) {
            $row['can_retake'] = ! empty($openRetakeByAssessment[(int) $row['assessment']->id]);

            return $row;
        });

        return compact('available', 'upcoming', 'inProgress', 'submitted', 'results', 'missed');
    }
}
