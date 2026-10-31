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
        $this->restorePublishedAssignedTests();

        return $ok;
    }

    /** Published tests that still have sittings must stay on Assigned Tests / My Tests. */
    private function restorePublishedAssignedTests(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        if (! Schema::hasColumn('lms_assessments', 'deleted_at')) {
            return;
        }
        try {
            $ids = LmsAssessmentAssignment::query()->pluck('assessment_id')->unique()->filter()->values()->all();
            if ($ids === []) {
                return;
            }
            LmsAssessment::onlyTrashed()
                ->where('status', 'published')
                ->whereIn('id', $ids)
                ->restore();
        } catch (\Throwable $e) {
            report($e);
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

    public function actorCanAccessAttempt(LmsAssessmentAttempt $attempt): bool
    {
        if ($this->lms->isAdminActor() || panel_sees_all_dashboards()) {
            return true;
        }
        $attempt->loadMissing('assessment');
        if ($attempt->assessment?->isSkill()) {
            return $this->canManageSkillTests();
        }
        $uid = (int) (Auth::id() ?? 0);
        if ($uid > 0 && Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')) {
            $asgId = (int) ($attempt->assignment_id ?? 0);
            $isMarker = LmsAssessmentAssignment::query()
                ->where('marker_user_id', $uid)
                ->where('assessment_id', $attempt->assessment_id)
                ->when($asgId > 0, fn ($q) => $q->where('id', $asgId))
                ->exists();
            if ($isMarker) {
                return true;
            }
        }
        if (! $this->canManageStudentAssessments()) {
            return false;
        }
        $courseId = (int) ($attempt->assessment?->course_id ?? 0);

        return $courseId <= 0 || $this->lms->instructorAssignedToCourse($courseId);
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
            $studentCol = Schema::hasColumn('class_batch_student', 'student_id') ? 'student_id' : 'user_id';
            $batchIds = DB::table('class_batch_student')
                ->where($studentCol, $userId)
                ->pluck('class_batch_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }
        if (Schema::hasTable('class_schedule_student') && Schema::hasTable('class_schedules')) {
            $scheduleIds = DB::table('class_schedule_student')->where('student_id', $userId)->pluck('class_schedule_id');
            $fromSchedules = DB::table('class_schedules')
                ->whereIn('id', $scheduleIds)
                ->whereNotNull('batch_id')
                ->pluck('batch_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $batchIds = array_merge($batchIds, $fromSchedules);
        }
        $batchIds = array_values(array_unique(array_filter($batchIds)));

        $assignQuery = LmsAssessmentAssignment::query()
            ->with('marker')
            ->where(function ($q) use ($userId, $batchIds) {
                $q->where('student_id', $userId);
                if ($batchIds !== []) {
                    $q->orWhereIn('class_batch_id', $batchIds);
                }
            })
            ->orderByDesc('id');
        $direct = $assignQuery->get();
        $assessmentIds = $direct->pluck('assessment_id')->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
        $assessmentsById = $assessmentIds === []
            ? collect()
            : LmsAssessment::query()->with('course')->whereIn('id', $assessmentIds)->get()->keyBy(fn ($row) => (int) $row->id);

        $out = collect();
        foreach ($direct as $assignment) {
            $assessment = $assessmentsById->get((int) $assignment->assessment_id);
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
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

        if (method_exists(StudyMaterialFolder::class, 'ensureFolderAssessmentsTable')) {
            $this->appendFolderSelfTests($user, $out, $kind);
        }

        return $out;
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
        $seen = $out->map(fn ($row) => (int) ($row['assignment']->id ?? 0) . ':' . (int) ($row['assessment']->id ?? 0))->all();
        $userId = (int) $user->id;
        foreach ($assignments as $assignment) {
            $assessment = $assignment->assessment ?: LmsAssessment::query()->with('course')->find($assignment->assessment_id);
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                continue;
            }
            if ($kind && $assessment->kind !== $kind) {
                continue;
            }
            if (! $assessment->isPublished()) {
                continue;
            }
            $forThisStudent = (int) ($assignment->student_id ?? 0) === $userId;
            $isSelf = method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting();
            // Only the scheduled exam sitting. Do not inject self/anytime rows (those looked like extra Available tests).
            if ($isSelf) {
                continue;
            }
            if (! $forThisStudent && empty($assignment->class_batch_id) && empty($assignment->available_from)) {
                continue;
            }
            if (empty($assignment->available_from) && empty($assignment->available_until)) {
                continue;
            }
            $key = (int) $assignment->id . ':' . (int) $assessment->id;
            if (in_array($key, $seen, true)) {
                continue;
            }
            $assignment->setRelation('assessment', $assessment);
            if (method_exists($assignment, 'applyMissingExamWindow')) {
                $assignment->applyMissingExamWindow('2026-10-26 20:30:00', 'Asia/Dubai', 180);
            }
            $out->push(['assessment' => $assessment, 'assignment' => $assignment]);
            $seen[] = $key;
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

    /** Whether an attempt belongs to this assignment sitting (window, or created_at if open-ended). */
    public function attemptBelongsToAssignment(LmsAssessmentAttempt $att, ?LmsAssessmentAssignment $asg): bool
    {
        if (! $asg || ! $att->started_at) {
            return false;
        }
        if (Schema::hasColumn('lms_assessment_attempts', 'assignment_id') && $att->assignment_id) {
            if ((int) $att->assignment_id !== (int) $asg->id) {
                return false;
            }
            if ($asg->isSelfSitting() || ! $att->started_at) {
                return true;
            }
            $start = $asg->windowStart() ?: $asg->available_from;
            if ($start && $att->started_at->lt($start->copy()->subMinutes(2))) {
                return false;
            }
            $end = $asg->appointmentEnd();
            if ($end && $att->started_at->gt($end->copy()->addMinutes(2))) {
                return false;
            }

            return true;
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
                if ($asg->isSelfSitting() || $asg->isAvailableNow()) {
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
            if (! $assignment->isSelfSitting() && $assignment->isExpired() && $usedOnSitting === 0) {
                if ($allowAnytimeRetake) {
                    $assignment = $this->ensureAnytimeRetakeSitting($user, $assessment, $assignment);
                } else {
                    abort(403, 'The availability window for this test has ended.');
                }
            } elseif (! $assignment->isSelfSitting() && $assignment->isNotYetOpen() && $usedOnSitting === 0) {
                abort(403, 'This test is not open yet.');
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
                if ($anyAsg && $anyAsg->isExpired()) {
                    abort(403, 'The availability window for this test has ended.');
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
     * Retake when slots remain (Max attempts) and either the scheduled sitting was missed
     * or a finished attempt has published results. Retake starts anytime (no new appointment).
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
        if (! $assignment && $this->userHasFolderSelfTest($user, $assessment)) {
            return $this->folderSelfAttemptsUsed($user, $assessment) < $max;
        }
        if ($this->missedExamSlotCount($user, $assessment) > 0) {
            return true;
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
            ->filter(fn ($asg) => ! $asg->isSelfSitting() && $asg->isExpired());
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

        $itemAssessmentIds = $items->pluck('assessment.id')->map(fn ($id) => (int) $id)->all();
        foreach ($userAttempts as $att) {
            $assessment = $att->assessment ?: LmsAssessment::query()->find($att->assessment_id);
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                continue;
            }
            if (in_array((int) $assessment->id, $itemAssessmentIds, true)) {
                continue;
            }
            $asg = null;
            if (! empty($att->assignment_id)) {
                $asg = LmsAssessmentAssignment::query()->find($att->assignment_id);
            }
            $items->push(['assessment' => $assessment, 'assignment' => $asg]);
            $itemAssessmentIds[] = (int) $assessment->id;
        }

        $attemptsByAssessment = $userAttempts->groupBy(fn ($a) => (int) $a->assessment_id);

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
                if ($remainingAttempts) {
                    $available->push($base + [
                        'attempt' => null,
                        'can_retake' => true,
                        'attempt_number' => $usedOnSitting + 1,
                        'attempt_total' => $finishTotal,
                    ]);
                }
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

            $examWindowPassed = $assignment
                && ! $assignment->isSelfSitting()
                && ($assignment->isExpired()
                    || ($this->isCimaCohortEmail($user->email ?? null)
                        && $assignment->windowStart()
                        && now()->gt($assignment->windowStart()->copy()->addMinutes(max(1, (int) ($assessment->durationMinutes() ?: 180))))));
            if ($examWindowPassed) {
                $missed->push($base + [
                    'attempt' => null,
                    'can_retake' => $this->canRetake($user, $assessment, $assignment),
                    'attempt_number' => 1,
                    'attempt_total' => $max,
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

        foreach ($attemptsByAssessment as $aid => $atts) {
            $sample = $items->first(fn ($r) => (int) $r['assessment']->id === (int) $aid);
            $assessment = $sample['assessment'] ?? $atts->first()?->assessment;
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
                $matchRow = $items->first(fn ($r) => $this->attemptBelongsToAssignment($att, $r['assignment'] ?? null));
                $asg = $matchRow['assignment'] ?? $sample['assignment'];
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

                return (int) $r['assessment']->id . ':' . $from;
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
        $missed = $missed->groupBy(fn ($r) => (int) $r['assessment']->id)->map(function ($group) {
            return $group->sortBy(fn ($r) => (int) ($r['assignment']->id ?? 0))->first();
        })->values();

        return compact('available', 'upcoming', 'inProgress', 'submitted', 'results', 'missed');
    }
}
