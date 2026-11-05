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
    /** @var array<int, \Illuminate\Support\Collection> */
    protected array $scsMock1AssignmentsCache = [];

    /** @var array<string, ?LmsAssessmentAssignment> */
    protected array $canonicalSittingCache = [];

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
        $this->restorePublishedAssignedTests();
        $this->backfillAssignmentCriteria();

        return $ok;
    }

    /** Deleted tests stay deleted. Do not put them back on My Tests. */
    private function restorePublishedAssignedTests(): void
    {
        return;
    }

    /**
     * Old sittings must match today's assign rules: sitting_kind set, later unused
     * batch copies keep the original exam window. Not Mock-1-only.
     */
    private function backfillAssignmentCriteria(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            if (! Schema::hasTable('lms_assessment_assignments')) {
                return;
            }
            if (Schema::hasColumn('lms_assessment_assignments', 'sitting_kind')) {
                DB::table('lms_assessment_assignments')
                    ->whereNull('sitting_kind')
                    ->update(['sitting_kind' => LmsAssessmentAssignment::KIND_EXAM]);
            }
            $this->backfillScsMock1ExamAppointment();
            $this->backfillMcsMock1ExamAppointment();
            $rows = LmsAssessmentAssignment::query()
                ->whereNotNull('class_batch_id')
                ->where('class_batch_id', '>', 0)
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($a) => (int) $a->assessment_id . ':' . (int) $a->class_batch_id);
            $attempted = [];
            if (Schema::hasTable('lms_assessment_attempts')) {
                $attempted = LmsAssessmentAttempt::query()
                    ->whereNotNull('assignment_id')
                    ->select('assignment_id')
                    ->distinct()
                    ->pluck('assignment_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                $attempted = array_fill_keys($attempted, true);
            }
            foreach ($rows as $group) {
                $exam = $group->filter(fn ($a) => ! (method_exists($a, 'isSelfSitting') && $a->isSelfSitting()))
                    ->sortBy(fn ($a) => (int) $a->id)
                    ->values();
                if ($exam->count() < 2) {
                    continue;
                }
                $first = $exam->first();
                if (! $first->available_from) {
                    continue;
                }
                foreach ($exam->slice(1) as $later) {
                    if (isset($attempted[(int) $later->id])) {
                        continue;
                    }
                    // Keep an edited unused sitting (reschedule) instead of copying the first window back.
                    if ($later->available_from && (string) $later->available_from !== (string) $first->available_from) {
                        continue;
                    }
                    $later->available_from = $first->available_from;
                    $later->available_until = $first->available_until;
                    if (Schema::hasColumn($later->getTable(), 'timezone') && filled($first->timezone)) {
                        $later->timezone = $first->timezone;
                    }
                    $later->save();
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Fill empty SCS Mock 1 exam windows only. Never overwrite a sitting an admin already saved. */
    private function backfillScsMock1ExamAppointment(): void
    {
        try {
            $aids = LmsAssessment::withTrashed()->get(['id', 'title'])
                ->filter(fn ($row) => $this->isCimaScsMock1($row))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();
            if ($aids->isEmpty()) {
                return;
            }
            $tz = 'Asia/Dubai';
            $from = \Carbon\Carbon::parse('2026-10-30 20:15:00', $tz)->timezone(config('app.timezone', $tz));
            $until = \Carbon\Carbon::parse('2026-10-30 20:15:00', $tz)->addMinutes(180)->timezone(config('app.timezone', $tz));
            $rows = LmsAssessmentAssignment::query()->whereIn('assessment_id', $aids->all())->get();
            foreach ($rows as $row) {
                if (method_exists($row, 'isSelfSitting') && $row->isSelfSitting()) {
                    continue;
                }
                if (method_exists($row, 'windowStart') && $row->windowStart()) {
                    continue;
                }
                $row->available_from = $from;
                $row->available_until = $until;
                if (Schema::hasColumn($row->getTable(), 'timezone')) {
                    $row->timezone = $tz;
                }
                $row->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** MCS Mock 1 class sitting is Test on 26/10/2026, 20:30. Named Self Tests stay Self Test. */
    private function backfillMcsMock1ExamAppointment(): void
    {
        try {
            $aids = LmsAssessment::withTrashed()->get(['id', 'title'])
                ->filter(fn ($row) => $this->isCimaMcsMock1($row))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();
            if ($aids->isEmpty()) {
                return;
            }
            $tz = 'Asia/Dubai';
            $from = \Carbon\Carbon::parse('2026-10-26 20:30:00', $tz)->timezone(config('app.timezone', $tz));
            $until = \Carbon\Carbon::parse('2026-10-26 20:30:00', $tz)->addMinutes(180)->timezone(config('app.timezone', $tz));
            $rows = LmsAssessmentAssignment::query()->whereIn('assessment_id', $aids->all())->get();
            foreach ($rows as $row) {
                $dirty = false;
                if (Schema::hasColumn($row->getTable(), 'sitting_kind')
                    && $row->sitting_kind !== LmsAssessmentAssignment::KIND_EXAM) {
                    $row->sitting_kind = LmsAssessmentAssignment::KIND_EXAM;
                    $dirty = true;
                }
                if (! (method_exists($row, 'windowStart') && $row->windowStart())) {
                    $row->available_from = $from;
                    $row->available_until = $until;
                    if (Schema::hasColumn($row->getTable(), 'timezone')) {
                        $row->timezone = $tz;
                    }
                    $dirty = true;
                }
                if ($dirty) {
                    $row->save();
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Same sitting on Admin, instructor, and student: MCS Mock 1 class sitting is Test + 26/10 20:30. */
    public function alignSittingWithExistingExamRule($asg, $assessment = null): void
    {
        if (! $asg) {
            return;
        }
        $assessment = $assessment ?: ($asg->assessment ?? null);
        if (! $assessment) {
            return;
        }
        if ($this->isCimaMcsMock1($assessment)) {
            if (Schema::hasColumn($asg->getTable(), 'sitting_kind')) {
                $asg->sitting_kind = LmsAssessmentAssignment::KIND_EXAM;
            }
            if (method_exists($asg, 'applyMissingExamWindow')) {
                $asg->applyMissingExamWindow('2026-10-26 20:30:00', 'Asia/Dubai', 180);
            }
            if ($asg->exists) {
                try {
                    $asg->save();
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return;
        }
        if ($asg->isSelfSitting() && ! empty($asg->student_id) && empty($asg->class_batch_id)) {
            return;
        }
        if ($this->isCimaScsMock1($assessment) && ! $asg->isSelfSitting() && method_exists($asg, 'applyMissingExamWindow')) {
            $asg->applyMissingExamWindow('2026-10-30 20:15:00', 'Asia/Dubai', 180);
        }
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

    public function actorIsAssignedMarker(): bool
    {
        $uid = (int) (Auth::id() ?? 0);
        if ($uid <= 0 || $this->lms->isAdminActor()) {
            return false;
        }
        if (! Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')) {
            return false;
        }

        return LmsAssessmentAssignment::query()->where('marker_user_id', $uid)->exists();
    }

    public function actorCanViewAssignedTests(): bool
    {
        return $this->canAssignStudentAssessments()
            || $this->canManageSkillTests()
            || $this->canManageStudentAssessments()
            || $this->actorIsAssignedMarker();
    }

    /** Admin, HoF, or anyone who can open Assigned Tests may remove a sitting. */
    public function actorCanRemoveSitting(): bool
    {
        if ($this->lms->isAdminActor() || (function_exists('panel_sees_all_dashboards') && panel_sees_all_dashboards())) {
            return true;
        }

        return $this->lms->isInstructorActor() && $this->actorCanViewAssignedTests();
    }

    public function actorCanAccessAttempt(LmsAssessmentAttempt $attempt): bool
    {
        if ($this->lms->isAdminActor()) {
            return true;
        }
        $attempt->loadMissing('assessment');
        if ($attempt->assessment?->isSkill()) {
            return $this->canManageSkillTests();
        }
        $uid = (int) (Auth::id() ?? 0);
        if ($uid <= 0) {
            return false;
        }
        if ((int) ($attempt->assessment->created_by_user_id ?? 0) === $uid) {
            return true;
        }
        if (Schema::hasTable('lms_assessment_assignments')) {
            $forThisTest = LmsAssessmentAssignment::query()
                ->where('assessment_id', (int) $attempt->assessment_id);
            if (Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')
                && (clone $forThisTest)->where('marker_user_id', $uid)->exists()) {
                return true;
            }
            if (Schema::hasColumn('lms_assessment_assignments', 'assigned_by_user_id')
                && (clone $forThisTest)->where('assigned_by_user_id', $uid)->exists()) {
                return true;
            }
        }
        if ($this->lms->isInstructorActor()) {
            $courseId = (int) ($attempt->assessment?->course_id ?? 0);

            return $courseId <= 0 || $this->lms->instructorAssignedToCourse($courseId);
        }

        return false;
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

        $attemptAsgIds = [];
        try {
            $attemptAsgIds = LmsAssessmentAttempt::query()
                ->where('user_id', $userId)
                ->whereNotNull('assignment_id')
                ->pluck('assignment_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            $attemptAsgIds = [];
        }

        $batchIds = [];
        if (Schema::hasTable('class_batch_student')) {
            $studentCol = Schema::hasColumn('class_batch_student', 'student_id') ? 'student_id' : 'user_id';
            $batchIds = DB::table('class_batch_student')
                ->where($studentCol, $userId)
                ->pluck('class_batch_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }
        $batchIds = array_values(array_unique(array_filter($batchIds)));

        $assignQuery = LmsAssessmentAssignment::query()
            ->with('marker')
            ->where(function ($q) use ($userId, $batchIds) {
                $q->where('student_id', $userId);
                if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                    $q->orWhereJsonContains('roster_user_ids', $userId)
                        ->orWhereJsonContains('roster_user_ids', (string) $userId);
                }
                if ($batchIds !== []) {
                    $q->orWhereIn('class_batch_id', $batchIds);
                }
            })
            ->orderByDesc('id');
        $direct = $assignQuery->get();
        $direct = $this->mergeRosterSittingsForUser($direct, $userId);
        $assessmentIds = $direct->pluck('assessment_id')->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
        $assessmentsById = $assessmentIds === []
            ? collect()
            : LmsAssessment::query()->with('course')->whereIn('id', $assessmentIds)->get()->keyBy(fn ($row) => (int) $row->id);

        $out = collect();
        foreach ($direct as $assignment) {
            if (! $this->assignmentIncludesStudent($assignment, $userId)) {
                continue;
            }
            $assessment = $assessmentsById->get((int) $assignment->assessment_id);
            if (! $assessment || ! $this->studentShouldSeeSitting($user, $assessment, $assignment, $attemptAsgIds)) {
                continue;
            }
            if ($kind && $assessment->kind !== $kind) {
                continue;
            }
            if ($assessment->isSkill() || $assessment->audience === LmsAssessment::AUDIENCE_STAFF) {
                if (! $isStaff) {
                    continue;
                }
            }
            if (! $assessment->isPublished()) {
                $hasAttempt = LmsAssessmentAttempt::query()
                    ->where('assessment_id', $assessment->id)
                    ->where('user_id', $userId)
                    ->exists();
                if (! $hasAttempt) {
                    continue;
                }
            }
            $assignment->setRelation('assessment', $assessment);
            $out->push(['assessment' => $assessment, 'assignment' => $assignment]);
        }

        if ($isStaff) {
            $skillQuery = LmsAssessment::query()
                ->with('course')
                ->where(function ($q) {
                    $q->where('kind', LmsAssessment::KIND_SKILL)
                        ->orWhere('audience', LmsAssessment::AUDIENCE_STAFF);
                })
                ->where('status', 'published');
            if ($kind) {
                $skillQuery->where('kind', $kind);
            }
            foreach ($skillQuery->orderByDesc('id')->get() as $assessment) {
                $already = $out->contains(fn ($row) => (int) $row['assessment']->id === (int) $assessment->id);
                if ($already) {
                    continue;
                }
                if ($assessment->assignments()->exists()) {
                    continue;
                }
                $out->push(['assessment' => $assessment, 'assignment' => null]);
            }
        }

        $this->attachCimaCohortSittingsForUser($user, $out, $kind);
        $this->attachScsMockSittingsForUser($user, $out, $kind);

        if (method_exists(StudyMaterialFolder::class, 'ensureFolderAssessmentsTable')) {
            $this->appendFolderSelfTests($user, $out, $kind);
        }

        return $this->collapseStudentScsSittings($user, $out, $attemptAsgIds);
    }

    /** Same list rule as Admin: unique sitting, and one unused class exam per test. */
    protected function collapseStudentScsSittings(User $user, Collection $out, array $attemptAsgIds = []): Collection
    {
        $uid = (int) ($user->id ?? 0);
        $rows = $out->filter(fn ($row) => is_array($row) && ! empty($row['assessment']))
            ->unique(function ($row) {
                return (int) data_get($row, 'assessment.id') . ':' . (int) data_get($row, 'assignment.id');
            })
            ->values();
        $unused = [];
        foreach ($rows as $i => $row) {
            $asg = $row['assignment'] ?? null;
            if (! $asg || (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting())) {
                continue;
            }
            $named = (int) ($asg->student_id ?? 0) === $uid && empty($asg->class_batch_id);
            if ($named) {
                continue;
            }
            $aid = (int) data_get($row, 'assessment.id');
            $unused[$aid][$i] = (int) $asg->id;
        }
        $drop = [];
        foreach ($unused as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }
            asort($indexes);
            $keep = array_key_first($indexes);
            foreach ($indexes as $i => $id) {
                if ($i !== $keep) {
                    $drop[$i] = true;
                }
            }
        }
        if ($drop === []) {
            return $rows;
        }

        return $rows->values()->filter(fn ($row, $i) => empty($drop[$i]))->values();
    }

    /** Saved assign snapshot, including JSON stored as ints or strings. */
    public function rosterHasUser($asg, int $userId): bool
    {
        if (! $asg || $userId <= 0) {
            return false;
        }
        try {
            if (! Schema::hasColumn($asg->getTable(), 'roster_user_ids')) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return collect($asg->roster_user_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->contains($userId);
    }

    /** JSON_CONTAINS often misses roster ids on MariaDB; load those sittings in PHP. */
    protected function mergeRosterSittingsForUser(Collection $direct, int $userId): Collection
    {
        if ($userId <= 0) {
            return $direct;
        }
        try {
            if (! Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                return $direct;
            }
            $have = $direct->pluck('id')->map(fn ($id) => (int) $id)->filter()->all();
            $more = LmsAssessmentAssignment::query()
                ->with('marker')
                ->whereNotNull('roster_user_ids')
                ->when($have !== [], fn ($q) => $q->whereNotIn('id', $have))
                ->get()
                ->filter(fn ($asg) => $this->rosterHasUser($asg, $userId));
            if ($more->isEmpty()) {
                return $direct;
            }

            return $direct->concat($more)->unique(fn ($row) => (int) ($row->id ?? 0))->values();
        } catch (\Throwable $e) {
            return $direct;
        }
    }

    /** Same people Admin lists on a sitting: named student, roster snapshot, or batch members at assign time. */
    public function assignmentIncludesStudent($asg, int $userId): bool
    {
        if (! $asg || $userId <= 0) {
            return false;
        }
        try {
            if (Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
                $excluded = collect($asg->excluded_user_ids ?? [])->map(fn ($id) => (int) $id);
                if ($excluded->contains($userId)) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            // fall through to named/roster/batch
        }
        if ((int) ($asg->student_id ?? 0) === $userId) {
            return true;
        }
        if ($this->rosterHasUser($asg, $userId)) {
            return true;
        }
        if (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) {
            return false;
        }
        try {
            return ClassBatch::studentUserIdsForSitting($asg)->contains($userId);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Remove one person's sitting/attempt so it leaves Admin, instructor Assigned Tests, and student My Tests.
     * Batch sittings stay for classmates; that student is excluded from the roster.
     */
    public function forgetStudentSitting(?LmsAssessmentAssignment $assignment, ?LmsAssessmentAttempt $attempt, int $studentId = 0): string
    {
        if ($studentId <= 0) {
            $studentId = (int) ($attempt->user_id ?? $assignment->student_id ?? 0);
        }
        $assessmentId = (int) ($assignment->assessment_id ?? $attempt->assessment_id ?? 0);
        $assignmentId = (int) ($assignment->id ?? $attempt->assignment_id ?? 0);
        $code = '—';
        if ($assignment && $studentId > 0 && method_exists($assignment, 'displayCodeForStudent')) {
            $code = $assignment->displayCodeForStudent($studentId);
        } elseif ($assignment) {
            $code = $assignment->displayCode();
        }

        $deleteAttempt = function (LmsAssessmentAttempt $att): void {
            try {
                $att->answers()->delete();
            } catch (\Throwable $e) {
                // attempt row still goes
            }
            $att->delete();
        };

        if ($attempt && $attempt->exists) {
            $deleteAttempt($attempt);
        }

        if ($assessmentId > 0 && $studentId > 0) {
            $rows = LmsAssessmentAttempt::query()
                ->where('assessment_id', $assessmentId)
                ->where('user_id', $studentId)
                ->get();
            $kind = ($assignment && method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting())
                ? 'self'
                : 'exam';
            foreach ($rows as $att) {
                $attAsg = (int) ($att->assignment_id ?? 0);
                $belongs = $assignmentId > 0 && $attAsg === $assignmentId;
                if (! $belongs && $assignment && $this->attemptBelongsToAssignment($att, $assignment)) {
                    $belongs = true;
                }
                if (! $belongs && $assignmentId <= 0) {
                    $belongs = true;
                }
                if ($belongs) {
                    $deleteAttempt($att);
                }
            }
            if ($assignment && $assignment->exists) {
                $this->excludeStudentFromAssignment($assignment, $studentId);
                $named = ! empty($assignment->student_id)
                    && (int) $assignment->student_id === $studentId
                    && empty($assignment->class_batch_id);
                if ($named || $this->sittingHasNoPeople($assignment)) {
                    $assignment->delete();
                }
            }
            $siblings = LmsAssessmentAssignment::query()
                ->where('assessment_id', $assessmentId)
                ->get();
            foreach ($siblings as $row) {
                if ($assignment && (int) $row->id === (int) $assignment->id) {
                    continue;
                }
                if (Schema::hasColumn($row->getTable(), 'sitting_kind')) {
                    $rowKind = method_exists($row, 'isSelfSitting') && $row->isSelfSitting() ? 'self' : 'exam';
                    if ($rowKind !== $kind) {
                        continue;
                    }
                }
                $named = ! empty($row->student_id)
                    && (int) $row->student_id === $studentId
                    && empty($row->class_batch_id);
                if ($named) {
                    $row->delete();
                }
            }
        }

        return $code;
    }

    public function sittingHasNoPeople($asg): bool
    {
        if (! $asg) {
            return true;
        }
        if ((int) ($asg->student_id ?? 0) > 0) {
            return false;
        }
        $roster = collect($asg->roster_user_ids ?? [])->map(fn ($id) => (int) $id)->filter();
        if ($roster->isNotEmpty()) {
            return false;
        }
        $rawRoster = $asg->getAttributes()['roster_user_ids'] ?? null;
        $rosterSaved = $rawRoster !== null && $rawRoster !== '';
        $batchId = (int) ($asg->class_batch_id ?? 0);
        if (! $rosterSaved && $batchId > 0 && ! (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting())) {
            try {
                if (ClassBatch::studentUserIdsFor($batchId)->isNotEmpty()) {
                    return false;
                }
            } catch (\Throwable $e) {
                return false;
            }
        }
        try {
            if (! empty($asg->id) && Schema::hasTable('lms_assessment_attempts')) {
                if (LmsAssessmentAttempt::query()->where('assignment_id', (int) $asg->id)->exists()) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            // treat as empty
        }

        return true;
    }

    public function excludeStudentFromAssignment(LmsAssessmentAssignment $row, int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        try {
            if (Schema::hasColumn($row->getTable(), 'excluded_user_ids')) {
                $ex = collect($row->excluded_user_ids ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->push($userId)
                    ->unique()
                    ->filter()
                    ->values()
                    ->all();
                $row->excluded_user_ids = $ex;
            }
            if (Schema::hasColumn($row->getTable(), 'roster_user_ids')) {
                $roster = collect($row->roster_user_ids ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->reject(fn ($id) => $id === $userId)
                    ->values()
                    ->all();
                $row->roster_user_ids = $roster;
            }
            $row->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function scsAssignedMockKey($assessment): ?string
    {
        if (! $this->isCimaScsAssignedMock($assessment)) {
            return null;
        }
        $title = mb_strtolower(trim((string) ($assessment->title ?? '')));
        if (! preg_match('/mock\s*(\d+)/', $title, $m)) {
            return null;
        }

        return 'scs:mock' . $m[1];
    }

    /**
     * One sitting per student per SCS Mock 1/2 — Admin, instructor, and My Tests all use this.
     * Named person assignment wins; if several exist, the one with an attempt, else the original (lowest id).
     * If they were only on a class sitting, that class assignment is used.
     */
    public function canonicalSittingForStudent($student, $assessment, array $attemptAsgIds = []): ?LmsAssessmentAssignment
    {
        $uid = (int) ($student->id ?? 0);
        $want = $this->scsAssignedMockKey($assessment);
        if ($uid <= 0 || ! $want) {
            return null;
        }
        $cacheKey = $uid . ':' . $want;
        if (array_key_exists($cacheKey, $this->canonicalSittingCache ?? [])) {
            return $this->canonicalSittingCache[$cacheKey];
        }
        $picked = null;
        try {
            $aids = LmsAssessment::query()->get(['id', 'title'])
                ->filter(fn ($row) => $this->scsAssignedMockKey($row) === $want)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();
            if ($aids !== []) {
                $rows = LmsAssessmentAssignment::query()
                    ->with('marker')
                    ->whereIn('assessment_id', $aids)
                    ->orderBy('id')
                    ->get()
                    ->filter(fn ($asg) => ! (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()))
                    ->values();
                $named = $rows->filter(fn ($asg) => (int) ($asg->student_id ?? 0) === $uid);
                $pool = $named->isNotEmpty() ? $named : $rows->filter(function ($asg) use ($uid) {
                    if ((int) ($asg->student_id ?? 0) === $uid) {
                        return true;
                    }
                    if (! Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                        return false;
                    }

                    return collect($asg->roster_user_ids ?? [])->map(fn ($id) => (int) $id)->contains($uid);
                });
                if ($pool->isNotEmpty()) {
                    if ($attemptAsgIds === []) {
                        try {
                            $attemptAsgIds = LmsAssessmentAttempt::query()
                                ->where('user_id', $uid)
                                ->whereIn('assignment_id', $pool->pluck('id')->all())
                                ->pluck('assignment_id')
                                ->map(fn ($id) => (int) $id)
                                ->all();
                        } catch (\Throwable $e) {
                            $attemptAsgIds = [];
                        }
                    }
                    $withAttempt = $pool->filter(fn ($asg) => in_array((int) ($asg->id ?? 0), $attemptAsgIds, true));
                    $picked = ($withAttempt->isNotEmpty() ? $withAttempt : $pool)->sortBy(fn ($asg) => (int) ($asg->id ?? 0))->first();
                }
            }
        } catch (\Throwable $e) {
            $picked = null;
        }
        $this->canonicalSittingCache[$cacheKey] = $picked;

        return $picked;
    }

    public function adminMatchingPersonSitting($user, $assessment, array $attemptAsgIds = []): ?LmsAssessmentAssignment
    {
        return $this->canonicalSittingForStudent($user, $assessment, $attemptAsgIds);
    }

    /**
     * Assessor for this sitting — same on Admin, instructor, and My Tests.
     * This sitting's marker, else a marker saved on the same Mock 1/2 test, else BAT-0004 trainer.
     */
    public function canonicalAssessorForSitting($asg, $assessment = null, $attempt = null): ?User
    {
        $assessment = $assessment ?: (($asg && method_exists($asg, 'assessment')) ? ($asg->assessment ?? null) : null);
        if ($attempt && ! empty($attempt->graded_by_user_id)) {
            $grader = User::query()->find((int) $attempt->graded_by_user_id);
            if ($grader && trim((string) ($grader->name ?? '')) !== '' && trim((string) $grader->name) !== '—') {
                return $grader;
            }
        }
        if ($asg instanceof LmsAssessmentAssignment) {
            try {
                $asg->loadMissing('marker');
            } catch (\Throwable $e) {
                // keep going
            }
            $marker = $asg->marker ?? null;
            if ($marker && trim((string) ($marker->name ?? '')) !== '' && trim((string) $marker->name) !== '—') {
                return $marker;
            }
            if (! empty($asg->marker_user_id)) {
                $loaded = User::query()->find((int) $asg->marker_user_id);
                if ($loaded && trim((string) ($loaded->name ?? '')) !== '') {
                    return $loaded;
                }
            }
        }
        $aids = [];
        $want = $this->scsAssignedMockKey($assessment);
        try {
            if ($want) {
                $aids = LmsAssessment::query()->get(['id', 'title'])
                    ->filter(fn ($row) => $this->scsAssignedMockKey($row) === $want)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->values()
                    ->all();
            } elseif ($assessment && ! empty($assessment->id)) {
                $aids = [(int) $assessment->id];
            }
            if ($aids !== []) {
                $row = LmsAssessmentAssignment::query()
                    ->with('marker')
                    ->whereIn('assessment_id', $aids)
                    ->whereNotNull('marker_user_id')
                    ->where('marker_user_id', '>', 0)
                    ->orderByDesc('id')
                    ->first();
                if ($row) {
                    $named = $row->marker ?: User::query()->find((int) $row->marker_user_id);
                    if ($named && trim((string) ($named->name ?? '')) !== '' && trim((string) $named->name) !== '—') {
                        return $named;
                    }
                }
            }
        } catch (\Throwable $e) {
            // fall through to class trainer
        }
        if (! $this->isCimaScsAssignedMock($assessment)) {
            return null;
        }
        try {
            $batch = ClassBatch::query()->where('code', 'BAT-0004')->first()
                ?: ClassBatch::query()->where('code', 'like', '%0004%')->first();
            if (! $batch) {
                return null;
            }
            $batch->loadMissing(['instructors', 'headOfFaculty']);
            $trainer = method_exists($batch, 'trainerInstructors')
                ? $batch->trainerInstructors()->first()
                : $batch->instructors->first();
            if ($trainer && trim((string) ($trainer->name ?? '')) !== '') {
                return $trainer;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    /** Class Mock 1 window (30/10) — not Yahia's own Nov 4/5 sitting. */
    public function usesMock1ClassWindow($asg, $assessment, $student = null): bool
    {
        if (! $this->isCimaScsMock1($assessment)) {
            return false;
        }
        if ($asg && method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) {
            return false;
        }

        return ! $this->isMock1YahiaNovemberSitting($asg, $assessment, $student);
    }

    /** This sitting's own start. Self tests have no appointment. Never copy another sitting's time. */
    public function scsMock1ExamWindowStart($asg, $assessment = null, $student = null): ?\Carbon\Carbon
    {
        if (! $asg || (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting())) {
            return null;
        }

        return method_exists($asg, 'windowStart') ? $asg->windowStart() : null;
    }

    public function sittingAppointmentLabel($asg, $assessment = null, $student = null): string
    {
        if (! $asg) {
            return '—';
        }
        $this->alignSittingWithExistingExamRule($asg, $assessment);
        return method_exists($asg, 'appointmentStartLabel')
            ? $asg->appointmentStartLabel()
            : '—';
    }

    /** Upcoming / Missed / Assigned from this sitting's start + 30 minutes. Self test stays Assigned. */
    public function unusedExamBucket($asg, $assessment = null, $student = null): string
    {
        if (! $asg || (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting())) {
            return 'assigned';
        }
        $start = method_exists($asg, 'windowStart') ? $asg->windowStart() : null;
        if ($start && now()->lt($start)) {
            return 'upcoming';
        }
        if ($start && now()->gte($start->copy()->addMinutes(30))) {
            return 'missed';
        }

        return 'assigned';
    }

    /** Sitting this student is on (direct or batch), so Missed/Available can show BEXT. */
    protected function resolveAssignmentForUser(User $user, LmsAssessment $assessment): ?LmsAssessmentAssignment
    {
        $userId = (int) $user->id;
        $aid = (int) $assessment->id;
        if ($userId <= 0 || $aid <= 0) {
            return null;
        }
        try {
            $own = LmsAssessmentAssignment::query()
                ->where('assessment_id', $aid)
                ->where('student_id', $userId)
                ->orderByDesc('id')
                ->first();
            if ($own && $this->assignmentIncludesStudent($own, $userId)) {
                return $own;
            }
            $batchIds = [];
            if (Schema::hasTable('class_batch_student')) {
                $studentCol = Schema::hasColumn('class_batch_student', 'student_id') ? 'student_id' : 'user_id';
                $batchIds = DB::table('class_batch_student')
                    ->where($studentCol, $userId)
                    ->pluck('class_batch_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
            $batchIds = array_values(array_unique(array_filter($batchIds)));
            if ($batchIds !== []) {
                $batchAsg = LmsAssessmentAssignment::query()
                    ->where('assessment_id', $aid)
                    ->whereIn('class_batch_id', $batchIds)
                    ->orderByDesc('id')
                    ->get();
                foreach ($batchAsg as $row) {
                    if ($this->assignmentIncludesStudent($row, $userId)) {
                        return $row;
                    }
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected function cimaCohortEmails(): array
    {
        return [
            'k_vinu83@yahoo.com',
            'kartik.chaturvedi469@gmail.com',
            'jahanps@gmail.com',
            'osamaym@hotmail.com',
            'vananthan1@gmail.com',
        ];
    }

    protected function isCimaCohortEmail(?string $email): bool
    {
        $email = mb_strtolower(trim((string) $email));

        return $email !== '' && in_array($email, $this->cimaCohortEmails(), true);
    }

    /** Student My Tests: hide deleted/disabled tests and SCS mocks nobody assigned to this person. */
    protected function studentShouldSeeSitting(User $user, $assessment, $assignment, array $attemptAsgIds = []): bool
    {
        if (! $assessment) {
            return false;
        }
        if (method_exists($assessment, 'trashed') && $assessment->trashed()) {
            return false;
        }
        $status = mb_strtolower(trim((string) ($assessment->status ?? '')));
        if (in_array($status, ['disabled', 'draft'], true)) {
            return false;
        }
        $userId = (int) $user->id;
        if ($this->studentIsHiddenOnMcs($user, $assessment)) {
            return false;
        }
        if ($assignment && Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
            $excluded = collect($assignment->excluded_user_ids ?? [])->map(fn ($id) => (int) $id);
            if ($excluded->contains($userId)) {
                return false;
            }
        }
        $title = mb_strtolower(trim((string) ($assessment->title ?? '')));
        $isScs = str_contains($title, 'scs')
            && str_contains($title, 'mock')
            && ! str_contains($title, 'masterclass')
            && ! str_contains($title, 'mcs');
        if ($isScs && ! $this->isCimaScsAssignedMock($assessment)) {
            $named = $assignment && (int) ($assignment->student_id ?? 0) === $userId;
            $onRoster = false;
            if ($assignment && Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                $onRoster = collect($assignment->roster_user_ids ?? [])->map(fn ($id) => (int) $id)->contains($userId);
            }
            $onAttempt = $assignment && in_array((int) ($assignment->id ?? 0), $attemptAsgIds, true);
            if (! $named && ! $onRoster && ! $onAttempt) {
                return false;
            }
        }

        return true;
    }

    /** Imthiyas is hidden on CIMA MCS only — every student My Tests tab. */
    protected function studentIsHiddenOnMcs($user, $assessment): bool
    {
        if (! $user || ! $assessment) {
            return false;
        }
        $blob = mb_strtolower(trim(implode(' ', array_filter([
            (string) ($user->name ?? ''),
            (string) ($user->email ?? ''),
            (string) ($user->first_name ?? ''),
            (string) ($user->last_name ?? ''),
        ]))));
        if ($blob === '' || (! str_contains($blob, 'imthiyas') && ! str_contains($blob, 'imthiya'))) {
            return false;
        }
        $hay = mb_strtolower(trim((string) ($assessment->title ?? '')));
        try {
            $courseTitle = (string) ($assessment->course->title ?? '');
            if ($courseTitle !== '') {
                $hay .= ' ' . mb_strtolower($courseTitle);
            }
        } catch (\Throwable $e) {
            // title is enough
        }

        return str_contains($hay, 'cima mcs')
            || str_contains($hay, 'management case')
            || (bool) preg_match('/\bmcs\b/', $hay);
    }

    public function studentExcludedFromAssessment(int $userId, int $assessmentId): bool
    {
        if ($userId <= 0 || $assessmentId <= 0) {
            return false;
        }
        try {
            if (! Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
                return false;
            }
            $rows = LmsAssessmentAssignment::query()
                ->where('assessment_id', $assessmentId)
                ->get(['excluded_user_ids']);
            foreach ($rows as $row) {
                $ex = collect($row->excluded_user_ids ?? [])->map(fn ($id) => (int) $id);
                if ($ex->contains($userId)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    public function isCimaScsAssignedMock($assessment): bool
    {
        if (! $assessment) {
            return false;
        }
        if ($this->isCimaScsMock1($assessment)) {
            return true;
        }
        $title = mb_strtolower(trim((string) ($assessment->title ?? '')));
        if ($title === '' || str_contains($title, 'masterclass') || str_contains($title, 'mcs')) {
            return false;
        }
        if (! str_contains($title, 'scs')) {
            return false;
        }

        return (bool) preg_match('/\bmock\s*2\b/', $title);
    }

    /** CIMA case sittings expire. Mock/practice/general stay startable after the clock. */
    public function isCimaScsMock1($assessment): bool
    {
        $title = mb_strtolower(trim((string) ($assessment->title ?? '')));
        if ($title === '' || str_contains($title, 'masterclass') || str_contains($title, 'mock 2')) {
            return false;
        }

        return str_contains($title, 'scs') && (str_contains($title, 'mock 1') || (bool) preg_match('/\bmock\s*1\b/', $title));
    }

    /** First real exam sitting for this test (not a later class copy, not a self test). */
    public function originalExamSittingFor($assessment): ?LmsAssessmentAssignment
    {
        $aid = (int) ($assessment->id ?? 0);
        if ($aid <= 0) {
            return null;
        }
        try {
            $rows = LmsAssessmentAssignment::query()->where('assessment_id', $aid)->orderBy('id')->get();
        } catch (\Throwable $e) {
            return null;
        }
        $exam = $rows->filter(fn ($a) => ! (method_exists($a, 'isSelfSitting') && $a->isSelfSitting()))->values();
        $pool = $exam;

        return $pool->sortBy(fn ($a) => (int) ($a->id ?? 0))->first();
    }

    public function isMock1FourthNovemberSitting($asg, $assessment = null): bool
    {
        return $this->mock1NovemberSittingOnDays($asg, $assessment, [4, 5]);
    }

    /** Unused class copies dated 4/5 Nov — not Yahia's own person sitting. */
    public function isMock1CopiedNovemberSitting($asg, $assessment = null): bool
    {
        if (! $asg || ! empty($asg->student_id)) {
            return false;
        }

        return $this->isMock1FourthNovemberSitting($asg, $assessment);
    }

    public function isMock1YahiaNovemberSitting($asg, $assessment = null, $student = null): bool
    {
        if (! $this->isMock1FourthNovemberSitting($asg, $assessment)) {
            return false;
        }
        $sid = (int) ($asg->student_id ?? 0);
        if ($sid <= 0) {
            return false;
        }
        if ($student && $this->isYahiaUser($student)) {
            return true;
        }
        try {
            $user = $student && ! empty($student->id) ? $student : User::query()->find($sid);

            return $this->isYahiaUser($user);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function mock1NovemberSittingOnDays($asg, $assessment, array $days): bool
    {
        if (! $asg) {
            return false;
        }
        $assessment = $assessment ?: ($asg->assessment ?? null);
        if (! $this->isCimaScsMock1($assessment)) {
            return false;
        }
        $start = null;
        try {
            $start = method_exists($asg, 'windowStart') ? $asg->windowStart() : ($asg->available_from ?? null);
        } catch (\Throwable $e) {
            $start = $asg->available_from ?? null;
        }
        if (! $start) {
            return str_contains((string) ($asg->available_from ?? ''), '2026-11-04')
                || str_contains((string) ($asg->available_from ?? ''), '2026-11-05');
        }
        try {
            $day = \Carbon\Carbon::parse($start)->timezone('Asia/Dubai');

            return $day->year === 2026 && $day->month === 11 && in_array((int) $day->day, $days, true);
        } catch (\Throwable $e) {
            $raw = (string) ($asg->available_from ?? '');

            return str_contains($raw, '2026-11-04') || str_contains($raw, '2026-11-05');
        }
    }

    /** Hard-delete unused Mock 1 *batch* copies dated 4/5 Nov. Keep person sittings (Yahia). */
    public function purgeMock1FourthNovemberSittings(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            if (! Schema::hasTable('lms_assessment_assignments')) {
                return;
            }
            $assessments = LmsAssessment::withTrashed()->get(['id', 'title']);
            $aids = $assessments->filter(fn ($row) => $this->isCimaScsMock1($row))->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();
            if ($aids->isEmpty()) {
                return;
            }
            $rows = LmsAssessmentAssignment::query()->whereIn('assessment_id', $aids->all())->get();
            foreach ($rows as $row) {
                $assessment = $assessments->firstWhere('id', (int) $row->assessment_id);
                if (! $this->isMock1CopiedNovemberSitting($row, $assessment)) {
                    continue;
                }
                $used = false;
                try {
                    $used = Schema::hasTable('lms_assessment_attempts')
                        && LmsAssessmentAttempt::query()->where('assignment_id', (int) $row->id)->exists();
                } catch (\Throwable $e) {
                    $used = false;
                }
                if ($used) {
                    continue;
                }
                DB::table('lms_assessment_assignments')->where('id', (int) $row->id)->delete();
            }
            $this->scsMock1AssignmentsCache = [];
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Recreate Yahia's Mock 1 person sittings on 4 Nov and 5 Nov 20:00 Asia/Dubai. */
    public function restoreYahiaMock1NovemberSittings(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            if (! Schema::hasTable('lms_assessment_assignments')) {
                return;
            }
            $yahias = User::query()->where(function ($q) {
                $q->where('name', 'like', '%yahia%')
                    ->orWhere('name', 'like', '%yahya%')
                    ->orWhere('email', 'like', '%yahia%')
                    ->orWhere('email', 'like', '%yahya%');
            })->get();
            if ($yahias->isEmpty()) {
                return;
            }
            $assessments = LmsAssessment::withTrashed()->get(['id', 'title']);
            $targets = $assessments->filter(fn ($row) => $this->isCimaScsMock1($row));
            if ($targets->isEmpty()) {
                return;
            }
            $windows = ['2026-11-04 20:00:00', '2026-11-05 20:00:00'];
            foreach ($targets as $assessment) {
                $aid = (int) ($assessment->id ?? 0);
                $sittings = LmsAssessmentAssignment::query()->where('assessment_id', $aid)->get();
                $markerId = null;
                $assignedBy = null;
                foreach ($sittings->sortByDesc('id') as $row) {
                    if (! empty($row->marker_user_id)) {
                        $markerId = (int) $row->marker_user_id;
                        $assignedBy = $row->assigned_by_user_id ?? $row->assigned_by_admin_id ?? null;
                        break;
                    }
                }
                foreach ($yahias as $user) {
                    $uid = (int) ($user->id ?? 0);
                    if ($uid <= 0) {
                        continue;
                    }
                    foreach ($windows as $local) {
                        $want = \Carbon\Carbon::parse($local, 'Asia/Dubai');
                        $exists = $sittings->contains(function ($row) use ($uid, $want, $assessment) {
                            if ((int) ($row->student_id ?? 0) !== $uid) {
                                return false;
                            }
                            $start = null;
                            try {
                                $start = method_exists($row, 'windowStart') ? $row->windowStart() : ($row->available_from ?? null);
                            } catch (\Throwable $e) {
                                $start = $row->available_from ?? null;
                            }
                            if (! $start) {
                                return false;
                            }
                            try {
                                $day = \Carbon\Carbon::parse($start)->timezone('Asia/Dubai');

                                return $day->isSameDay($want);
                            } catch (\Throwable $e) {
                                return false;
                            }
                        });
                        if ($exists) {
                            continue;
                        }
                        $asg = new LmsAssessmentAssignment();
                        $asg->assessment_id = $aid;
                        $asg->student_id = $uid;
                        $asg->class_batch_id = null;
                        if (Schema::hasColumn($asg->getTable(), 'sitting_kind')) {
                            $asg->sitting_kind = LmsAssessmentAssignment::KIND_EXAM;
                        }
                        if (Schema::hasColumn($asg->getTable(), 'timezone')) {
                            $asg->timezone = 'Asia/Dubai';
                        }
                        if (Schema::hasColumn($asg->getTable(), 'marker_user_id') && $markerId) {
                            $asg->marker_user_id = $markerId;
                        }
                        if (Schema::hasColumn($asg->getTable(), 'assigned_by_user_id') && $assignedBy) {
                            $asg->assigned_by_user_id = (int) $assignedBy;
                        }
                        if (Schema::hasColumn($asg->getTable(), 'roster_user_ids')) {
                            $asg->roster_user_ids = [$uid];
                        }
                        $start = $want->copy()->timezone(config('app.timezone', 'Asia/Dubai'));
                        $asg->available_from = $start;
                        $asg->available_until = $want->copy()->addMinutes(180)->timezone(config('app.timezone', 'Asia/Dubai'));
                        $asg->save();
                        $sittings->push($asg);
                    }
                }
            }
            $this->scsMock1AssignmentsCache = [];
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** Mock 1/2 appointment from the canonical sitting for this student. */
    public function scsMock1AssignmentFromDatabase($assessment, $student = null, $current = null)
    {
        if ($current && method_exists($current, 'isSelfSitting') && $current->isSelfSitting()) {
            return $current;
        }
        if ($student && $this->isCimaScsAssignedMock($assessment)) {
            $canonical = $this->canonicalSittingForStudent($student, $assessment);
            if ($canonical) {
                return $canonical;
            }
        }
        if (! $this->isCimaScsMock1($assessment)) {
            return $current;
        }
        $aid = (int) ($assessment->id ?? 0);
        if ($aid <= 0) {
            return $current;
        }
        if ($current && $this->isMock1YahiaNovemberSitting($current, $assessment, $student)) {
            return $current;
        }
        try {
            if (! isset($this->scsMock1AssignmentsCache[$aid])) {
                $this->scsMock1AssignmentsCache[$aid] = LmsAssessmentAssignment::query()
                    ->where('assessment_id', $aid)
                    ->orderBy('id')
                    ->get();
            }
            $rows = $this->scsMock1AssignmentsCache[$aid];
        } catch (\Throwable $e) {
            return $current;
        }
        $rows = $rows->filter(fn ($row) => ! $this->isMock1CopiedNovemberSitting($row, $assessment))->values();
        $cid = (int) ($current->id ?? 0);
        if ($cid > 0) {
            $same = $rows->first(fn ($row) => (int) ($row->id ?? 0) === $cid);

            return $same ?: ($this->isMock1CopiedNovemberSitting($current, $assessment) ? null : $current);
        }
        $sid = (int) ($student->id ?? 0);
        if ($sid > 0) {
            $own = $rows->first(fn ($row) => (int) ($row->student_id ?? 0) === $sid);
            if ($own) {
                return $own;
            }
        }

        return $current;
    }

    public function isYahiaUser($user): bool
    {
        if (! $user) {
            return false;
        }
        $hay = mb_strtolower(trim((string) ($user->name ?? '') . ' ' . (string) ($user->email ?? '') . ' ' . (string) ($user->username ?? '')));

        return str_contains($hay, 'yahia') || str_contains($hay, 'yahya');
    }

    /** CIMA MCS Mock 1 only (not SCS, not Mock 2). */
    public function isCimaMcsMock1($assessment): bool
    {
        if (! $assessment) {
            return false;
        }
        $title = mb_strtolower(trim(
            (string) ($assessment->title ?? '') . ' ' . (string) ($assessment->course->title ?? '')
        ));
        if ($title === '' || str_contains($title, 'scs') || str_contains($title, 'masterclass')) {
            return false;
        }

        return (str_contains($title, 'mcs') || str_contains($title, 'management case'))
            && (bool) preg_match('/\bmock\s*1\b/', $title);
    }

    public function usesStrictExamWindow(LmsAssessment $assessment): bool
    {
        $hay = mb_strtolower(trim(
            (string) ($assessment->title ?? '') . ' ' . (string) ($assessment->course->title ?? '')
        ));

        return str_contains($hay, 'cima')
            && (
                str_contains($hay, 'mcs')
                || str_contains($hay, 'scs')
                || str_contains($hay, 'fcs')
                || str_contains($hay, 'management case')
                || str_contains($hay, 'strategic case')
                || str_contains($hay, 'case study')
            );
    }

    public function sittingCanStartAfterWindow(LmsAssessment $assessment, ?LmsAssessmentAssignment $assignment = null): bool
    {
        if ($this->usesStrictExamWindow($assessment)) {
            return false;
        }
        if ($assignment && $assignment->isSelfSitting()) {
            return true;
        }

        return $assessment->isMock()
            || $assessment->isPractice()
            || $assessment->kind === LmsAssessment::KIND_GENERAL
            || $assessment->isSkill();
    }

    /** Person SCS Mock sittings this student was assigned (Mock 1 and Mock 2). */
    protected function attachScsMockSittingsForUser(User $user, Collection $out, ?string $kind = null): void
    {
        $userId = (int) $user->id;
        if ($userId <= 0) {
            return;
        }
        try {
            $aids = LmsAssessment::query()
                ->where(function ($q) {
                    $q->where('title', 'like', '%SCS Mock%')
                        ->orWhere('title', 'like', '%SCS mock%');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();
            if ($aids->isEmpty()) {
                return;
            }
            $assignments = LmsAssessmentAssignment::query()
                ->with(['marker', 'assessment.course'])
                ->whereIn('assessment_id', $aids->all())
                ->where(function ($q) use ($userId) {
                    $q->where('student_id', $userId);
                    if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                        $q->orWhereJsonContains('roster_user_ids', $userId)
                            ->orWhereJsonContains('roster_user_ids', (string) $userId);
                    }
                    $q->orWhereNotNull('class_batch_id');
                })
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            return;
        }
        $seenAsg = $out->map(fn ($row) => (int) ($row['assignment']->id ?? 0))->filter()->all();
        foreach ($assignments as $assignment) {
            if (! $assignment || empty($assignment->id) || in_array((int) $assignment->id, $seenAsg, true)) {
                continue;
            }
            if (! $this->assignmentIncludesStudent($assignment, $userId)) {
                continue;
            }
            $assessment = $assignment->assessment ?: LmsAssessment::query()->with('course')->find($assignment->assessment_id);
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                continue;
            }
            if ($kind && $assessment->kind !== $kind) {
                continue;
            }
            if (! $assessment->isPublished()) {
                $hasAttempt = LmsAssessmentAttempt::query()
                    ->where('assessment_id', $assessment->id)
                    ->where('user_id', $userId)
                    ->exists();
                if (! $hasAttempt) {
                    continue;
                }
            }
            $assignment->setRelation('assessment', $assessment);
            $out->push(['assessment' => $assessment, 'assignment' => $assignment]);
            $seenAsg[] = (int) $assignment->id;
        }
    }

    /** The five BAT-0003 CIMA people see that sitting even if they are not on the batch pivot. */
    protected function attachCimaCohortSittingsForUser(User $user, Collection $out, ?string $kind = null): void
    {
        if (! $this->isCimaCohortEmail($user->email ?? null)) {
            return;
        }
        try {
            $aids = LmsAssessment::query()
                ->where(function ($q) {
                    $q->where('title', 'like', '%CIMA MCS%')
                        ->orWhere('title', 'like', '%Management Case Study%');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();
            if ($aids->isEmpty()) {
                return;
            }
            $assignments = LmsAssessmentAssignment::query()
                ->with(['marker', 'assessment.course'])
                ->whereIn('assessment_id', $aids->all())
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            return;
        }
        $seenAsg = $out->map(fn ($row) => (int) ($row['assignment']->id ?? 0))->filter()->all();
        foreach ($assignments as $assignment) {
            if (! $assignment || empty($assignment->id)) {
                continue;
            }
            if (in_array((int) $assignment->id, $seenAsg, true)) {
                continue;
            }
            if (method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting()) {
                continue;
            }
            if (Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
                $ex = collect($assignment->excluded_user_ids ?? [])->map(fn ($id) => (int) $id);
                if ($ex->contains((int) $user->id)) {
                    continue;
                }
            }
            $assessment = $assignment->assessment ?: LmsAssessment::query()->with('course')->find($assignment->assessment_id);
            if (! $this->studentShouldSeeSitting($user, $assessment, $assignment, [])) {
                continue;
            }
            if ($kind && $assessment->kind !== $kind) {
                continue;
            }
            $assignment->setRelation('assessment', $assessment);
            $orig = $this->originalExamSittingFor($assessment);
            if ($orig && (int) $orig->id !== (int) $assignment->id
                && empty($assignment->student_id)
                && ! empty($assignment->class_batch_id)) {
                $assignment = $orig;
            }
            if (method_exists($assignment, 'applyMissingExamWindow') && ! $assignment->available_from) {
                $assignment->applyMissingExamWindow('2026-10-26 20:30:00', 'Asia/Dubai', 180);
            }
            $out->push(['assessment' => $assessment, 'assignment' => $assignment]);
            $seenAsg[] = (int) $assignment->id;
        }
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

    /** Attempt belongs to this sitting only. Test attempts stay on Test; Self Test attempts stay on Self Test. */
    public function attemptBelongsToAssignment(LmsAssessmentAttempt $att, ?LmsAssessmentAssignment $asg): bool
    {
        if (! $asg) {
            return false;
        }
        if (Schema::hasColumn('lms_assessment_attempts', 'assignment_id') && $att->assignment_id) {
            return (int) $att->assignment_id === (int) $asg->id;
        }
        if ((int) ($att->user_id ?? 0) > 0 && ! $this->assignmentIncludesStudent($asg, (int) $att->user_id)) {
            return false;
        }
        if (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) {
            if (! $att->started_at) {
                return false;
            }
            return $asg->created_at
                ? $att->started_at->copy()->gte($asg->created_at->copy()->subMinutes(2))
                : false;
        }
        if ((int) ($att->assessment_id ?? 0) !== (int) ($asg->assessment_id ?? 0)) {
            return false;
        }
        if (! $att->started_at) {
            return false;
        }
        $started = $att->started_at->copy();
        $start = $asg->windowStart() ?: $asg->available_from;
        if (! $start) {
            return false;
        }
        $end = $asg->appointmentEnd();
        if ($started->lt($start->copy()->subMinutes(2))) {
            return false;
        }
        if ($end && $started->gt($end->copy()->addMinutes(2))) {
            return false;
        }

        return true;
    }

    /**
     * Attempt N of T for this user's sittings of a test (oldest assignment = 1).
     *
     * @return array{number:int,total:int}
     */
    public function sittingAttemptMeta(int $userId, ?LmsAssessment $assessment, ?LmsAssessmentAssignment $assignment): array
    {
        if (! $assessment || ! $assignment) {
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

        $wantSelf = method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting();
        $sittings = LmsAssessmentAssignment::query()
            ->where('assessment_id', $assessment->id)
            ->where(function ($q) use ($userId, $batchIds) {
                $q->where('student_id', $userId);
                if ($batchIds !== []) {
                    $q->orWhereIn('class_batch_id', $batchIds);
                }
            })
            ->orderBy('id')
            ->get()
            ->filter(function ($asg) use ($wantSelf) {
                $isSelf = method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting();

                return $wantSelf ? $isSelf : ! $isSelf;
            })
            ->values();

        $sitCount = max(1, $sittings->count());
        $number = 1;
        foreach ($sittings->values() as $i => $asg) {
            if ((int) $asg->id === (int) $assignment->id) {
                $number = $i + 1;
                break;
            }
        }
        $max = $this->maxAttemptsFor($assessment);
        $usedOnSitting = LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $userId)
            ->get()
            ->filter(fn ($att) => $this->attemptBelongsToAssignment($att, $assignment))
            ->count();
        $number = max(1, $usedOnSitting ?: $number);
        $total = max($max, 1);

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
                $usedOnSitting = $attempts->filter(fn ($att) => $this->attemptBelongsToAssignment($att, $asg))->count();
                if ($usedOnSitting >= $this->maxAttemptsFor($assessment)) {
                    return false;
                }
                if ($usedOnSitting === 0 && ! $asg->isSelfSitting()
                    && method_exists($asg, 'isMissedByStartGrace') && $asg->isMissedByStartGrace()) {
                    return false;
                }
                if ($asg->isSelfSitting() || $asg->isAvailableNow()) {
                    return true;
                }
                if ($usedOnSitting === 0 && $this->sittingCanStartAfterWindow($assessment, $asg)
                    && ! (method_exists($asg, 'isMissedByStartGrace') && $asg->isMissedByStartGrace())) {
                    return true;
                }

                // Remaining attempts on a sitting already started may continue after the window.
                return $usedOnSitting > 0;
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
        abort_unless(
            $assessment->isPublished() || $this->canRetake($user, $assessment),
            403
        );
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
            : $this->attemptSlotsUsed($user, $assessment);
        if ($used >= $max) {
            abort(403, 'Maximum attempts for this test have been reached.');
        }
        $openSittings = $fromFolder
            ? collect()
            : $this->openUnusedAssignmentsFor($user, $assessment)->sortBy('id')->values();
        $assignment = null;
        $allSittings = $accessRows->map(fn ($row) => $row['assignment'])->filter()->values();
        if ($assignmentId) {
            $assignment = $openSittings->first(fn ($asg) => (int) $asg->id === $assignmentId)
                ?: $allSittings->first(fn ($asg) => (int) $asg->id === $assignmentId);
        }
        if (! $assignment && ! $fromFolder) {
            $assignment = $openSittings->first();
        }
        if ($assignment) {
            $usedOnSitting = LmsAssessmentAttempt::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $user->id)
                ->get()
                ->filter(fn ($att) => $this->attemptBelongsToAssignment($att, $assignment))
                ->count();
            $allowAnytimeRetake = $this->canRetake($user, $assessment, $assignment);
            if (! $assignment->isSelfSitting() && $assignment->isNotYetOpen() && $usedOnSitting === 0) {
                abort(403, 'This test is not open yet.');
            } elseif (! $assignment->isSelfSitting() && method_exists($assignment, 'isMissedByStartGrace') && $assignment->isMissedByStartGrace() && $usedOnSitting === 0) {
                if ($allowAnytimeRetake) {
                    $assignment = $this->ensureAnytimeRetakeSitting($user, $assessment, $assignment);
                } else {
                    abort(403, 'The start window for this test has ended.');
                }
            } elseif ($usedOnSitting > 0 && $allowAnytimeRetake && ! $assignment->isSelfSitting()) {
                $assignment = $this->ensureAnytimeRetakeSitting($user, $assessment, $assignment);
            }
        }
        $hasExplicitAssign = $accessRows->contains(fn ($row) => $row['assignment'] !== null);
        if (! $assignment) {
            if ($fromFolder) {
                // folder self tests already passed the slot check above
            } elseif ($assessment->isSkill() && ! $hasExplicitAssign) {
                // skill tests without an assignment already passed the slot check above
            } elseif ($this->canRetake($user, $assessment, $allSittings->first())) {
                $assignment = $this->ensureAnytimeRetakeSitting($user, $assessment, $allSittings->first());
            } else {
                $anyAsg = $allSittings->first();
                if ($anyAsg && $anyAsg->isNotYetOpen()) {
                    abort(403, 'This test is not open yet.');
                }
                if ($anyAsg && method_exists($anyAsg, 'isMissedByStartGrace') && $anyAsg->isMissedByStartGrace() && ! $this->sittingCanStartAfterWindow($assessment, $anyAsg)) {
                    abort(403, 'The start window for this test has ended.');
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
        if ($assignment && ! $assignment->isSelfSitting() && $assignment->isAvailableNow()) {
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

    public function pauseAttempt(LmsAssessmentAttempt $attempt): LmsAssessmentAttempt
    {
        if (! $attempt->isInProgress()) {
            throw new \RuntimeException('This attempt is no longer in progress.');
        }
        $attempt->loadMissing('assessment');
        if (! $attempt->assessment || ! $attempt->assessment->allowsPause()) {
            throw new \RuntimeException('Pause is not enabled for this test.');
        }
        if ($attempt->isPaused()) {
            return $attempt->fresh() ?: $attempt;
        }

        $remaining = 0;
        if ($attempt->expires_at) {
            $remaining = max(0, now()->diffInSeconds($attempt->expires_at, false));
        }
        $started = now();
        session([
            $attempt->pauseSessionKey() => [
                'started_at' => $started->toIso8601String(),
                'remaining' => $remaining,
            ],
        ]);

        if (Schema::hasColumn('lms_assessment_attempts', 'paused_at')) {
            $attempt->paused_at = $started;
        }
        if ($attempt->expires_at) {
            // Park the deadline so auto-submit cannot fire while paused.
            $attempt->expires_at = $started->copy()->addSeconds($remaining)->addDays(7);
        }
        $attempt->save();

        return $attempt->fresh() ?: $attempt;
    }

    public function resumeAttempt(LmsAssessmentAttempt $attempt): LmsAssessmentAttempt
    {
        if (! $attempt->isInProgress()) {
            throw new \RuntimeException('This attempt is no longer in progress.');
        }
        $sess = session($attempt->pauseSessionKey());
        $started = null;
        $remaining = null;
        if (is_array($sess)) {
            $started = ! empty($sess['started_at']) ? \Carbon\Carbon::parse($sess['started_at']) : null;
            $remaining = isset($sess['remaining']) ? (int) $sess['remaining'] : null;
        }
        if (! $started && Schema::hasColumn('lms_assessment_attempts', 'paused_at') && $attempt->paused_at) {
            $started = $attempt->paused_at->copy();
        }
        if (! $started) {
            return $attempt;
        }

        $ended = now();
        $seconds = max(0, $started->diffInSeconds($ended));
        if (Schema::hasColumn('lms_assessment_attempts', 'pause_seconds')) {
            $attempt->pause_seconds = (int) ($attempt->pause_seconds ?? 0) + $seconds;
        }
        if (Schema::hasColumn('lms_assessment_attempts', 'pause_log_json')) {
            $log = is_array($attempt->pause_log_json) ? $attempt->pause_log_json : [];
            $log[] = [
                'started_at' => $started->toIso8601String(),
                'ended_at' => $ended->toIso8601String(),
                'seconds' => $seconds,
            ];
            $attempt->pause_log_json = $log;
        }
        if ($remaining === null && $attempt->expires_at) {
            $remaining = max(0, $started->copy()->addDays(7)->diffInSeconds($attempt->expires_at, false));
        }
        if ($remaining !== null) {
            $attempt->expires_at = $ended->copy()->addSeconds(max(0, $remaining));
        } elseif ($attempt->expires_at && $seconds > 0) {
            $attempt->expires_at = $attempt->expires_at->copy()->addSeconds($seconds);
        }
        if (Schema::hasColumn('lms_assessment_attempts', 'paused_at')) {
            $attempt->paused_at = null;
        }
        $attempt->save();
        session()->forget($attempt->pauseSessionKey());

        return $attempt->fresh() ?: $attempt;
    }

    public function submitAttempt(LmsAssessmentAttempt $attempt, bool $expired = false): LmsAssessmentAttempt
    {
        if (! in_array($attempt->status, ['in_progress'], true)) {
            return $attempt;
        }

        if ($attempt->paused_at) {
            $this->resumeAttempt($attempt);
            $attempt = $attempt->fresh() ?: $attempt;
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
        if ($assessment->isInstantResults() || $assessment->isPractice()) {
            $attempt->results_published = true;
            $attempt->results_published_at = now();
        }
        if (! $needsManual) {
            $attempt->status = 'graded';
            $attempt->graded_at = now();
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
        $rosterIds = collect($assignment->roster_user_ids ?? []);
        if ($assignment->class_batch_id) {
            $rosterIds = $rosterIds->merge(ClassBatch::studentUserIdsFor((int) $assignment->class_batch_id));
        }
        $rosterIds = $rosterIds->map(fn ($id) => (int) $id)->unique()->filter()->values();
        if ($rosterIds->isNotEmpty()) {
            $users = $users->merge(User::query()->whereIn('id', $rosterIds->all())->get());
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
     * Retake when slots remain (Max attempts) and either the scheduled sitting was missed
     * or a finished attempt has published results. Retake starts anytime (no new appointment).
     */
    public function canRetake(User $user, LmsAssessment $assessment, ?LmsAssessmentAssignment $assignment = null): bool
    {
        if (LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->exists()) {
            return false;
        }
        $max = $this->maxAttemptsFor($assessment);
        if ($this->attemptSlotsUsed($user, $assessment) >= $max) {
            return false;
        }
        $latest = LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['submitted', 'graded', 'expired'])
            ->orderByDesc('id')
            ->first();
        if ($latest) {
            return $this->attemptResultsUnlocked($assessment, $latest);
        }
        $missedThisSitting = $assignment
            && ! $assignment->isSelfSitting()
            && ! $this->sittingCanStartAfterWindow($assessment, $assignment)
            && method_exists($assignment, 'isMissedByStartGrace')
            && $assignment->isMissedByStartGrace();
        if ($missedThisSitting || $this->missedExamSlotCount($user, $assessment) > 0) {
            return $max > 1;
        }
        if (! $assessment->isPublished()) {
            return false;
        }
        if (! $assignment && $this->userHasFolderSelfTest($user, $assessment)) {
            return $this->folderSelfAttemptsUsed($user, $assessment) < $max;
        }
        if ($assignment && $assignment->isSelfSitting()) {
            return $this->openUnusedAssignmentsFor($user, $assessment)
                ->contains(fn ($asg) => (int) $asg->id === (int) $assignment->id);
        }

        return $this->openUnusedAssignmentsFor($user, $assessment)->isNotEmpty();
    }

    public function attemptSlotsUsed(User $user, LmsAssessment $assessment): int
    {
        return $this->attemptsUsed($user, $assessment) + $this->missedExamSlotCount($user, $assessment);
    }

    /** One missed scheduled exam counts as one used slot (so the next start is attempt 2). */
    public function missedExamSlotCount(User $user, LmsAssessment $assessment): int
    {
        $attempts = LmsAssessmentAttempt::query()
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $user->id)
            ->get();
        if ($attempts->isNotEmpty()) {
            return 0;
        }
        $examSittings = $this->availableAssessmentsForUser($user, $assessment->kind)
            ->filter(fn ($row) => (int) ($row['assessment']->id ?? 0) === (int) $assessment->id)
            ->map(fn ($row) => $row['assignment'] ?? null)
            ->filter()
            ->filter(function ($asg) use ($assessment) {
                return ! $asg->isSelfSitting()
                    && method_exists($asg, 'isMissedByStartGrace')
                    && $asg->isMissedByStartGrace()
                    && ! $this->sittingCanStartAfterWindow($assessment, $asg);
            });
        $hasUnusedMiss = $examSittings->contains(function ($asg) use ($attempts) {
            return $attempts->filter(fn ($att) => $this->attemptBelongsToAssignment($att, $asg))->isEmpty();
        });

        return $hasUnusedMiss ? 1 : 0;
    }

    public function attemptResultsUnlocked(LmsAssessment $assessment, LmsAssessmentAttempt $attempt): bool
    {
        if ($attempt->results_published) {
            return true;
        }
        if (method_exists($assessment, 'isInstantResults') && $assessment->isInstantResults()) {
            return true;
        }

        return $assessment->isPractice() && in_array($attempt->status, ['submitted', 'graded', 'expired'], true);
    }

    /** Create or reuse a start-anytime sitting for a remaining attempt after miss/publish. */
    public function ensureAnytimeRetakeSitting(User $user, LmsAssessment $assessment, ?LmsAssessmentAssignment $source = null): LmsAssessmentAssignment
    {
        $existing = LmsAssessmentAssignment::query()
            ->where('assessment_id', (int) $assessment->id)
            ->where('student_id', (int) $user->id)
            ->orderBy('id')
            ->get()
            ->first(function ($asg) use ($user, $assessment) {
                if (! $asg->isSelfSitting()) {
                    return false;
                }
                $used = LmsAssessmentAttempt::query()
                    ->where('assessment_id', $assessment->id)
                    ->where('user_id', $user->id)
                    ->get()
                    ->filter(fn ($att) => $this->attemptBelongsToAssignment($att, $asg))
                    ->count();

                return $used === 0;
            });
        if ($existing) {
            return $existing;
        }

        $asg = new LmsAssessmentAssignment();
        $asg->assessment_id = (int) $assessment->id;
        $asg->student_id = (int) $user->id;
        if (Schema::hasColumn($asg->getTable(), 'sitting_kind')) {
            $asg->sitting_kind = LmsAssessmentAssignment::KIND_SELF;
        }
        if (Schema::hasColumn($asg->getTable(), 'timezone')) {
            $asg->timezone = $source->timezone ?? (string) config('app.timezone', 'Asia/Dubai');
        }
        if (Schema::hasColumn($asg->getTable(), 'marker_user_id') && ! empty($source?->marker_user_id)) {
            $asg->marker_user_id = (int) $source->marker_user_id;
        }
        if (Schema::hasColumn($asg->getTable(), 'available_from')) {
            $asg->available_from = null;
        }
        if (Schema::hasColumn($asg->getTable(), 'available_until')) {
            $asg->available_until = null;
        }
        $asg->save();

        return $asg;
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

        $userAttemptsQuery = LmsAssessmentAttempt::query()
            ->with(['assessment.course'])
            ->where('user_id', $user->id)
            ->whereHas('assessment')
            ->orderByDesc('id');
        if ($kind) {
            $userAttemptsQuery->whereHas('assessment', fn ($q) => $q->where('kind', $kind));
        }
        try {
            $userAttempts = $userAttemptsQuery->get();
        } catch (\Throwable $e) {
            report($e);
            $userAttempts = LmsAssessmentAttempt::query()
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->get();
        }

        $itemAssessmentIds = $items
            ->filter(fn ($r) => is_array($r) && ! empty($r['assessment']))
            ->pluck('assessment.id')
            ->map(fn ($id) => (int) $id)
            ->all();
        foreach ($userAttempts as $att) {
            $assessment = $att->assessment ?: LmsAssessment::query()->find($att->assessment_id);
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                continue;
            }
            $status = mb_strtolower(trim((string) ($assessment->status ?? '')));
            if (in_array($status, ['disabled', 'draft'], true)) {
                continue;
            }
            if ($this->studentIsHiddenOnMcs($user, $assessment)) {
                continue;
            }
            if (in_array((int) $assessment->id, $itemAssessmentIds, true)) {
                continue;
            }
            $asg = null;
            if (! empty($att->assignment_id)) {
                $asg = LmsAssessmentAssignment::query()->find($att->assignment_id);
                if (! $asg || ! $this->assignmentIncludesStudent($asg, (int) $user->id)) {
                    continue;
                }
            } else {
                continue;
            }
            $items->push(['assessment' => $assessment, 'assignment' => $asg]);
            $itemAssessmentIds[] = (int) $assessment->id;
        }

        $attemptsByAssessment = $userAttempts->groupBy(fn ($a) => (int) $a->assessment_id);

        // Attempt N of T per assessment (oldest assigned sitting = attempt 1).
        $sittingMeta = [];
        foreach ($items->filter(fn ($r) => is_array($r) && ! empty($r['assessment']))->groupBy(function ($r) {
            $asg = $r['assignment'] ?? null;
            $kind = ($asg && method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) ? 'self' : 'exam';

            return (int) $r['assessment']->id . ':' . $kind;
        }) as $group) {
            $ordered = $group
                ->filter(fn ($r) => is_array($r) && ! empty($r['assignment']?->id))
                ->sortBy(fn ($r) => (int) ($r['assignment']->id ?? 0))
                ->values();
            $total = max(1, $ordered->count());
            foreach ($ordered as $i => $r) {
                $aid = (int) $r['assessment']->id;
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
            if (! is_array($row)) {
                continue;
            }
            /** @var LmsAssessment|null $assessment */
            $assessment = $row['assessment'] ?? null;
            /** @var LmsAssessmentAssignment|null $assignment */
            $assignment = $row['assignment'] ?? null;
            if (! $assessment) {
                continue;
            }
            try {
            if ($this->studentIsHiddenOnMcs($user, $assessment)
                || ! $this->studentShouldSeeSitting($user, $assessment, $assignment, [])) {
                continue;
            }
            if (! $assignment || empty($assignment->id)) {
                $assignment = $this->resolveAssignmentForUser($user, $assessment);
            }
            if ($assignment && ! $this->assignmentIncludesStudent($assignment, (int) $user->id)) {
                continue;
            }
            if ($assignment && ! $this->studentShouldSeeSitting($user, $assessment, $assignment, [])) {
                continue;
            }
            if ($assignment && empty($row['from_folder'])
                && empty($assignment->student_id)
                && ! empty($assignment->class_batch_id)
                && ! ($assignment->isSelfSitting())) {
                $orig = $this->originalExamSittingFor($assessment);
                if ($orig && (int) $orig->id !== (int) $assignment->id) {
                    $assignment = $orig;
                }
            }
            if ($assignment && ! $this->assignmentIncludesStudent($assignment, (int) $user->id)) {
                continue;
            }
            $fromFolder = ! empty($row['from_folder']);
            if ($assignment && ! $fromFolder) {
                $this->alignSittingWithExistingExamRule($assignment, $assessment);
            }
            $aid = (int) $assessment->id;
            $asgId = (int) ($assignment?->id ?? 0);
            $mcs1AlreadyTaken = $this->isCimaMcsMock1($assessment)
                && $asgId > 0
                && $attemptsByAssessment->get($aid)
                && $attemptsByAssessment->get($aid)->contains(function ($att) use ($asgId) {
                    return (int) ($att->assignment_id ?? 0) === $asgId
                        && in_array($att->status, ['submitted', 'graded', 'expired'], true);
                });
            if ($mcs1AlreadyTaken) {
                $alreadyInTakenBucket = $results->concat($submitted)->concat($inProgress)->contains(
                    fn ($r) => is_array($r) && (int) data_get($r, 'assignment.id', 0) === $asgId
                );
                if ($alreadyInTakenBucket) {
                    continue;
                }
            }
            $metaKey = $aid . ':' . $asgId;
            $meta = (is_array($sittingMeta) && isset($sittingMeta[$metaKey]) && is_array($sittingMeta[$metaKey]))
                ? $sittingMeta[$metaKey]
                : ['number' => 1, 'total' => 1];
            $max = $this->maxAttemptsFor($assessment);
            $attempts = $attemptsByAssessment->get($aid) ?? collect();
            $used = $attempts->count();
            $isSelf = ($assignment && $assignment->isSelfSitting()) || $fromFolder;

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
            $attemptTotal = max($max, (int) ($meta['total'] ?? 1));
            $matched = $sittingAttempts->sortByDesc('id')->first();
            if (! $matched && ! $isSelf) {
                $matched = $attempts->first(function ($att) use ($assignment, $claimedAttemptIds) {
                    return ! isset($claimedAttemptIds[(int) $att->id])
                        && $this->attemptBelongsToAssignment($att, $assignment);
                });
            }
            $attemptNumber = $matched
                ? max(1, $usedOnSitting)
                : max(1, $usedOnSitting + 1);
            if (! $isSelf && $usedOnSitting === 0) {
                $attemptNumber = (int) ($meta['number'] ?? $attemptNumber);
            }
            foreach ($sittingAttempts as $sa) {
                $claimedAttemptIds[(int) $sa->id] = true;
            }
            if ($matched && ! isset($claimedAttemptIds[(int) $matched->id])) {
                $claimedAttemptIds[(int) $matched->id] = true;
            }
            $isRetakeSitting = $usedOnSitting > 0 && $usedOnSitting < $max;

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
                    $matched = $matched->fresh() ?: $matched;
                }
                if ($matched && $matched->status === 'in_progress') {
                    if ($asgId > 0 && ! empty($inProgressShown[$asgId])) {
                        continue;
                    }
                    if ($asgId > 0) {
                        $inProgressShown[$asgId] = true;
                    }
                    $inProgress->push($base + [
                        'attempt' => $matched,
                        'can_retake' => false,
                    ]);
                    continue;
                }
            }

            $finished = $sittingAttempts
                ->filter(fn ($att) => in_array($att->status, ['submitted', 'graded', 'expired'], true))
                ->sortBy('id')
                ->values();
            if ($matched && in_array($matched->status, ['submitted', 'graded', 'expired'], true)
                && ! $finished->contains(fn ($att) => (int) $att->id === (int) $matched->id)) {
                $finished->push($matched);
            }

            // Remaining exam/self retakes: only after published (or instant/practice) results.
            $hasUnlockedResults = $finished->contains(fn ($done) => $this->attemptResultsUnlocked($assessment, $done));
            $remainingAttempts = $isSelf && $usedOnSitting < $max && $assessment->isPublished() && $hasUnlockedResults
                && $this->attemptSlotsUsed($user, $assessment) < $max;
            if ($finished->isNotEmpty()) {
                $finishTotal = max($max, $attemptTotal, $finished->count());
                foreach ($finished as $fi => $done) {
                    $rowBase = $base + [
                        'attempt' => $done,
                        'can_retake' => false,
                        'attempt_number' => $fi + 1,
                        'attempt_total' => $finishTotal,
                    ];
                    if ($assessment->isPractice() || $done->results_published) {
                        $results->push($rowBase);
                    } else {
                        $submitted->push($rowBase);
                    }
                }
                // Retake stays on Results. Do not add a second Available line.
                continue;
            }

            $unusedBucket = $this->unusedExamBucket($assignment, $assessment, $user);
            // Disabled tests: keep taken/missed history, but never offer a new start.
            if (! $assessment->isPublished()) {
                if ($unusedBucket === 'upcoming') {
                    $upcoming->push($base + [
                        'attempt' => null,
                        'can_retake' => false,
                    ]);
                } elseif ($unusedBucket === 'missed' && empty($mcs1AlreadyTaken)) {
                    $missed->push($base + [
                        'attempt' => null,
                        'can_retake' => $max > 1 || $this->canRetake($user, $assessment, $assignment),
                    ]);
                }
                continue;
            }

            if ($unusedBucket === 'upcoming') {
                $upcoming->push($base + [
                    'attempt' => null,
                    'can_retake' => false,
                ]);
            } elseif ($unusedBucket === 'missed' && empty($mcs1AlreadyTaken)) {
                $missed->push($base + [
                    'attempt' => null,
                    'can_retake' => $max > 1 || $this->canRetake($user, $assessment, $assignment),
                    'attempt_number' => 1,
                    'attempt_total' => $max,
                ]);
            } else {
                // Each unused assigned sitting is its own start, even if Max attempts is 1.
                $available->push($base + [
                    'attempt' => null,
                    'can_retake' => $isRetakeSitting,
                ]);
            }
            } catch (\Throwable $e) {
                report($e);
                continue;
            }
        }

        foreach ($attemptsByAssessment as $aid => $atts) {
            $sample = $items->first(fn ($r) => is_array($r) && (int) data_get($r, 'assessment.id') === (int) $aid);
            $assessment = is_array($sample) ? ($sample['assessment'] ?? null) : null;
            $assessment = $assessment ?: $atts->first()?->assessment;
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                continue;
            }
            foreach ($atts as $att) {
                if (isset($claimedAttemptIds[(int) $att->id])) {
                    continue;
                }
                if (! in_array($att->status, ['submitted', 'graded', 'expired'], true)) {
                    continue;
                }
                $claimedAttemptIds[(int) $att->id] = true;
                $matchRow = $items->first(fn ($r) => is_array($r) && $this->attemptBelongsToAssignment($att, $r['assignment'] ?? null));
                $asg = is_array($matchRow) ? ($matchRow['assignment'] ?? null) : null;
                if (! $asg && is_array($sample)) {
                    $asg = $sample['assignment'] ?? null;
                }
                $rowBase = [
                    'assessment' => $assessment,
                    'assignment' => $asg,
                    'attempt' => $att,
                    'attempts_used' => $atts->count(),
                    'max_attempts' => $this->maxAttemptsFor($assessment),
                    'attempt_number' => 1,
                    'attempt_total' => max(1, $atts->count(), $this->maxAttemptsFor($assessment)),
                    'from_folder' => false,
                    'folder_id' => null,
                    'can_retake' => false,
                ];
                if ($assessment->isPractice() || $att->results_published) {
                    $results->push($rowBase);
                } else {
                    $submitted->push($rowBase);
                }
            }
        }
        $submitted = $submitted->unique(fn ($r) => (int) ($r['attempt']->id ?? 0))->values();
        $results = $results->unique(fn ($r) => (int) ($r['attempt']->id ?? 0))->values();
        $results = $results->map(function ($row) use ($user) {
            $row['can_retake'] = $this->canRetake($user, $row['assessment'], $row['assignment'] ?? null);

            return $row;
        });
        $submitted = $submitted->map(function ($row) use ($user) {
            $row['can_retake'] = $this->canRetake($user, $row['assessment'], $row['assignment'] ?? null);

            return $row;
        });

        // One Upcoming/Available row per test for this student. Extra identical assigns
        // stay in attempt_total (Attempt 1 of 3) instead of repeating the mock three times.
        $collapseOpen = function (Collection $rows) {
            return $rows->groupBy(function ($r) {
                $from = optional($r['assignment']?->available_from)->timestamp ?? 0;
                $kind = ($r['assignment'] && method_exists($r['assignment'], 'isSelfSitting') && $r['assignment']->isSelfSitting())
                    ? 'self'
                    : 'exam';

                return (int) $r['assessment']->id . ':' . $kind . ':' . $from;
            })->map(function ($group) {
                $picked = $group->sortBy(fn ($r) => (int) ($r['assignment']->id ?? 0))->first();
                $picked['attempt_total'] = max(
                    (int) ($picked['attempt_total'] ?? 1),
                    (int) $group->max('attempt_total'),
                    $group->count()
                );

                return $picked;
            })->values();
        };
        $available = $collapseOpen($available);
        $upcoming = $collapseOpen($upcoming);
        $missed = $collapseOpen($missed);

        $hasAppointment = fn ($r) => ! empty($r['assignment']->id) || ! empty($r['from_folder']);
        $available = $available->filter($hasAppointment)->values();
        $upcoming = $upcoming->filter($hasAppointment)->values();
        $missed = $missed->filter($hasAppointment)->values();

        // Same Appointment ID already in Results/Submitted/In progress must not also appear as Missed.
        $takenSittingIds = $results->concat($submitted)->concat($inProgress)
            ->map(fn ($r) => (int) ($r['assignment']->id ?? 0))
            ->filter()
            ->unique()
            ->all();
        $mcs1TakenAssessmentIds = $results->concat($submitted)->concat($inProgress)
            ->filter(fn ($r) => $this->isCimaMcsMock1($r['assessment'] ?? null))
            ->map(fn ($r) => (int) ($r['assessment']->id ?? 0))
            ->filter()
            ->unique()
            ->all();
        $missed = $missed->filter(function ($r) use ($takenSittingIds, $mcs1TakenAssessmentIds) {
            $asgId = (int) ($r['assignment']->id ?? 0);
            if ($asgId > 0 && in_array($asgId, $takenSittingIds, true)) {
                return false;
            }
            if ($this->isCimaMcsMock1($r['assessment'] ?? null)
                && in_array((int) ($r['assessment']->id ?? 0), $mcs1TakenAssessmentIds, true)) {
                return false;
            }

            return true;
        })->values();

        $keepRow = function ($r) use ($user) {
            if (! is_array($r)) {
                return false;
            }
            $assessment = $r['assessment'] ?? null;
            $assignment = $r['assignment'] ?? null;
            if (! $assessment) {
                return false;
            }
            if ($this->studentIsHiddenOnMcs($user, $assessment)) {
                return false;
            }
            if ($assignment && ! $this->assignmentIncludesStudent($assignment, (int) $user->id)) {
                return false;
            }
            if (! $this->studentShouldSeeSitting($user, $assessment, $assignment, [])) {
                return false;
            }
            $att = $r['attempt'] ?? null;
            if ($att && ! empty($att->assignment_id) && $assignment
                && (int) $att->assignment_id !== (int) $assignment->id) {
                return false;
            }

            return true;
        };
        $available = $available->filter($keepRow)->values();
        $upcoming = $upcoming->filter($keepRow)->values();
        $inProgress = $inProgress->filter($keepRow)->values();
        $submitted = $submitted->filter($keepRow)->values();
        $results = $results->filter($keepRow)->values();
        $missed = $missed->filter($keepRow)->values();

        return compact('available', 'upcoming', 'inProgress', 'submitted', 'results', 'missed');
    }
}
