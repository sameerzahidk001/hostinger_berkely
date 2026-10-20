<?php

namespace App\Services;

use App\Mail\UserMail;
use App\Models\ClassBatch;
use App\Models\Email;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAssignment;
use App\Models\LmsAssessmentAttempt;
use App\Models\LmsAttemptAnswer;
use App\Models\LmsQuestion;
use App\Models\StudyMaterialFolder;
use App\Models\StudyMaterialInstructorAccess;
use App\Models\StudyMaterialStudentAccess;
use App\Models\User;
use Database\Seeders\LmsAssessmentEmailTemplateSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class LmsAssessmentService
{
    public function __construct(protected StudyMaterialService $lms)
    {
    }

    public function userPreviewPayload(\App\Models\User $user): array
    {
        $user->loadMissing('roles:id,name');
        $roles = $user->roles
            ->pluck('name')
            ->map(fn ($n) => ucwords(str_replace(['_', '-'], ' ', (string) $n)))
            ->filter()
            ->implode(', ');
        $blank = static fn ($v) => ($v !== null && trim((string) $v) !== '') ? trim((string) $v) : '—';
        $gender = trim((string) ($user->gender ?? ''));
        if ($gender !== '') {
            $gender = ucfirst(strtolower($gender));
        }
        $isInstructor = $user->roles->contains(
            fn ($r) => strtolower((string) $r->name) === 'instructor'
        );
        $facultyProfileUrl = (
            $isInstructor
            && (int) ($user->is_on_web ?? 0) === 1
            && (int) ($user->approved ?? 0) === 1
        ) ? url('/instructor/' . $user->id) : null;

        return [
            'id' => (int) $user->id,
            'name' => $user->name ?: ('User #' . $user->id),
            'email' => $blank($user->email),
            'mobile' => $blank($user->mobile_number),
            'roles' => $roles !== '' ? $roles : '—',
            'gender' => $gender !== '' ? $gender : '—',
            'nationality' => $blank($user->nationality),
            'city' => $blank($user->city),
            'country' => $blank($user->country),
            'address' => $blank($user->address),
            'post_code' => $blank($user->post_code ?? null),
            'faculty_profile_url' => $facultyProfileUrl,
        ];
    }

    public function ensure(): bool
    {
        $ok = LmsAssessmentSchema::ensure();
        StudyMaterialFolder::ensureFolderAssessmentsTable();

        return $ok;
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

    /**
     * Edit / delete / re-assign sittings for a test.
     * Admin login only (not Head-of-Faculty panel bypass). Instructors: create/assign permission
     * AND they created the test. Assessor-only (marker) never gets these actions.
     */
    public function canManageSittingsFor(LmsAssessment $assessment): bool
    {
        // Real Admin panel only — Musa / panel_sees_all must not inherit sitting admin powers.
        if ($this->lms->isAdminActor()) {
            return true;
        }

        if ($assessment->isSkill()) {
            return false;
        }

        if (! $this->canCreateStudentAssessments() && ! $this->canAssignStudentAssessments()) {
            return false;
        }

        $uid = (int) (Auth::id() ?? 0);

        return $uid > 0 && (int) ($assessment->created_by_user_id ?? 0) === $uid;
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
            // Keep disabled tests visible when the user already has attempts (Results), but not for new starts.
            ->where(function ($q) use ($userId) {
                $q->where('status', 'published')
                    ->orWhere(function ($inner) use ($userId) {
                        $inner->where('status', 'disabled')
                            ->whereHas('attempts', fn ($aq) => $aq->where('user_id', $userId));
                    });
            });

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

        if (method_exists(StudyMaterialFolder::class, 'ensureFolderAssessmentsTable')) {
            $this->appendFolderSelfTests($user, $out, $kind);
        }

        return $out;
    }

    /** Active study-material folder IDs this user can open. */
    public function activeFolderIdsForUser(User $user): array
    {
        $ids = [];
        $userId = (int) $user->id;
        if (Schema::hasTable('study_material_student_access')) {
            $ids = array_merge($ids, StudyMaterialStudentAccess::query()
                ->where('student_id', $userId)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('access_till')->orWhereDate('access_till', '>=', now()->toDateString());
                })
                ->pluck('folder_id')
                ->map(fn ($id) => (int) $id)
                ->all());
        }
        if (Schema::hasTable('study_material_instructor_access')) {
            $ids = array_merge($ids, StudyMaterialInstructorAccess::query()
                ->where('instructor_id', $userId)
                ->where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('access_till')->orWhereDate('access_till', '>=', now()->toDateString());
                })
                ->pluck('folder_id')
                ->map(fn ($id) => (int) $id)
                ->all());
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function userHasFolderSelfTest(User $user, LmsAssessment $assessment, ?int $folderId = null): bool
    {
        if (! method_exists(StudyMaterialFolder::class, 'ensureFolderAssessmentsTable')) {
            return false;
        }
        if (! StudyMaterialFolder::ensureFolderAssessmentsTable()) {
            return false;
        }
        $folderIds = $this->activeFolderIdsForUser($user);
        if ($folderIds === []) {
            return false;
        }
        if ($folderId) {
            if (! in_array($folderId, $folderIds, true)) {
                return false;
            }
            $folderIds = [$folderId];
        }

        return Schema::hasTable('study_material_folder_assessments')
            && DB::table('study_material_folder_assessments')
                ->where('assessment_id', $assessment->id)
                ->whereIn('folder_id', $folderIds)
                ->exists();
    }

    /** Attempts for this test that are not tied to an exam/self assignment sitting. */
    public function folderSelfAttemptsUsed(User $user, LmsAssessment $assessment): int
    {
        $attempts = LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->get();
        $assignments = LmsAssessmentAssignment::query()
            ->where('assessment_id', $assessment->id)
            ->get();

        return $attempts->filter(function ($att) use ($assignments) {
            return ! $assignments->contains(fn ($asg) => $this->attemptBelongsToAssignment($att, $asg));
        })->count();
    }

    /** Tests attached to a folder, with Start / Continue / Retake state for this user. */
    public function folderPortalTests(User $user, StudyMaterialFolder $folder): array
    {
        if (! StudyMaterialFolder::ensureFolderAssessmentsTable()) {
            return [];
        }
        $folder->loadMissing(['assessments.course', 'assessments.questions']);
        $rows = [];
        foreach ($folder->assessments as $assessment) {
            $used = $this->folderSelfAttemptsUsed($user, $assessment);
            $max = $this->maxAttemptsFor($assessment);
            $open = LmsAssessmentAttempt::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $user->id)
                ->where('status', 'in_progress')
                ->orderByDesc('id')
                ->first();
            $latestQuery = LmsAssessmentAttempt::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $user->id);
            if (Schema::hasColumn('lms_assessment_attempts', 'assignment_id')) {
                $latestQuery->where(function ($q) {
                    $q->whereNull('assignment_id')->orWhere('assignment_id', 0);
                });
            }
            $latest = $latestQuery->orderByDesc('id')->first();
            $canStart = $assessment->isPublished() && ! $open && $used < $max;
            $rows[] = [
                'assessment' => $assessment,
                'attempt' => $open ?: $latest,
                'open' => $open,
                'used' => $used,
                'max' => $max,
                'can_start' => $canStart,
                'can_continue' => (bool) $open,
            ];
        }

        return $rows;
    }

    protected function appendFolderSelfTests(User $user, Collection $out, ?string $kind): void
    {
        if (! StudyMaterialFolder::ensureFolderAssessmentsTable()) {
            return;
        }
        $folderIds = $this->activeFolderIdsForUser($user);
        if ($folderIds === []) {
            return;
        }

        $tests = LmsAssessment::query()
            ->with(['course:id,title,slug', 'questions'])
            ->whereIn('status', ['published', 'disabled'])
            ->whereHas('studyFolders', fn ($q) => $q->whereIn('study_material_folders.id', $folderIds))
            ->when($kind, fn ($q) => $q->where('kind', $kind))
            ->orderByDesc('id')
            ->get();

        $have = $out->map(fn ($row) => (int) $row['assessment']->id)->all();
        $pivotFolders = DB::table('study_material_folder_assessments')
            ->whereIn('folder_id', $folderIds)
            ->get()
            ->groupBy('assessment_id');

        foreach ($tests as $assessment) {
            if (in_array((int) $assessment->id, $have, true)) {
                continue;
            }
            $folderId = (int) ($pivotFolders->get($assessment->id)?->first()->folder_id ?? 0);
            $out->push([
                'assessment' => $assessment,
                'assignment' => null,
                'from_folder' => true,
                'folder_id' => $folderId > 0 ? $folderId : null,
            ]);
            $have[] = (int) $assessment->id;
        }
    }

    /** Whether an attempt belongs to this assignment sitting (window, or created_at if open-ended). */
    public function attemptBelongsToAssignment(LmsAssessmentAttempt $att, ?LmsAssessmentAssignment $asg): bool
    {
        if (! $asg || ! $att->started_at) {
            return false;
        }
        if (Schema::hasColumn('lms_assessment_attempts', 'assignment_id') && $att->assignment_id) {
            return (int) $att->assignment_id === (int) $asg->id;
        }
        $started = $att->started_at->copy();
        $start = $asg->windowStart() ?: $asg->available_from;
        if ($start) {
            $end = $asg->appointmentEnd();
            // Must start on/after this sitting — a later Assign must not inherit an older attempt.
            if ($started->lt($start->copy()->subMinutes(2))) {
                return false;
            }
            if ($end && $started->gt($end->copy()->addMinutes(2))) {
                return false;
            }

            return true;
        }
        if ($asg->created_at) {
            return $started->gte($asg->created_at->copy()->subMinutes(2));
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
            ->filter(function (LmsAssessmentAssignment $asg) use ($attempts, $assessment) {
                if (! $asg->isAvailableNow()) {
                    return false;
                }
                $usedOnSitting = $attempts->filter(fn ($att) => $this->attemptBelongsToAssignment($att, $asg))->count();
                if ($asg->isSelfSitting()) {
                    return $usedOnSitting < $this->maxAttemptsFor($assessment);
                }

                return $usedOnSitting === 0;
            })
            ->values();
    }

    public function userCanAccessAssessment(User $user, LmsAssessment $assessment): bool
    {
        return $this->availableAssessmentsForUser($user, $assessment->kind)
            ->contains(fn ($row) => (int) $row['assessment']->id === (int) $assessment->id);
    }

    public function startAttempt(User $user, LmsAssessment $assessment, ?int $assignmentId = null, ?int $folderId = null): LmsAssessmentAttempt
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
                try {
                    $this->submitAttempt($open, true);
                } catch (\Throwable $e) {
                    Log::warning('LMS timeout submit skipped: ' . $e->getMessage());
                }
            } else {
                return $open;
            }
        }

        $max = $this->maxAttemptsFor($assessment);
        $fromFolder = $folderId
            ? $this->userHasFolderSelfTest($user, $assessment, $folderId)
            : $accessRows->contains(fn ($row) => ! empty($row['from_folder']));
        if ($folderId && ! $fromFolder) {
            abort(403, 'This test is not part of that folder.');
        }

        $used = $fromFolder
            ? $this->folderSelfAttemptsUsed($user, $assessment)
            : $this->attemptsUsed($user, $assessment);
        $openSittings = $fromFolder
            ? collect()
            : $this->openUnusedAssignmentsFor($user, $assessment)->sortBy('id')->values();
        $assignment = null;
        if ($assignmentId) {
            $assignment = $openSittings->first(fn ($asg) => (int) $asg->id === $assignmentId);
        }
        if (! $assignment && ! $fromFolder) {
            $assignment = $openSittings->first();
        }
        $hasExplicitAssign = $accessRows->contains(fn ($row) => $row['assignment'] !== null);
        if (! $assignment) {
            if ($fromFolder) {
                if ($used >= $max) {
                    abort(403, 'Maximum attempts for this test have been reached.');
                }
            } elseif ($assessment->isSkill() && ! $hasExplicitAssign) {
                if ($used >= $max) {
                    abort(403, 'Maximum attempts for this test have been reached.');
                }
            } else {
                $anyAsg = $accessRows->map(fn ($row) => $row['assignment'])->filter()->first();
                if ($anyAsg && $anyAsg->isNotYetOpen()) {
                    abort(403, 'This exam is not open yet.');
                }
                if ($anyAsg && $anyAsg->isExpired()) {
                    abort(403, 'The availability window for this exam has ended.');
                }
                if ($used >= $max) {
                    abort(403, 'Maximum attempts for this test have been reached.');
                }
                abort(403, 'No open assigned sitting. Ask admin to Assign a retake for this test.');
            }
        }

        $attempt = new LmsAssessmentAttempt();
        $attempt->assessment_id = $assessment->id;
        $attempt->user_id = $user->id;
        $attempt->status = 'in_progress';
        $attempt->started_at = now();
        if ($assignment && Schema::hasColumn('lms_assessment_attempts', 'assignment_id')) {
            $attempt->assignment_id = (int) $assignment->id;
        }
        // Prefer sum of per-question timers; else optional test-level duration.
        $expires = null;
        $assessment->loadMissing('questions');
        $mins = (int) ($assessment->durationMinutes() ?? 0);
        if ($mins > 0) {
            $expires = now()->addMinutes($mins);
        }
        if ($assignment && ! $assignment->isSelfSitting()) {
            $apptEnd = $assignment->appointmentEnd();
            if ($apptEnd) {
                $expires = $expires ? $expires->min($apptEnd) : $apptEnd->copy();
            }
        }
        $attempt->expires_at = $expires;
        $attempt->save();

        return $attempt;
    }

    public function saveAnswers(LmsAssessmentAttempt $attempt, array $answersByQuestionId): void
    {
        abort_unless($attempt->isInProgress(), 403);
        $timedOut = $attempt->isTimedOut();

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

            $json = is_array($answer->answer_json) ? $answer->answer_json : [];
            if ($question->isMcq()) {
                $selected = isset($payload['selected']) ? (string) $payload['selected'] : null;
                $json['selected'] = $selected;
            } elseif ($question->hasSubQuestions() && isset($payload['parts']) && is_array($payload['parts'])) {
                $parts = [];
                foreach ($payload['parts'] as $key => $text) {
                    $parts[(string) $key] = trim((string) $text);
                }
                $json['parts'] = $parts;
            } else {
                $json['text'] = trim((string) ($payload['text'] ?? ''));
            }
            $answer->answer_json = $json;
            $answer->save();
        }

        if ($timedOut) {
            $this->submitAttempt($attempt, true);
        }
    }

    public function autoSubmitTimedOutForUser(User $user): void
    {
        $open = LmsAssessmentAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();
        foreach ($open as $attempt) {
            try {
                $this->submitAttempt($attempt, true);
            } catch (\Throwable $e) {
                Log::warning('LMS auto-submit skipped: ' . $e->getMessage());
            }
        }
    }

    public function submitAttempt(LmsAssessmentAttempt $attempt, bool $expired = false): LmsAssessmentAttempt
    {
        if (! in_array($attempt->status, ['in_progress'], true)) {
            return $attempt;
        }

        if (! $expired && $attempt->expires_at && now()->gte($attempt->expires_at)) {
            $expired = true;
        }

        $assessment = $attempt->assessment()->with('questions')->first();
        $this->autoMarkMcqs($attempt, $assessment);

        // Time-up / submission-window close still counts as submitted (Marking Required), not Missed.
        $attempt->status = 'submitted';
        // Timed-out submits use expires_at so spent duration cannot exceed the allowed window.
        if ($expired && $attempt->expires_at) {
            $attempt->submitted_at = $attempt->expires_at->copy();
        } else {
            $attempt->submitted_at = now();
        }
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
        $fresh = $attempt->fresh(['assessment', 'answers.question', 'user']);
        try {
            $this->notifyTestSubmitted($fresh);
        } catch (\Throwable $e) {
            Log::warning('LMS submit email skipped: ' . $e->getMessage());
        }

        return $fresh;
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
        $this->notifyResultPublished($attempt->fresh(['user', 'assessment.course']));
    }

    public function notifyAssignmentCreated(LmsAssessmentAssignment $assignment): void
    {
        try {
        $assignment->loadMissing(['assessment.course', 'student', 'batch.students', 'marker']);
        $names = $assignment->isSelfSitting()
            ? ['lms-self-test-assigned']
            : ['lms-exam-assigned'];
        $template = $this->lmsEmailTemplate($names);
        if (! $template) {
            return;
        }
        foreach ($this->assignmentRecipients($assignment) as $user) {
            $this->sendLmsTemplate($template, $user, $this->assignmentPlaceholders($assignment, $user));
        }
        } catch (\Throwable $e) {
            Log::warning('LMS assign email skipped: ' . $e->getMessage());
        }
    }

    public function notifyTestSubmitted(LmsAssessmentAttempt $attempt): void
    {
        try {
        $attempt->loadMissing(['user', 'assessment.course']);
        $user = $attempt->user;
        if (! $user || empty($user->email)) {
            return;
        }
        $template = $this->lmsEmailTemplate(['lms-test-submitted']);
        if (! $template) {
            return;
        }
        $this->sendLmsTemplate(
            $template,
            $user,
            $this->assignmentPlaceholders($this->sittingForAttempt($attempt), $user, $attempt)
        );
        } catch (\Throwable $e) {
            Log::warning('LMS submit email skipped: ' . $e->getMessage());
        }
    }

    public function notifyResultPublished(LmsAssessmentAttempt $attempt): void
    {
        try {
        $attempt->loadMissing(['user', 'assessment.course']);
        $user = $attempt->user;
        if (! $user) {
            return;
        }
        $template = $this->lmsEmailTemplate(['lms-test-result-published']);
        if (! $template) {
            return;
        }
        $placeholders = $this->assignmentPlaceholders($this->sittingForAttempt($attempt), $user, $attempt);

        $this->sendLmsTemplate($template, $user, $placeholders);
        } catch (\Throwable $e) {
            Log::warning('LMS result email skipped: ' . $e->getMessage());
        }
    }

    private function sittingForAttempt(LmsAssessmentAttempt $attempt): ?LmsAssessmentAssignment
    {
        return LmsAssessmentAssignment::query()
            ->with(['assessment.course', 'marker'])
            ->where('assessment_id', $attempt->assessment_id)
            ->orderBy('id')
            ->get()
            ->first(fn ($asg) => $this->attemptBelongsToAssignment($attempt, $asg));
    }

    private function lmsEmailTemplate(array $names): ?Email
    {
        try {
            if (! Schema::hasTable('emails')) {
                return null;
            }
            if (class_exists(LmsAssessmentEmailTemplateSeeder::class)) {
                (new LmsAssessmentEmailTemplateSeeder())->run();
            }
            foreach ($names as $name) {
                $found = Email::query()->where('name', $name)->first();
                if ($found) {
                    return $found;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('LMS email template lookup failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @return Collection<int, User>
     */
    private function assignmentRecipients(LmsAssessmentAssignment $assignment): Collection
    {
        $users = collect();
        if ($assignment->student) {
            $users->push($assignment->student);
        } elseif ($assignment->student_id) {
            $found = User::query()->find($assignment->student_id);
            if ($found) {
                $users->push($found);
            }
        }
        if ($assignment->batch && $assignment->batch->relationLoaded('students')) {
            $users = $users->merge($assignment->batch->students);
        } elseif ($assignment->class_batch_id && Schema::hasTable('class_batch_student')) {
            $batch = ClassBatch::query()->with('students')->find($assignment->class_batch_id);
            if ($batch) {
                $users = $users->merge($batch->students);
            }
        }

        return $users->filter(fn ($u) => $u && ! empty($u->email))->unique('id')->values();
    }

    private function assignmentPlaceholders(?LmsAssessmentAssignment $assignment, User $user, ?LmsAssessmentAttempt $attempt = null): array
    {
        if ($assignment) {
            $assignment->loadMissing(['assessment.course', 'marker']);
        }
        $assessment = $assignment?->assessment ?: $attempt?->assessment;
        $testId = $assessment ? $assessment->displayCode() : '—';
        $testName = $assessment?->title ?: '—';
        $apptId = $assignment ? $assignment->displayCode() : '—';
        $apptTime = $assignment ? $assignment->appointmentWindowLabel('d/m/Y H:i') : '—';
        $resultUrl = $attempt ? url(route('user.assessments.result', $attempt->id, false)) : url(route('user.assessments.index', [], false));
        $isSelf = $assignment && $assignment->isSelfSitting();
        $start = $assignment?->windowStart();
        $mins = $assessment ? $assessment->durationMinutes() : null;
        $duration = ($mins === null || $mins === '') ? '—' : (((int) $mins < 60) ? ((int) $mins . ' min') : (intdiv((int) $mins, 60) . 'h' . (((int) $mins % 60) ? (' ' . ((int) $mins % 60) . 'm') : '')));
        $assessor = $assignment?->marker?->name ?: 'Admin / course instructors';

        return [
            '{name}' => $user->name ?? '',
            '{email}' => $user->email ?? '',
            '{test_id}' => $testId,
            '{test_name}' => $testName,
            '{test_type}' => $assessment ? $assessment->kindLabel() : '—',
            '{sitting_kind}' => $isSelf ? 'Self test' : 'Exam',
            '{start_date}' => $isSelf ? 'Start anytime' : ($start ? $assignment->formatInTimezone($start, 'd/m/Y') : '—'),
            '{start_time}' => $isSelf ? 'Start anytime' : ($start ? $assignment->formatInTimezone($start, 'H:i') : '—'),
            '{start_datetime}' => $isSelf ? 'Start anytime' : ($start ? $assignment->formatInTimezone($start, 'd/m/Y H:i') : '—'),
            '{timezone}' => $assignment ? $assignment->timezoneLabel() : '—',
            '{duration}' => $duration,
            '{assessor}' => $assessor,
            '{max_attempts}' => (string) max(1, (int) ($assessment?->max_attempts ?? 1)),
            '{course_name}' => $assessment?->course?->title ?: '—',
            '{appointment_id}' => $apptId,
            '{appointment_time}' => $apptTime,
            '{login_url}' => url('/login'),
            '{portal_url}' => url(route('user.assessments.index', [], false)),
            '{result_url}' => $resultUrl,
            '{started_at}' => ($attempt && $assignment && $attempt->started_at)
                ? $assignment->formatInTimezone($attempt->started_at, 'd/m/Y H:i')
                : ($attempt?->started_at?->format('d/m/Y H:i') ?: '—'),
            '{submitted_at}' => ($attempt && $assignment && $attempt->submitted_at)
                ? $assignment->formatInTimezone($attempt->submitted_at, 'd/m/Y H:i')
                : ($attempt?->submitted_at?->format('d/m/Y H:i') ?: '—'),
        ];
    }

    private function sendLmsTemplate(Email $template, User $user, array $placeholders): void
    {
        try {
            $body = str_replace(array_keys($placeholders), array_values($placeholders), (string) $template->body);
            $subject = str_replace(array_keys($placeholders), array_values($placeholders), (string) $template->subject);
            $cc = ! empty($template->cc) ? array_filter(array_map('trim', explode(',', $template->cc))) : [];
            $bcc = ! empty($template->bcc) ? array_filter(array_map('trim', explode(',', $template->bcc))) : [];
            Mail::to($user->email)->cc($cc)->bcc($bcc)->send(new UserMail($user, $subject, $body));
        } catch (\Throwable $e) {
            Log::warning('LMS email failed: ' . $e->getMessage());
        }
    }

    public function markPartAnswers(LmsAttemptAnswer $answer, array $subs, array $scores, array $feedbacks): void
    {
        $json = is_array($answer->answer_json) ? $answer->answer_json : [];
        $partScores = [];
        $partFb = [];
        $total = 0.0;
        foreach ($subs as $sub) {
            if (! is_array($sub)) {
                continue;
            }
            $sk = (string) ($sub['key'] ?? '');
            if ($sk === '') {
                continue;
            }
            $cap = (float) ($sub['marks'] ?? 0);
            $raw = $scores[$sk] ?? null;
            $score = ($raw === null || $raw === '') ? 0.0 : (float) $raw;
            $score = max(0, min($score, max(0, $cap)));
            $partScores[$sk] = round($score, 2);
            $total += $score;
            $partFb[$sk] = (string) ($feedbacks[$sk] ?? '');
        }
        $json['part_scores'] = $partScores;
        $json['part_feedback'] = $partFb;
        $answer->answer_json = $json;
        $this->markManualAnswer($answer, $total, null);
    }

    public function markManualAnswer(LmsAttemptAnswer $answer, float $score, ?string $feedback = null): void
    {
        $answer->manual_score = max(0, min($score, (float) $answer->max_marks));
        if (func_num_args() >= 3) {
            $answer->feedback = $feedback;
        }
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
     * Exam retake: admin assigned a new unused sitting that is open now.
     * Self test: remaining attempts on this sitting (Max attempts on the test).
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
        if ($assignment && $assignment->isSelfSitting()) {
            return $this->openUnusedAssignmentsFor($user, $assessment)
                ->contains(fn ($asg) => (int) $asg->id === (int) $assignment->id);
        }
        if (! $assignment && $this->userHasFolderSelfTest($user, $assessment)) {
            return $this->folderSelfAttemptsUsed($user, $assessment) < $this->maxAttemptsFor($assessment);
        }

        return $this->openUnusedAssignmentsFor($user, $assessment)->isNotEmpty();
    }

    /** Allowed attempt total for display / gating (defaults to 1 when unset). */
    public function maxAttemptsFor(LmsAssessment $assessment): int
    {
        $max = $assessment->max_attempts;

        return ($max !== null && (int) $max > 0) ? (int) $max : 1;
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
            $ordered = $group
                ->filter(fn ($r) => ! empty($r['assignment']?->id))
                ->sortBy(fn ($r) => (int) $r['assignment']->id)
                ->values();
            $total = max(1, $ordered->count());
            foreach ($ordered as $i => $r) {
                $asgId = (int) $r['assignment']->id;
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
            $max = $this->maxAttemptsFor($assessment);
            $attempts = $attemptsByAssessment->get($aid) ?? collect();
            $used = $attempts->count();
            $fromFolder = ! empty($row['from_folder']);
            $isSelf = ($assignment && $assignment->isSelfSitting()) || $fromFolder;

            if ($isSelf) {
                $sittingAttempts = $attempts
                    ->filter(function ($att) use ($assignment, $fromFolder, $claimedAttemptIds) {
                        if (isset($claimedAttemptIds[(int) $att->id])) {
                            return false;
                        }
                        if ($fromFolder) {
                            return empty($att->assignment_id);
                        }

                        return $this->attemptBelongsToAssignment($att, $assignment);
                    })
                    ->sortBy('id')
                    ->values();
                $usedOnSitting = $sittingAttempts->count();
                $attemptTotal = $max;
                $matched = $sittingAttempts->sortByDesc('id')->first();
                $attemptNumber = $matched ? $usedOnSitting : max(1, $usedOnSitting + 1);
                foreach ($sittingAttempts as $sa) {
                    $claimedAttemptIds[(int) $sa->id] = true;
                }
                $isRetakeSitting = $usedOnSitting > 0 && $usedOnSitting < $max;
            } else {
                $attemptNumber = (int) $meta['number'];
                $attemptTotal = max($max, (int) $meta['total']);
                $isRetakeSitting = $attemptNumber > 1 && $max > 1;
                $matched = $attempts->first(function ($att) use ($assignment, $claimedAttemptIds) {
                    return ! isset($claimedAttemptIds[(int) $att->id])
                        && $this->attemptBelongsToAssignment($att, $assignment);
                });
                if ($matched) {
                    $claimedAttemptIds[(int) $matched->id] = true;
                }
            }

            $base = [
                'assessment' => $assessment,
                'assignment' => $assignment,
                'attempts_used' => $used,
                'max_attempts' => $max,
                'attempt_number' => $attemptNumber,
                'attempt_total' => $attemptTotal,
                'from_folder' => $fromFolder,
                'folder_id' => $row['folder_id'] ?? null,
            ];

            if ($matched && $matched->status === 'in_progress') {
                if ($matched->isTimedOut()) {
                    try {
                        $this->submitAttempt($matched, true);
                    } catch (\Throwable $e) {
                        Log::warning('LMS timeout submit skipped: ' . $e->getMessage());
                    }
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

            // Disabled tests: keep taken/missed history, but never offer a new start.
            if (! $assessment->isPublished()) {
                if ($assignment && $assignment->isExpired()) {
                    $missed->push($base + [
                        'attempt' => null,
                        'can_retake' => false,
                    ]);
                }
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
                // Each unused assigned sitting is its own start, even if Max attempts is 1.
                $available->push($base + [
                    'attempt' => null,
                    'can_retake' => $isRetakeSitting,
                ]);
            }
        }

        // Result/Submitted: Retake only when Max attempts allows and an open unused sitting exists.
        $results = $results->map(function ($row) use ($user) {
            $row['can_retake'] = $this->canRetake($user, $row['assessment'], $row['assignment'] ?? null);

            return $row;
        });
        $submitted = $submitted->map(function ($row) use ($user) {
            $row['can_retake'] = $this->canRetake($user, $row['assessment'], $row['assignment'] ?? null);

            return $row;
        });

        return compact('available', 'upcoming', 'inProgress', 'submitted', 'results', 'missed');
    }
}
