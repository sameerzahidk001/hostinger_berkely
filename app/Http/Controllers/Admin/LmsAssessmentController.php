<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAssignment;
use App\Models\LmsAssessmentAttempt;
use App\Models\LmsAttemptAnswer;
use App\Models\LmsQuestion;
use App\Models\User;
use App\Services\LmsAssessmentService;
use App\Services\StudyMaterialService;
use App\Services\ZohoLmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LmsAssessmentController extends Controller
{
    public function __construct(
        protected LmsAssessmentService $assessments,
        protected StudyMaterialService $lms,
        protected ZohoLmsService $zoho
    ) {
    }

    /** @var array<int, \Illuminate\Support\Collection<int, int>> */
    protected array $excludedTakersByAssessment = [];

    protected string $scsRosterNote = '';

    /** @var array<int, \App\Models\User|false> */
    protected array $namedAssessorByAssessment = [];

    /** @var array<int>|null */
    protected $instructorScopeBatchIds = null;

    /** @var array<int>|null */
    protected $instructorScopeAssessmentIds = null;

    /** @var array<int, array<int>> */
    protected array $courseIdsWhereUserIsAssessorCache = [];

    /** @var array<int, bool> */
    protected array $assignmentHasSubmittedAttemptCache = [];

    /** @var array<int, \Carbon\Carbon|null> */
    protected array $mock1SittingStartCache = [];

    public function index(Request $request)
    {
        // Create Test page: create permission (or skill admin). Assign-only users use Assigned Tests.
        abort_unless($this->assessments->canCreateStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();

        $kind = trim((string) $request->get('kind', ''));
        $search = trim((string) $request->get('search', ''));
        $query = LmsAssessment::query()->withCount(['questions', 'attempts'])->orderByDesc('id');

        if ($kind !== '' && array_key_exists($kind, LmsAssessment::kinds())) {
            $query->where('kind', $kind);
        }
        if (! $this->assessments->canManageSkillTests()) {
            $query->where('kind', '!=', LmsAssessment::KIND_SKILL)
                ->where('audience', '!=', LmsAssessment::AUDIENCE_STAFF);
        }
        if ($this->shouldScopeTestsToOwnAssignments()) {
            $uid = (int) (Auth::id() ?? 0);
            if ($uid <= 0) {
                $query->whereRaw('1 = 0');
            } else {
                $this->applyInstructorAssessmentScope($query, $uid);
            }
        }
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($search, $like) {
                $q->where('title', 'like', $like);
                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                }
            });
        }

        $all = $query->with([
            'course',
            'assignments.marker',
        ])->get();
        $all = $this->uniqueAssessmentsByTitle($all, $all->flatMap(fn ($row) => $row->assignments ?? collect()));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 20;
        $total = $all->count();
        $lastPage = max(1, (int) ceil(($total ?: 1) / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $rows = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => collect($request->query())->except('page')->all(),
            ]
        );

        return view('admin.assessments.tests.index', [
            'rows' => $rows,
            'kinds' => LmsAssessment::kinds(),
            'kind' => $kind,
            'search' => $search,
            'canSkill' => $this->assessments->canManageSkillTests(),
            'canCreate' => $this->assessments->canCreateStudentAssessments() || $this->assessments->canManageSkillTests(),
            'canAssign' => $this->assessments->canAssignStudentAssessments() || $this->assessments->canManageSkillTests(),
            // Instructors must not delete tests — Admin (or full panel) only.
            'canDelete' => $this->lms->isAdminActor() || panel_sees_all_dashboards(),
        ]);
    }

    public function create(Request $request)
    {
        $kind = $request->get('kind', LmsAssessment::KIND_PRACTICE);
        if ($kind === LmsAssessment::KIND_SKILL) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canCreateStudentAssessments(), 403);
        }
        $this->assessments->ensure();

        return view('admin.assessments.tests.form', $this->formData(new LmsAssessment([
            'kind' => $kind,
            'audience' => $kind === LmsAssessment::KIND_SKILL ? LmsAssessment::AUDIENCE_STAFF : LmsAssessment::AUDIENCE_STUDENT,
            'status' => 'draft',
            'results_mode' => 'after_publish',
            'pass_percent' => null,
        ])));
    }

    public function store(Request $request)
    {
        if ($request->input('kind') === LmsAssessment::KIND_SKILL) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canCreateStudentAssessments(), 403);
        }
        $this->assessments->ensure();
        $validator = Validator::make($request->all(), $this->rules($request));
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $a = new LmsAssessment();
        $this->fill($a, $request, true);
        $a->save();
        $this->syncQuestions($a, $request);
        $uploadError = $this->syncResourceFiles($a, $request);
        $redirect = redirect()->route('admin.assessments.tests.edit', $a->id)
            ->with('success', 'Test created. Use Assign to give it to users or batches.');
        if ($uploadError) {
            $redirect->with('fail', $uploadError);
        }

        return $redirect;
    }

    public function edit($id)
    {
        $this->assessments->ensure();
        $assessment = LmsAssessment::with(['questions', 'assignments.batch', 'assignments.student'])->findOrFail($id);
        if ($assessment->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canManageStudentAssessments(), 403);
            $this->assertInstructorOwnsCourse((int) $assessment->course_id);
        }

        return view('admin.assessments.tests.form', $this->formData($assessment));
    }

    public function update(Request $request, $id)
    {
        $this->assessments->ensure();
        $a = LmsAssessment::findOrFail($id);
        if ($a->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canManageStudentAssessments(), 403);
            $this->assertInstructorOwnsCourse((int) $a->course_id);
        }
        $canCreate = $a->isSkill()
            ? $this->assessments->canManageSkillTests()
            : $this->assessments->canCreateStudentAssessments();
        $canAssign = $a->isSkill()
            ? $this->assessments->canManageSkillTests()
            : $this->assessments->canAssignStudentAssessments();

        $validator = Validator::make($request->all(), $this->rules($request));
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        if ($canCreate) {
            $this->fill($a, $request, false);
            $a->save();
            $this->syncQuestions($a, $request);
            $uploadError = $this->syncResourceFiles($a, $request);
            $redirect = redirect()->route('admin.assessments.tests.edit', $a->id)->with('success', 'Test updated.');
            if ($uploadError) {
                $redirect->with('fail', $uploadError);
            }

            return $redirect;
        }

        return redirect()->route('admin.assessments.tests.edit', $a->id)->with('success', 'Test updated.');
    }

    public function destroy($id)
    {
        // Instructors must not delete — Admin (or full panel) only.
        abort_unless($this->lms->isAdminActor() || panel_sees_all_dashboards(), 403);
        $this->assessments->ensure();
        $a = LmsAssessment::findOrFail($id);
        if ($a->hasAssignedSittings()) {
            return redirect()->route('admin.assessments.tests.index')
                ->with('error', 'This test has assigned sittings. Disable it instead of deleting.');
        }
        try {
            LmsAssessmentAssignment::query()->where('assessment_id', (int) $a->id)->delete();
        } catch (\Throwable $e) {
            // test row still goes
        }
        $a->delete();

        return redirect()->route('admin.assessments.tests.index')->with('success', 'Test removed.');
    }

    public function disable($id)
    {
        abort_unless($this->lms->isAdminActor() || panel_sees_all_dashboards(), 403);
        $this->assessments->ensure();
        $a = LmsAssessment::findOrFail($id);
        if (Schema::hasColumn($a->getTable(), 'status')) {
            $a->status = 'disabled';
            $a->save();
        }

        return redirect()->route('admin.assessments.tests.index')
            ->with('success', 'Test disabled. Existing sittings stay, but it cannot be assigned until it is published again.');
    }

    public function assignForm($id)
    {
        $this->assessments->ensure();
        $assessment = LmsAssessment::with(['assignments.batch', 'assignments.student', 'assignments.marker', 'course'])->findOrFail($id);
        abort_unless($this->assessments->canManageSittingsFor($assessment), 403);
        if ($assessment->isDisabled()) {
            return redirect()->route('admin.assessments.tests.index')
                ->with('error', 'Disabled tests cannot be assigned.');
        }
        if (! $assessment->isSkill()) {
            $this->assertInstructorOwnsCourse((int) $assessment->course_id);
        }

        // Assign = add new people only — never preload existing assignees.
        $assignMode = old('sitting_kind', request('mode')) === 'self' ? 'self' : 'exam';

        return view('admin.assessments.tests.assign', array_merge($this->formData($assessment), [
            'selectedBatchIds' => old('batch_ids', []),
            'selectedStudentIds' => old('student_ids', []),
            'selectedMarkerId' => old('marker_user_id'),
            'selectedTimezone' => old('timezone', config('app.timezone', 'Asia/Dubai')),
            'assignFresh' => true,
            'assignMode' => $assignMode,
        ]));
    }

    public function saveAssign(Request $request, $id)
    {
        $this->assessments->ensure();
        $a = LmsAssessment::findOrFail($id);
        abort_unless($this->assessments->canManageSittingsFor($a), 403);
        if ($a->isDisabled()) {
            return redirect()->route('admin.assessments.tests.index')
                ->with('error', 'Disabled tests cannot be assigned.');
        }
        if (! $a->isSkill()) {
            $this->assertInstructorOwnsCourse((int) $a->course_id);
        }

        $isSelf = $request->input('sitting_kind') === 'self';
        $validator = Validator::make($request->all(), [
            'batch_ids' => 'nullable|array',
            'batch_ids.*' => 'exists:class_batches,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
            'marker_user_id' => 'nullable|exists:users,id',
            'sitting_kind' => 'nullable|in:exam,self',
            'available_from' => $isSelf ? 'nullable|date' : 'required|date',
            'available_until' => 'nullable|date',
            'timezone' => 'required|string|max:64',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $batchCount = count(array_filter((array) $request->input('batch_ids', [])));
        $studentCount = count(array_filter((array) $request->input('student_ids', [])));
        if ($batchCount === 0 && $studentCount === 0) {
            return back()->withErrors(['student_ids' => 'Select at least one batch or user to assign.'])->withInput();
        }

        $this->syncAssignments($a, $request);
        $created = (int) $request->attributes->get('lms_assignments_created', $batchCount + $studentCount);
        $kindNote = $isSelf
            ? ' Self test: no appointment — the student starts anytime, up to Max attempts on the test.'
            : ' Exam sitting: student may start anytime inside the appointment window.';

        if ($created === 0) {
            return redirect()->route('admin.assessments.tests.assigned-users', ['assessment_id' => $a->id])
                ->with('success', 'Those people already have this unused sitting. No extra assignment was created.');
        }

        return redirect()->route('admin.assessments.tests.assigned-users', ['assessment_id' => $a->id])
            ->with('success', $created . ' new sitting' . ($created === 1 ? '' : 's') . ' created for test “' . $a->title . '”.' . $kindNote);
    }

    /** Read-only profile JSON for Assigned Tests / Results popups (no user-edit access required). */
    public function userPreview($userId)
    {
        abort_unless(
            $this->assessments->canManageStudentAssessments()
            || $this->assessments->canManageSkillTests()
            || $this->assessments->canAssignStudentAssessments(),
            403
        );
        $user = User::query()->with('roles:id,name')->findOrFail($userId);

        return response()->json($this->assessments->userPreviewPayload($user));
    }

    public function editAssignment($assignmentId)
    {
        $this->assessments->ensure();
        $assignment = LmsAssessmentAssignment::with(['assessment', 'student', 'batch', 'marker'])->findOrFail($assignmentId);
        $assessment = $assignment->assessment;
        abort_unless($assessment, 404);
        if ($deny = $this->denyNonAdminSittingChange()) {
            return $deny;
        }
        if (! $assessment->isSkill()) {
            $this->assertInstructorOwnsCourse((int) $assessment->course_id);
        }

        return view('admin.assessments.tests.edit-assignment', array_merge($this->formData($assessment), [
            'assignment' => $assignment,
            'selectedMarkerId' => old('marker_user_id', $assignment->marker_user_id),
            'selectedTimezone' => old('timezone', $assignment->timezone ?: config('app.timezone', 'Asia/Dubai')),
            'assignMode' => $assignment->isSelfSitting() ? 'self' : 'exam',
        ]));
    }

    public function updateAssignment(Request $request, $assignmentId)
    {
        $this->assessments->ensure();
        $assignment = LmsAssessmentAssignment::with('assessment')->findOrFail($assignmentId);
        $a = $assignment->assessment;
        abort_unless($a, 404);
        if ($deny = $this->denyNonAdminSittingChange()) {
            return $deny;
        }
        if (! $a->isSkill()) {
            $this->assertInstructorOwnsCourse((int) $a->course_id);
        }

        $isSelf = $assignment->isSelfSitting() || $request->input('sitting_kind') === 'self';
        $validator = Validator::make($request->all(), [
            'marker_user_id' => 'nullable|exists:users,id',
            'available_from' => $isSelf ? 'nullable|date' : 'required|date',
            'timezone' => 'required|string|max:64',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $tzOptions = LmsAssessmentAssignment::timezoneOptions();
        $timezone = trim((string) $request->input('timezone', ''));
        if ($timezone === '' || ! array_key_exists($timezone, $tzOptions)) {
            $timezone = (string) config('app.timezone', 'Asia/Dubai');
        }

        if ($isSelf) {
            $assignment->available_from = null;
            $assignment->available_until = null;
        } else {
            $fromLocal = \Carbon\Carbon::parse($request->input('available_from'), $timezone);
            $assignment->available_from = $fromLocal->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
            $a->loadMissing('questions');
            if ($a->durationMinutes()) {
                $assignment->available_until = $fromLocal->copy()
                    ->addMinutes((int) $a->durationMinutes())
                    ->timezone(config('app.timezone'))
                    ->format('Y-m-d H:i:s');
            }
        }
        if (Schema::hasColumn('lms_assessment_assignments', 'timezone')) {
            $assignment->timezone = $timezone;
        }
        if (Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')) {
            $assignment->marker_user_id = $request->filled('marker_user_id')
                ? (int) $request->input('marker_user_id')
                : null;
        }
        $assignment->save();

        return redirect()
            ->route('admin.assessments.tests.assigned-users', ['assessment_id' => $a->id])
            ->with('success', 'Sitting ' . $assignment->displayCode() . ' updated. Appointment and assessor are the values you saved.');
    }

    public function destroyAssignment(Request $request, $assignmentId)
    {
        $this->assessments->ensure();
        if ($deny = $this->denyNonAdminSittingChange()) {
            return $deny;
        }
        $assignedUrl = route('admin.assessments.tests.assigned-users');
        $assignment = LmsAssessmentAssignment::with('assessment')->find($assignmentId);
        $attemptId = (int) $request->input('attempt_id', 0);
        $studentId = (int) $request->input('student_id', 0);
        if (! $assignment) {
            if ($attemptId > 0) {
                return $this->destroyAttemptRow($attemptId);
            }

            return redirect($assignedUrl)->with('error', 'That sitting was already removed.');
        }

        $attempt = null;
        if ($attemptId > 0) {
            $attempt = LmsAssessmentAttempt::query()
                ->where('id', $attemptId)
                ->where('assessment_id', $assignment->assessment_id)
                ->first();
            if ($attempt && $studentId <= 0) {
                $studentId = (int) $attempt->user_id;
            }
        }

        if ($studentId <= 0) {
            $studentId = (int) ($assignment->student_id ?? 0);
        }

        $backUrl = route('admin.assessments.tests.assigned-users', array_filter([
            'assessment_id' => (int) ($assignment->assessment_id ?? 0) ?: null,
        ]));
        if ($studentId <= 0 && (! empty($assignment->class_batch_id) || empty($assignment->student_id))) {
            $linked = collect($assignment->roster_user_ids ?? [])->map(fn ($id) => (int) $id)->filter()->values();
            if (! empty($assignment->student_id)) {
                $linked->push((int) $assignment->student_id);
            }
            $code = method_exists($assignment, 'displayCode') ? $assignment->displayCode() : ('BEXT' . (int) $assignment->id);
            $assignment->delete();

            return redirect($backUrl)->with('success', 'Removed empty sitting ' . $code . '.');
        }

        $code = $this->assessments->forgetStudentSitting($assignment, $attempt, $studentId);
        $this->excludedTakersByAssessment = [];

        return redirect($backUrl)->with('success', 'Removed ' . $code . '. It is no longer on student or instructor lists.');
    }

    public function destroyAttemptRow($attemptId)
    {
        $this->assessments->ensure();
        if ($deny = $this->denyNonAdminSittingChange()) {
            return $deny;
        }
        $assignedUrl = route('admin.assessments.tests.assigned-users');
        $attempt = LmsAssessmentAttempt::query()->find($attemptId);
        if (! $attempt) {
            return redirect($assignedUrl)->with('error', 'That sitting was already removed.');
        }
        $aid = (int) ($attempt->assessment_id ?? 0);
        $assignment = ! empty($attempt->assignment_id)
            ? LmsAssessmentAssignment::query()->find((int) $attempt->assignment_id)
            : null;
        $this->assessments->forgetStudentSitting($assignment, $attempt, (int) ($attempt->user_id ?? 0));
        $this->excludedTakersByAssessment = [];

        return redirect()->route('admin.assessments.tests.assigned-users', array_filter([
            'assessment_id' => $aid ?: null,
        ]))->with('success', 'Removed that sitting. It is no longer on student or instructor lists.');
    }

    /** Keep the batch sitting; never put this student back on this test. */
    protected function excludeStudentFromAssessment(int $assessmentId, int $userId): void
    {
        if ($assessmentId <= 0 || $userId <= 0) {
            return;
        }
        if (! Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
            return;
        }
        $assessment = LmsAssessment::withTrashed()->find($assessmentId);
        $aids = $assessment ? $this->sameTitleAssessmentIds($assessment) : [$assessmentId];
        $rows = LmsAssessmentAssignment::query()->whereIn('assessment_id', $aids)->get();
        foreach ($rows as $row) {
            $ex = collect($row->excluded_user_ids ?? [])->map(fn ($id) => (int) $id)->push($userId)->unique()->filter()->values()->all();
            $row->excluded_user_ids = $ex;
            if (Schema::hasColumn($row->getTable(), 'roster_user_ids')) {
                $roster = collect($row->roster_user_ids ?? [])->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === $userId)->values()->all();
                $row->roster_user_ids = $roster;
            }
            $row->save();
        }
        $this->excludedTakersByAssessment = [];
    }

    /**
     * One list for all assigned test takers (started or not) with status / score / mark actions.
     * Replaces the separate Test Results menu.
     */
    public function assignedUsers(Request $request)
    {
        abort_unless($this->assessments->actorCanViewAssignedTests(), 403);
        $this->assessments->ensure();

        $assessmentId = (int) $request->get('assessment_id', 0);
        $batchId = (int) $request->get('batch_id', 0);
        $courseId = (int) $request->get('course_id', 0);
        $kind = trim((string) $request->get('kind', ''));
        $statusFilter = trim((string) $request->get('status', ''));
        $search = trim((string) $request->get('search', ''));
        $resultsView = $request->query('view') === 'results';

        $selectedAssessment = $assessmentId > 0
            ? LmsAssessment::query()->find($assessmentId)
            : null;

        if ($selectedAssessment && $selectedAssessment->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        }

        $courseIdsScope = null;
        $markerOnlyUid = null;
        if ($this->shouldScopeTestsToOwnAssignments()) {
            $courseIdsScope = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $markerOnlyUid = (int) (Auth::id() ?: Auth::guard('web')->id() ?: 0);
        }

        $assignments = collect();
        try {
            $assignments = LmsAssessmentAssignment::query()
                ->with(['batch', 'student', 'marker'])
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            report($e);
            try {
                $raw = Schema::hasTable('lms_assessment_assignments')
                    ? DB::table('lms_assessment_assignments')->orderByDesc('id')->get()
                    : collect();
                $assignments = LmsAssessmentAssignment::hydrate($raw->map(fn ($row) => (array) $row)->all());
            } catch (\Throwable $e2) {
                $assignments = collect();
            }
        }
        $assignments = $assignments->filter(function ($assignment) {
            if (! method_exists($this->assessments, 'sittingHasNoPeople') || ! $this->assessments->sittingHasNoPeople($assignment)) {
                return true;
            }
            try {
                $assignment->delete();
            } catch (\Throwable $e) {
                return false;
            }

            return false;
        })->values();

        $assessmentIds = $assignments->pluck('assessment_id')->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
        if ($assessmentId > 0) {
            $assessmentIds[] = $assessmentId;
            $assessmentIds = array_values(array_unique($assessmentIds));
        }
        try {
            $assessmentsById = $assessmentIds === []
                ? collect()
                : LmsAssessment::withTrashed()->with('course')->whereIn('id', $assessmentIds)->get()->keyBy(fn ($row) => (int) $row->id);
        } catch (\Throwable $e) {
            report($e);
            $assessmentsById = $assessmentIds === []
                ? collect()
                : LmsAssessment::withTrashed()->whereIn('id', $assessmentIds)->get()->keyBy(fn ($row) => (int) $row->id);
        }
        foreach ($assignments as $assignment) {
            $aid = (int) $assignment->assessment_id;
            $found = $assessmentsById->get($aid) ?: LmsAssessment::withTrashed()->find($aid);
            if ($found) {
                $assessmentsById->put($aid, $found);
                $assignment->setRelation('assessment', $found);
            }
        }

        $assessmentFilterIds = $assessmentId > 0 ? [$assessmentId] : [];
        if ($assessmentId > 0 && $selectedAssessment) {
            $assessmentFilterIds = $this->sameTitleAssessmentIds($selectedAssessment);
        }

        $loadedAssignments = $assignments;
        $assignments = $assignments->filter(function ($assignment) use (
            $assessmentId, $assessmentFilterIds, $batchId, $courseId, $kind, $markerOnlyUid, $courseIdsScope
        ) {
            $assessment = $assignment->assessment ?? null;
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                return false;
            }
            if ($markerOnlyUid && ! $this->instructorOwnsSitting($markerOnlyUid, $assignment, $assessment)) {
                return false;
            }
            if ($assessmentId > 0 && ! in_array((int) $assignment->assessment_id, $assessmentFilterIds, true)) {
                return false;
            }
            if ($batchId > 0 && (int) ($assignment->class_batch_id ?? 0) !== $batchId) {
                return false;
            }
            if ($courseId > 0 && ! $this->sittingMatchesCourseFilter($assignment, $assessment, $courseId, $markerOnlyUid)) {
                return false;
            }
            if ($kind !== '' && $assessment && $assessment->kind !== $kind) {
                return false;
            }
            if ($markerOnlyUid) {
                if (! $this->instructorCanSeeSitting($markerOnlyUid, $assignment, $assessment)) {
                    return false;
                }
            } elseif ($courseIdsScope !== null) {
                $inCourse = in_array((int) ($assessment->course_id ?? 0), $courseIdsScope ?: [0], true);
                $skillOk = $this->assessments->canManageSkillTests()
                    && $assessment
                    && ($assessment->isSkill() || $assessment->audience === LmsAssessment::AUDIENCE_STAFF);
                if (! $inCourse && ! $skillOk) {
                    return false;
                }
            } elseif (! $this->assessments->canManageSkillTests() && $assessment && ($assessment->isSkill() || $assessment->audience === LmsAssessment::AUDIENCE_STAFF)) {
                return false;
            }

            return true;
        })->values();

        $rowsByKey = [];
        $batchStudentCache = [];

        foreach ($assignments as $assignment) {
            $assessment = $assignment->relationLoaded('assessment')
                ? $assignment->assessment
                : $assessmentsById->get((int) $assignment->assessment_id);
            if (! $assessment) {
                continue;
            }
            if ($this->looksLikeCimaScsNovember($assessment) && ! $this->isCimaScsAssignedMock($assessment)) {
                continue;
            }
            if (method_exists($assessment, 'trashed') && $assessment->trashed()) {
                continue;
            }

            $students = collect();
            if ($assignment->class_batch_id) {
                $bid = (int) $assignment->class_batch_id;
                if ($batchId > 0 && $bid !== $batchId) {
                    continue;
                }
            }
            if ($assignment->student_id) {
                $student = $assignment->student
                    ?: \App\Models\User::query()->find($assignment->student_id);
                if ($student) {
                    $students->push($student);
                }
            }
            try {
                if (! empty($assignment->id) && Schema::hasTable('lms_assessment_attempts')) {
                    $attemptUids = LmsAssessmentAttempt::query()
                        ->where('assignment_id', (int) $assignment->id)
                        ->pluck('user_id')
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->values();
                    if ($attemptUids->isNotEmpty()) {
                        $students = $students->concat(User::query()->whereIn('id', $attemptUids->all())->get());
                    }
                }
            } catch (\Throwable $e) {
                // keep named student
            }
            $students = $this->expandSittingStudents($students, $assignment, $assessment, $batchStudentCache);
            if ($students->isEmpty()) {
                continue;
            }

            if ($batchId > 0 && $assignment->student_id) {
                $inBatch = ClassBatch::find($batchId)?->students()->where('users.id', $assignment->student_id)->exists();
                if (! $inBatch) {
                    continue;
                }
            }

            foreach ($students as $student) {
                if (! $this->studentOnSitting($student, $assignment, $assessment)) {
                    continue;
                }
                // One row per assignment sitting (same test can appear many times).
                $key = (int) $assignment->id . ':' . (int) $student->id;
                if (isset($rowsByKey[$key])) {
                    continue;
                }
                $rowsByKey[$key] = [
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $assignment,
                    'attempt' => null,
                ];
            }
        }

        try {
            $attemptQuery = LmsAssessmentAttempt::query()->orderByDesc('id')->limit(3000);
            if ($assessmentFilterIds !== []) {
                $attemptQuery->whereIn('assessment_id', $assessmentFilterIds);
            }
            $allAttempts = $attemptQuery->get();
        } catch (\Throwable $e) {
            report($e);
            $allAttempts = collect();
        }
        $markerIds = $assignments->pluck('marker_user_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $creatorIds = $assignments->pluck('assigned_by_user_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $attemptUserIds = $allAttempts->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $peopleIds = $markerIds->merge($creatorIds)->merge($attemptUserIds)->unique()->values();
        $peopleById = $peopleIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $peopleIds)->get()->keyBy('id');
        foreach ($assignments as $assignment) {
            $marker = $peopleById->get((int) ($assignment->marker_user_id ?? 0));
            if ($marker) {
                $assignment->setRelation('marker', $marker);
            }
            $creator = $peopleById->get((int) ($assignment->assigned_by_user_id ?? 0));
            if ($creator) {
                $assignment->setRelation('assignedByUser', $creator);
            }
        }
        $assignmentIdsFromAttempts = $allAttempts->pluck('assignment_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $missingAsgIds = $assignmentIdsFromAttempts->diff($assignments->pluck('id')->map(fn ($id) => (int) $id))->filter();
        if ($missingAsgIds->isNotEmpty()) {
            foreach (LmsAssessmentAssignment::query()->whereIn('id', $missingAsgIds->all())->get() as $extraAsg) {
                $assignments->push($extraAsg);
                $found = $assessmentsById->get((int) $extraAsg->assessment_id);
                if ($found) {
                    $extraAsg->setRelation('assessment', $found);
                }
            }
        }
        $assignmentsById = $assignments->keyBy(fn ($row) => (int) $row->id);
        $attemptAssessmentIds = $allAttempts->pluck('assessment_id')->map(fn ($id) => (int) $id)->unique()->filter()->values();
        $missingAssessmentIds = $attemptAssessmentIds->diff($assessmentsById->keys()->map(fn ($id) => (int) $id))->filter();
        if ($missingAssessmentIds->isNotEmpty()) {
            foreach (LmsAssessment::withTrashed()->whereIn('id', $missingAssessmentIds->all())->get() as $extraAssessment) {
                $assessmentsById->put((int) $extraAssessment->id, $extraAssessment);
            }
        }
        foreach ($allAttempts as $att) {
            $found = $assessmentsById->get((int) $att->assessment_id);
            if ($found) {
                $att->setRelation('assessment', $found);
            }
        }

        $claimedAttemptIds = [];
        try {
            $attemptsByPair = $allAttempts->groupBy(fn ($a) => (int) $a->assessment_id . ':' . (int) $a->user_id);
            $grouped = collect($rowsByKey)->groupBy(
                fn ($r) => (int) ($r['assessment']->id ?? 0) . ':' . (int) ($r['student']->id ?? 0)
            );
            foreach ($grouped as $pairKey => $sittings) {
                $candidates = ($attemptsByPair->get($pairKey) ?? collect())->sortBy('id')->values();
                foreach ($sittings->sortBy(fn ($r) => (int) ($r['assignment']->id ?? 0)) as $sitting) {
                    $asg = $sitting['assignment'] ?? null;
                    if (! $asg) {
                        continue;
                    }
                    $rowKey = (int) $asg->id . ':' . (int) ($sitting['student']->id ?? 0);
                    $matched = $this->matchSittingAttempt($candidates, $asg, $claimedAttemptIds);
                    $rowsByKey[$rowKey]['attempt'] = $matched;
                    foreach ($candidates as $extra) {
                        if (isset($claimedAttemptIds[(int) $extra->id])) {
                            continue;
                        }
                        if (! $this->assessments->attemptBelongsToAssignment($extra, $asg)) {
                            continue;
                        }
                        $claimedAttemptIds[(int) $extra->id] = true;
                        $rowsByKey['attempt:' . $extra->id] = [
                            'assessment' => $sitting['assessment'],
                            'student' => $sitting['student'],
                            'assignment' => $asg,
                            'attempt' => $extra,
                        ];
                    }
                }
            }

        } catch (\Throwable $e) {
            report($e);
        }

        foreach ($allAttempts as $att) {
            if (isset($claimedAttemptIds[(int) ($att->id ?? 0)])) {
                continue;
            }
            $claimedAttemptIds[(int) $att->id] = true;
            $student = $att->user
                ?: $peopleById->get((int) $att->user_id)
                ?: User::query()->find($att->user_id)
                ?: (object) [
                    'id' => (int) $att->user_id,
                    'name' => 'User #' . $att->user_id,
                    'email' => '',
                ];
            $assessment = $att->assessment
                ?: $assessmentsById->get((int) $att->assessment_id)
                ?: LmsAssessment::query()->find($att->assessment_id);
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                continue;
            }
            if ($this->looksLikeCimaScsNovember($assessment) && ! $this->isCimaScsAssignedMock($assessment)) {
                continue;
            }
            if (method_exists($assessment, 'trashed') && $assessment->trashed()) {
                continue;
            }
            if ($this->isRemovedTestTaker($student, $assessment)) {
                continue;
            }
            $asg = $this->assignmentForAttempt($att, $assignments, $assignmentsById);
            if ($markerOnlyUid) {
                if ($asg && ! $this->instructorOwnsSitting($markerOnlyUid, $asg, $assessment)) {
                    continue;
                }
                if (! $this->instructorCanSeeRow($markerOnlyUid, $asg, $assessment, $att)) {
                    continue;
                }
            }
            if ($asg && $this->assessments->attemptBelongsToAssignment($att, $asg)) {
                $sitKey = (int) $asg->id . ':' . (int) $student->id;
                if (isset($rowsByKey[$sitKey]) && empty($rowsByKey[$sitKey]['attempt'])) {
                    $rowsByKey[$sitKey]['attempt'] = $att;
                    continue;
                }
            }
            $rowsByKey['attempt:' . $att->id] = [
                'assessment' => $assessment,
                'student' => $student,
                'assignment' => $asg,
                'attempt' => $att,
            ];
        }

        try {
            $this->fillMissingBatchMates($rowsByKey, $assignments, $assessmentsById, $batchStudentCache);
            $this->attachCimaCohortSittingsToAssignedRows($rowsByKey, $assignments, $assessmentsById);
            $this->ensureMock1OriginalRoster($rowsByKey, $assignments, $assessmentsById);
            $rowsByKey = $this->keepOriginalUnusedClassSitting($rowsByKey);
        } catch (\Throwable $e) {
            report($e);
        }
        $rowsByKey = $this->dropGhostAssignmentRows($rowsByKey);

        $claimedAttemptIds = [];
        foreach ($rowsByKey as $r) {
            if (! empty($r['attempt']->id)) {
                $claimedAttemptIds[(int) $r['attempt']->id] = true;
            }
        }
        foreach ($allAttempts as $att) {
            if (isset($claimedAttemptIds[(int) $att->id])) {
                continue;
            }
            foreach ($rowsByKey as $k => $r) {
                if (! empty($r['attempt'])) {
                    continue;
                }
                if ((int) ($r['student']->id ?? 0) !== (int) $att->user_id) {
                    continue;
                }
                if ((int) ($r['assessment']->id ?? 0) !== (int) $att->assessment_id) {
                    continue;
                }
                if (! $this->assessments->attemptBelongsToAssignment($att, $r['assignment'] ?? null)) {
                    continue;
                }
                $rowsByKey[$k]['attempt'] = $att;
                $claimedAttemptIds[(int) $att->id] = true;
                break;
            }
        }

        $this->splitAttemptsThatDoNotBelongToSitting($rowsByKey);
        $rowsByKey = $this->attachTakenAttemptsToExamRows($rowsByKey, $allAttempts, $assignments);

        // Attempt N of T per student+assessment (oldest sitting = 1).
        $sittingsByPair = collect($rowsByKey)->groupBy(
            fn ($r) => (int) ($r['assessment']->id ?? 0) . ':' . (int) ($r['student']->id ?? 0)
        );
        foreach ($sittingsByPair as $pairRows) {
            $ordered = $pairRows->sortBy(fn ($r) => (int) ($r['assignment']->id ?? 0))->values();
            $total = $ordered->count();
            foreach ($ordered as $i => $r) {
                $k = (int) ($r['assignment']->id ?? 0) . ':' . (int) ($r['student']->id ?? 0);
                if (isset($rowsByKey[$k])) {
                    $asg = $r['assignment'] ?? null;
                    $max = max(1, (int) ($r['assessment']->max_attempts ?? 1), $total);
                    $rowsByKey[$k]['attempt_number'] = min($max, max(1, $i + 1));
                    $rowsByKey[$k]['attempt_total'] = $max;
                }
            }
        }
        foreach ($rowsByKey as $k => $r) {
            if (! isset($r['attempt_number'])) {
                $max = max(1, (int) ($r['assessment']->max_attempts ?? 1));
                $rowsByKey[$k]['attempt_number'] = 1;
                $rowsByKey[$k]['attempt_total'] = $max;
            }
            $asg = $r['assignment'] ?? null;
            $att = $r['attempt'] ?? null;
            $assessment = $r['assessment'] ?? $asg?->assessment;
            if (! $asg && $this->isCimaCohortSitting($assessment, $asg, collect([$r['student'] ?? null])) && $assessment) {
                $asg = $this->ensureCimaCohortAssignment($assessment);
            }
            if (! $asg && $att) {
                $asg = $this->assignmentForAttempt($att, $assignments, $assignmentsById);
            }
            if ($asg) {
                $this->hydrateSittingDisplay($asg, $assessment);
                $marker = $peopleById->get((int) ($asg->marker_user_id ?? 0)) ?: ($asg->marker ?? null);
                if ($marker) {
                    $asg->setRelation('marker', $marker);
                }
                $creator = $peopleById->get((int) ($asg->assigned_by_user_id ?? 0));
                if ($creator) {
                    $asg->setRelation('assignedByUser', $creator);
                }
                $rowsByKey[$k]['assignment'] = $asg;
                $r['assignment'] = $asg;
            }
            $assessment = $r['assessment'] ?? $asg?->assessment;
            if ($assessment && ! $assessment->relationLoaded('course') && ! empty($assessment->course_id)) {
                $assessment->loadMissing('course');
            }
            if ($assessment) {
                $rowsByKey[$k]['assessment'] = $assessment;
            }
            if (! $assessment) {
                unset($rowsByKey[$k]);
            } elseif (method_exists($assessment, 'trashed') && $assessment->trashed() && empty($r['assignment'])) {
                unset($rowsByKey[$k]);
            }
        }
        $rowsByKey = collect($rowsByKey)->filter(function ($r) {
            $assessment = $r['assessment'] ?? null;
            if (! $assessment) {
                return false;
            }
            if (method_exists($assessment, 'trashed') && $assessment->trashed() && empty($r['assignment'])) {
                return false;
            }
            if ($this->isRemovedTestTaker($r['student'] ?? null, $r['assessment'] ?? null)) {
                return false;
            }

            return true;
        })->all();

        if (! $markerOnlyUid) {
            $rowsByKey = $this->collapseUnusedRetakeRows($rowsByKey);
        }
        $rowsByKey = $this->dedupeAssignedSittingRows($rowsByKey);
        foreach ($rowsByKey as $k => $r) {
            $max = max(1, (int) ($r['assessment']->max_attempts ?? 1));
            $rowsByKey[$k]['attempt_number'] = min($max, max(1, (int) ($r['attempt_number'] ?? 1)));
            $rowsByKey[$k]['attempt_total'] = $max;
        }

        foreach ($rowsByKey as $k => $r) {
            $rowsByKey[$k] = $this->applySittingDisplayToRow($r);
        }

        if ($markerOnlyUid) {
            $rowsByKey = collect($rowsByKey)->filter(function ($r) use ($markerOnlyUid) {
                return $this->instructorCanSeeRow(
                    $markerOnlyUid,
                    $r['assignment'] ?? null,
                    $r['assessment'] ?? null,
                    $r['attempt'] ?? null
                );
            })->all();
        }

        $rows = collect($rowsByKey)->values();
        $rows = $rows->sortBy(function ($row) {
            $status = (string) (($row['sitting_display']['status'] ?? ''));
            $rank = match ($status) {
                'In progress' => 0,
                'Marking Required' => 1,
                'Completed and Marked', 'Completed' => 2,
                'Upcoming' => 3,
                'Missed' => 4,
                'Assigned', 'Available' => 5,
                default => 6,
            };
            $title = mb_strtolower((string) ($row['assessment']->title ?? ''));
            $name = mb_strtolower((string) ($row['student']->name ?? ''));

            return sprintf('%d-%s-%s', $rank, $title, $name);
        })->values();

        if ($rows->isEmpty() && $assignments->isNotEmpty()) {
            foreach ($assignments as $assignment) {
                $assessment = $assignment->assessment;
                if (! $assessment) {
                    continue;
                }
                $student = $assignment->student;
                if (! $student && $assignment->student_id) {
                    $student = User::query()->find($assignment->student_id);
                }
                if (! $student && $assignment->class_batch_id) {
                    $cache = [];
                    foreach ($this->expandSittingStudents(collect(), $assignment, $assessment, $cache) as $batchStudent) {
                        $rows->push([
                            'assessment' => $assessment,
                            'student' => $batchStudent,
                            'assignment' => $assignment,
                            'attempt' => null,
                            'attempt_number' => 1,
                            'attempt_total' => max(1, (int) ($assessment->max_attempts ?? 1)),
                        ]);
                    }
                    continue;
                }
                if (! $student) {
                    continue;
                }
                $rows->push([
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $assignment,
                    'attempt' => null,
                    'attempt_number' => 1,
                    'attempt_total' => max(1, (int) ($assessment->max_attempts ?? 1)),
                ]);
            }
        }

        if ($rows->isEmpty()) {
            try {
                $fallbackAttempts = LmsAssessmentAttempt::query()
                    ->with(['user', 'assessment.course'])
                    ->when($assessmentFilterIds !== [], fn ($q) => $q->whereIn('assessment_id', $assessmentFilterIds))
                    ->orderByDesc('id')
                    ->limit(2000)
                    ->get();
            } catch (\Throwable $e) {
                report($e);
                $fallbackAttempts = collect();
            }
            foreach ($fallbackAttempts as $att) {
                $assessment = $att->assessment;
                if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                    continue;
                }
                $student = $att->user ?: User::query()->find($att->user_id);
                if (! $student || $this->isRemovedTestTaker($student, $assessment)) {
                    continue;
                }
                $asg = $this->assignmentForAttempt($att, $assignments, $assignments->keyBy(fn ($row) => (int) $row->id));
                $rows->push([
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $asg,
                    'attempt' => $att,
                    'attempt_number' => 1,
                    'attempt_total' => max(1, (int) ($assessment->max_attempts ?? 1)),
                ]);
            }
        }

        $rows = $rows->map(function ($r) {
            if (! empty($r['sitting_display'])) {
                return $r;
            }
            return $this->applySittingDisplayToRow($r);
        })->values();

        try {
            $rows = $this->replaceCimaScsRows($rows, $assignments, $selectedAssessment, $assessmentsById, $allAttempts);
            $rows = $this->renumberCollapsedAttempts($rows);
            $rows = $rows->sortBy(function ($row) {
                $status = (string) (($row['sitting_display']['status'] ?? ''));
                $rank = match ($status) {
                    'In progress' => 0,
                    'Marking Required' => 1,
                    'Completed and Marked', 'Completed' => 2,
                    'Upcoming' => 3,
                    'Missed' => 4,
                    'Assigned', 'Available' => 5,
                    default => 6,
                };
                $title = mb_strtolower((string) ($row['assessment']->title ?? ''));
                $name = mb_strtolower((string) ($row['student']->name ?? ''));

                return sprintf('%d-%s-%s', $rank, $title, $name);
            })->values();
        } catch (\Throwable $e) {
            report($e);
        }

        if ($search !== '') {
            $rows = $rows->filter(fn ($r) => $this->assignedRowMatchesSearch($r, $search))->values();
        }

        if ($resultsView) {
            $haveAttemptIds = [];
            $rows = $rows->filter(function ($r) use (&$haveAttemptIds) {
                $att = $r['attempt'] ?? null;
                if (empty($att->id)) {
                    return false;
                }
                $id = (int) $att->id;
                if (isset($haveAttemptIds[$id])) {
                    return false;
                }
                $haveAttemptIds[$id] = true;

                return true;
            })->values();
            foreach ($allAttempts as $att) {
                if (isset($haveAttemptIds[(int) ($att->id ?? 0)])) {
                    continue;
                }
                $student = $att->user
                    ?: ($peopleById->get((int) $att->user_id) ?? User::query()->find($att->user_id));
                if (! $student) {
                    continue;
                }
                $assessment = $att->assessment
                    ?: ($assessmentsById->get((int) $att->assessment_id) ?? LmsAssessment::withTrashed()->find($att->assessment_id));
                if (! $assessment) {
                    continue;
                }
                $asg = $this->assignmentForAttempt($att, $assignments, $assignmentsById);
                $row = $this->applySittingDisplayToRow([
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $asg,
                    'attempt' => $att,
                    'attempt_number' => 1,
                    'attempt_total' => max(1, (int) ($assessment->max_attempts ?? 1)),
                ]);
                $rows->push($row);
                $haveAttemptIds[(int) $att->id] = true;
            }
            $rows = $rows->values();
        }
        $rows = $this->dedupeAssignedSittingRows($rows);
        $rows = $this->renumberCollapsedAttempts($rows);

        $statusOptions = [
            'Upcoming' => 'Upcoming',
            'Assigned' => 'Assigned',
            'In progress' => 'In progress',
            'Marking Required' => 'Marking Required',
            'Completed' => 'Completed',
            'Completed and Marked' => 'Completed and Marked',
            'Missed' => 'Missed',
        ];
        $statusCounts = [
            'Missed' => $rows->filter(fn ($r) => ($r['sitting_display']['status'] ?? '') === 'Missed')->count(),
            'Upcoming' => $rows->filter(fn ($r) => ($r['sitting_display']['status'] ?? '') === 'Upcoming')->count(),
        ];
        $dropdownFromTable = $rows->map(fn ($r) => $r['assessment'] ?? null)->filter()->values();
        if ($statusFilter !== '' && array_key_exists($statusFilter, $statusOptions)) {
            $rows = $rows->filter(function ($row) use ($statusFilter) {
                $statusText = $row['sitting_display']['status'] ?? '';
                if ($statusText === '') {
                    $attempt = $row['attempt'];
                    $assignment = $row['assignment'];
                    if ($attempt) {
                        $statusText = $attempt->statusLabel();
                    } elseif ($assignment && $assignment->isNotYetOpen()) {
                        $statusText = 'Upcoming';
                    } elseif ($assignment && method_exists($assignment, 'isMissedByStartGrace') && $assignment->isMissedByStartGrace()) {
                        $statusText = 'Missed';
                    } else {
                        $statusText = 'Assigned';
                    }
                }

                return $statusText === $statusFilter;
            })->values();
        }

        $assessmentQuery = LmsAssessment::query()->orderBy('title');
        if ($markerOnlyUid) {
            $this->applyInstructorAssessmentScope($assessmentQuery, $markerOnlyUid, $courseIdsScope);
        } elseif ($courseIdsScope !== null) {
            $assessmentQuery->where(function ($q) use ($courseIdsScope) {
                $q->whereIn('course_id', $courseIdsScope ?: [0]);
                if ($this->assessments->canManageSkillTests()) {
                    $q->orWhere('kind', LmsAssessment::KIND_SKILL)
                        ->orWhere('audience', LmsAssessment::AUDIENCE_STAFF);
                }
            });
        } elseif (! $this->assessments->canManageSkillTests()) {
            $assessmentQuery->where('kind', '!=', LmsAssessment::KIND_SKILL)
                ->where('audience', '!=', LmsAssessment::AUDIENCE_STAFF);
        }

        $kinds = LmsAssessment::kinds();
        if (! $this->assessments->canManageSkillTests()) {
            unset($kinds[LmsAssessment::KIND_SKILL]);
        }

        if ($request->query('export') === 'excel') {
            return $this->streamAssignedRowsExcel($rows, 'assigned-tests_' . now()->format('Y-m-d') . '.csv');
        }

        $page = max(1, (int) $request->get('page', 1));
        $perPage = $assessmentId > 0 ? 50 : 15;
        $total = $rows->count();
        $lastPage = max(1, (int) ceil(($total ?: 1) / $perPage));
        if ($page > $lastPage) {
            $page = $lastPage;
        }
        $paged = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => collect($request->query())->except('page')->all(),
            ]
        );

        return view('admin.assessments.tests.assigned-users', [
            'rows' => $paged,
            'rowsTotal' => $total,
            'scsRosterNote' => '',
            'statusCounts' => $statusCounts ?? ['Missed' => 0, 'Upcoming' => 0],
            'selectedAssessment' => $selectedAssessment,
            'resultsView' => $resultsView,
            'assessments' => $this->uniqueAssessmentsByTitle(
                $this->assessmentsWithAssignedPeople(
                    $this->uniqueAssessmentsByTitle($assessmentQuery->get(['id', 'title', 'kind']), $loadedAssignments),
                    $loadedAssignments,
                    $markerOnlyUid
                )->concat($dropdownFromTable),
                $loadedAssignments
            ),
            'batches' => Schema::hasTable('class_batches')
                ? ClassBatch::query()->orderByDesc('id')->get(['id', 'code', 'name'])
                : collect(),
            'courses' => $this->lms->coursesForActor(),
            'kinds' => $kinds,
            'statusOptions' => $statusOptions,
            'canDeleteAttempts' => $this->lms->isAdminActor() || panel_sees_all_dashboards(),
            'canAssign' => $this->assessments->canAssignStudentAssessments()
                || $this->assessments->canManageSkillTests(),
            'filters' => [
                'assessment_id' => $assessmentId,
                'batch_id' => $batchId,
                'course_id' => $courseId,
                'kind' => $kind,
                'status' => $statusFilter,
                'search' => $search,
                'view' => $resultsView ? 'results' : '',
            ],
        ]);
    }

    public function attempts(Request $request, $id)
    {
        $query = array_filter([
            'assessment_id' => (int) $id,
            'view' => 'results',
            'export' => $request->query('export'),
            'page' => $request->query('page'),
            'status' => $request->query('status'),
            'search' => $request->query('search'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect()->route('admin.assessments.tests.assigned-users', $query);
    }

    public function mark($attemptId)
    {
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['answers.question', 'user'])->findOrFail($attemptId);
        $assessment = LmsAssessment::withTrashed()->with('questions')->find($attempt->assessment_id);
        if ($assessment) {
            $attempt->setRelation('assessment', $assessment);
        }
        abort_unless($this->assessments->actorCanAccessAttempt($attempt), 403);

        return view('admin.assessments.tests.mark', compact('attempt'));
    }

    public function attemptPdf($attemptId)
    {
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['answers.question', 'user'])->findOrFail($attemptId);
        $assessment = LmsAssessment::withTrashed()->with(['course', 'questions'])->find($attempt->assessment_id);
        if ($assessment) {
            $attempt->setRelation('assessment', $assessment);
        }
        abort_unless($this->assessments->actorCanAccessAttempt($attempt), 403);

        $title = Str::slug($attempt->assessment->title ?? 'attempt', '-');
        $student = Str::slug($attempt->user->name ?? 'student', '-');
        $filename = 'attempt-' . $attempt->id . '-' . $title . '-' . $student . '.pdf';

        return Pdf::loadView('admin.assessments.tests.attempt-pdf', compact('attempt'))
            ->setPaper('a4')
            ->download($filename);
    }

    public function saveMarks(Request $request, $attemptId)
    {
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['answers.question'])->findOrFail($attemptId);
        $assessment = LmsAssessment::withTrashed()->with('questions')->find($attempt->assessment_id);
        if ($assessment) {
            $attempt->setRelation('assessment', $assessment);
        }
        abort_unless($this->assessments->actorCanAccessAttempt($attempt), 403);

        $feedbackMax = 5000;
        $overallMax = 5000;
        $errors = [];
        foreach ((array) $request->input('feedback', []) as $answerId => $feedback) {
            if ($this->plainTextLen($feedback) > $feedbackMax) {
                $errors['feedback.' . $answerId] = 'Feedback exceeds ' . number_format($feedbackMax) . ' characters.';
            }
        }
        foreach ((array) $request->input('part_feedback', []) as $qid => $rows) {
            foreach ((array) $rows as $sk => $feedback) {
                if ($this->plainTextLen($feedback) > $feedbackMax) {
                    $errors['part_feedback.' . $qid . '.' . $sk] = 'Feedback exceeds ' . number_format($feedbackMax) . ' characters.';
                }
            }
        }
        $overallLen = $this->plainTextLen($request->input('grader_feedback'));
        if ($overallLen < 1) {
            $errors['grader_feedback'] = 'Overall feedback to student is required.';
        } elseif ($overallLen > $overallMax) {
            $errors['grader_feedback'] = 'Overall feedback exceeds ' . number_format($overallMax) . ' characters.';
        }
        if ($errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

        foreach ((array) $request->input('part_score', []) as $questionId => $scores) {
            $question = $attempt->assessment->questions->firstWhere('id', (int) $questionId);
            if (! $question || ! $question->hasSubQuestions() || ! $question->needsManualMark()) {
                continue;
            }
            $answer = $attempt->answers->firstWhere('question_id', (int) $questionId);
            if (! $answer) {
                $answer = new LmsAttemptAnswer();
                $answer->attempt_id = $attempt->id;
                $answer->question_id = (int) $questionId;
                $answer->max_marks = (float) ($question->pivot->marks_override ?? $question->max_marks);
                $answer->save();
            }
            $this->assessments->markPartAnswers(
                $answer,
                $question->subQuestions(),
                (array) $scores,
                (array) $request->input('part_feedback.' . $questionId, [])
            );
        }

        foreach ((array) $request->input('manual', []) as $answerId => $score) {
            $answer = $attempt->answers->firstWhere('id', (int) $answerId);
            if (! $answer || ! $answer->question || ! $answer->question->needsManualMark()) {
                continue;
            }
            if ($answer->question->hasSubQuestions()) {
                continue;
            }
            $feedback = $request->input('feedback.' . $answerId);
            $this->assessments->markManualAnswer($answer, (float) $score, $feedback !== null ? (string) $feedback : null);
        }

        $graderFeedback = $request->input('grader_feedback');
        if ($graderFeedback !== null) {
            $attempt->grader_feedback = trim((string) $graderFeedback) === '' ? null : (string) $graderFeedback;
            $attempt->save();
        }

        if ($request->boolean('publish')) {
            $this->assessments->publishResults($attempt->fresh(), $request->input('grader_feedback'));
            return redirect()->route('admin.assessments.tests.assigned-users', [
                'assessment_id' => $attempt->assessment_id,
                'view' => 'results',
            ])->with('success', 'Marks saved and results published to the student.');
        }

        return back()->with('success', 'Marks saved.');
    }

    protected function plainTextLen(?string $html): int
    {
        $text = html_entity_decode(strip_tags((string) $html));
        $text = str_replace("\xc2\xa0", ' ', $text);

        return mb_strlen(trim($text));
    }

    public function publishAttempt($attemptId)
    {
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with('assessment')->findOrFail($attemptId);
        abort_unless($this->assessments->actorCanAccessAttempt($attempt), 403);
        abort_unless($attempt->status === 'graded', 422, 'Mark the attempt before publishing results.');
        $this->assessments->publishResults($attempt);

        return redirect()->route('admin.assessments.tests.assigned-users', ['assessment_id' => $attempt->assessment_id])
            ->with('success', 'Results published.');
    }


    protected function streamAssignedRowsExcel($rows, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $headers = [
            'Appointment ID', 'Test Name', 'Type', 'Test Taker', 'Attempt',
            'Appointment Window', 'Started', 'Ended', 'Assessor', 'Status', 'Score', 'Published',
        ];
        $callback = function () use ($rows, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                $assessment = $row['assessment'];
                $student = $row['student'];
                $assignment = $row['assignment'];
                $attempt = $row['attempt'] ?? null;
                if ($attempt) {
                    $statusText = $attempt->statusLabel();
                } elseif ($assignment && $assignment->isNotYetOpen()) {
                    $statusText = 'Upcoming';
                } elseif ($assignment && method_exists($assignment, 'isMissedByStartGrace') && $assignment->isMissedByStartGrace()) {
                    $statusText = 'Missed';
                } else {
                    $statusText = 'Assigned';
                }
                $score = '';
                if ($attempt && $attempt->results_published && $attempt->score_raw !== null && ! ($assessment->hidesOverallMarks())) {
                    $score = lms_format_number($attempt->score_raw) . '/' . lms_format_number($attempt->score_max);
                    if ($attempt->percent() !== null) {
                        $score .= ' (' . lms_format_number($attempt->percent()) . '%)';
                    }
                }
                $disp = $row['sitting_display'] ?? [];
                fputcsv($handle, [
                    $disp['appt_id'] ?? ($assignment ? $assignment->displayCode() : '—'),
                    $assessment->title ?? '—',
                    method_exists($assessment, 'kindLabel') ? $assessment->kindLabel() : ($assessment->kind ?? ''),
                    $student->name ?? '—',
                    'Attempt ' . (int) ($row['attempt_number'] ?? 1) . ' of ' . max(1, (int) ($row['attempt_total'] ?? 1)),
                    $disp['appointment'] ?? ($assignment ? $assignment->appointmentWindowLabel() : '—'),
                    ($assignment && $attempt?->started_at) ? $assignment->formatInTimezone($attempt->started_at) : (optional($attempt?->started_at)->format('d/m/y H:i') ?? '—'),
                    ($assignment && $attempt?->submitted_at) ? $assignment->formatInTimezone($attempt->submitted_at) : (optional($attempt?->submitted_at)->format('d/m/y H:i') ?? '—'),
                    $disp['assessor'] ?? ($attempt ? $attempt->assessorLabel($assignment) : ($assignment?->marker?->name ?? '—')),
                    $statusText,
                    $score !== '' ? $score : '—',
                    ($attempt && $attempt->results_published) ? 'Yes' : 'No',
                ]);
            }
            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function rules(Request $request): array
    {
        $courseRequired = $this->lms->isInstructorActor()
            && ! $this->lms->isAdminActor()
            && ! panel_sees_all_dashboards()
            && $request->input('kind') !== LmsAssessment::KIND_SKILL;

        $isSkill = $request->input('kind') === LmsAssessment::KIND_SKILL;

        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:20000',
            'kind' => 'required|in:practice,mock,general,skill',
            'course_id' => ($courseRequired ? 'required' : 'nullable') . '|exists:courses,id',
            'target_role' => ($isSkill ? 'required' : 'nullable') . '|in:' . implode(',', array_keys(LmsAssessment::targetRoles())),
            'duration_minutes' => 'nullable|integer|min:1',
            'max_attempts' => 'required|integer|min:1|max:50',
            'pass_percent' => 'nullable|numeric|min:0|max:100',
            'shuffle_questions' => 'nullable|boolean',
            'results_mode' => 'required|in:instant_mcq,instant,after_publish',
            'allow_pause' => 'nullable|boolean',
            'status' => 'required|in:draft,published,disabled',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'exists:lms_questions,id',
            'reference_files' => 'nullable|array|max:1',
            'reference_files.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp,txt|max:20480',
            'preseen_files' => 'nullable|array|max:1',
            'preseen_files.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,gif,webp,txt|max:20480',
            'remove_reference' => 'nullable|array',
            'remove_preseen' => 'nullable|array',
        ];
    }

    protected function assertInstructorOwnsCourse(int $courseId): void
    {
        if ($this->lms->isAdminActor() || panel_sees_all_dashboards()) {
            return;
        }
        if (! $this->lms->isInstructorActor()) {
            return;
        }
        // General / no-course tests: nothing to check (avoid 403 on Results).
        if ($courseId <= 0) {
            return;
        }
        abort_unless($this->lms->instructorAssignedToCourse($courseId), 403);
    }

    protected function fill(LmsAssessment $a, Request $request, bool $creating): void
    {
        $a->title = trim((string) $request->input('title'));
        $a->description = trim((string) $request->input('description', '')) ?: null;
        $a->kind = $request->input('kind');
        $a->audience = $a->kind === LmsAssessment::KIND_SKILL
            ? LmsAssessment::AUDIENCE_STAFF
            : LmsAssessment::AUDIENCE_STUDENT;
        $a->course_id = $a->kind === LmsAssessment::KIND_SKILL ? null : ($request->input('course_id') ?: null);
        if ($a->course_id) {
            $this->assertInstructorOwnsCourse((int) $a->course_id);
        }
        if (Schema::hasColumn('lms_assessments', 'target_role')) {
            $role = trim((string) $request->input('target_role', ''));
            $a->target_role = $a->kind === LmsAssessment::KIND_SKILL
                ? ($role !== '' ? $role : 'all_staff')
                : ($role !== '' ? $role : null);
        }
        $a->duration_minutes = $request->filled('duration_minutes') ? (int) $request->input('duration_minutes') : null;
        // Pass/Fail threshold removed from UI — criteria varies by course; score % only.
        $a->pass_percent = null;
        // Retake only when admin/instructor sets N > 1 (each sitting still needs Assign).
        $a->max_attempts = max(1, (int) $request->input('max_attempts', 1));
        $a->shuffle_questions = $request->boolean('shuffle_questions');
        $mode = $request->input('results_mode', 'after_publish');
        $a->results_mode = $mode === 'instant' ? 'instant_mcq' : $mode;
        if (Schema::hasColumn('lms_assessments', 'allow_pause') && $request->has('allow_pause')) {
            $a->allow_pause = $request->boolean('allow_pause');
        }
        $a->status = $request->input('status', 'draft');
        if ($a->status === 'published' && ! $a->published_at) {
            $a->published_at = now();
        }
        if ($creating) {
            if ($this->lms->isAdminActor()) {
                $a->created_by_admin_id = Auth::guard('admin')->id();
            } else {
                $a->created_by_user_id = Auth::id();
            }
        }
    }

    protected function syncQuestions(LmsAssessment $a, Request $request): void
    {
        $ids = collect($request->input('question_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $sync = [];
        foreach ($ids as $i => $qid) {
            $sync[$qid] = ['sort_order' => $i];
        }
        $a->questions()->sync($sync);
    }

    protected function syncResourceFiles(LmsAssessment $a, Request $request): ?string
    {
        if (! Schema::hasColumn('lms_assessments', 'reference_files_json')) {
            return null;
        }
        $wantedUploads = 0;
        $storedUploads = 0;
        $error = null;
        foreach ([
            'reference' => ['input' => 'reference_files', 'col' => 'reference_files_json', 'remove' => 'remove_reference'],
            'preseen' => ['input' => 'preseen_files', 'col' => 'preseen_files_json', 'remove' => 'remove_preseen'],
        ] as $kind => $cfg) {
            if (! Schema::hasColumn('lms_assessments', $cfg['col'])) {
                continue;
            }
            $existing = is_array($a->{$cfg['col']}) ? $a->{$cfg['col']} : [];
            $remove = array_map('intval', (array) $request->input($cfg['remove'], []));
            $kept = [];
            foreach ($existing as $i => $row) {
                if (! is_array($row)) {
                    continue;
                }
                if (in_array((int) $i, $remove, true)) {
                    $diskPath = (string) ($row['path'] ?? '');
                    if ($diskPath !== '' && ! str_contains($diskPath, 'workdrive.zoho') && ! str_starts_with($diskPath, 'http')) {
                        Storage::disk('public')->delete($diskPath);
                    }
                    continue;
                }
                $kept[] = $row;
            }
            $uploads = $request->file($cfg['input'], []);
            if (! is_array($uploads)) {
                $uploads = $uploads ? [$uploads] : [];
            }
            foreach ($uploads as $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    continue;
                }
                $wantedUploads++;
                if (count($kept) >= 1) {
                    $kept = [];
                }
                if (! $this->zoho->isWorkDriveReady()) {
                    $error = 'Connect Zoho WorkDrive OAuth first. Exam files are stored on Zoho Drive, not this server.';
                    continue;
                }
                try {
                    $remote = $this->zoho->uploadToWorkDrive($file);
                } catch (\Throwable $e) {
                    Log::error('Zoho WorkDrive exam file upload threw', ['message' => $e->getMessage()]);
                    $error = 'WorkDrive could not be reached. Check Zoho OAuth and try again.';
                    continue;
                }
                if (! $remote || empty($remote['url'])) {
                    $error = 'WorkDrive upload failed. Check the WorkDrive folder ID and try again.';
                    continue;
                }
                $kept[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => (string) $remote['url'],
                    'url' => (string) $remote['url'],
                    'id' => $remote['id'] ?? null,
                    'mime' => 'zoho/workdrive',
                ];
                $storedUploads++;
            }
            $a->{$cfg['col']} = $kept !== [] ? array_values(array_slice($kept, 0, 1)) : null;
        }
        $a->save();
        if ($wantedUploads > 0 && $storedUploads === 0) {
            return $error ?: 'WorkDrive upload failed.';
        }
        if ($error && $storedUploads < $wantedUploads) {
            return $error;
        }

        return null;
    }

    protected function studentsForBatchId(int $bid)
    {
        $ids = ClassBatch::studentUserIdsFor($bid);
        $users = collect();
        try {
            $batch = ClassBatch::query()->with('students')->find($bid);
            if ($batch && $batch->students) {
                $users = $users->concat($batch->students);
                $ids = $ids->merge($batch->students->pluck('id'));
            }
        } catch (\Throwable $e) {
            // keep id list
        }
        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
        if ($ids->isNotEmpty()) {
            $foundUsers = User::query()->whereIn('id', $ids->all())->get();
            $users = $users->concat($foundUsers);
        }
        $users = $users->unique(fn ($row) => (int) ($row->id ?? 0))->values();
        $found = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($ids as $sid) {
            if (! in_array((int) $sid, $found, true)) {
                $users->push((object) [
                    'id' => (int) $sid,
                    'name' => 'User #' . (int) $sid,
                    'email' => '',
                ]);
            }
        }

        return $users;
    }

    /** Keep a missed exam sitting and a late/other attempt as two rows. */
    protected function splitAttemptsThatDoNotBelongToSitting(array &$rowsByKey): void
    {
        foreach ($rowsByKey as $k => $r) {
            $att = $r['attempt'] ?? null;
            $asg = $r['assignment'] ?? null;
            if (! $att || ! $asg) {
                continue;
            }
            try {
                if ($this->assessments->attemptBelongsToAssignment($att, $asg)) {
                    continue;
                }
            } catch (\Throwable $e) {
                continue;
            }
            $attemptKey = 'attempt:' . (int) $att->id;
            if (! isset($rowsByKey[$attemptKey])) {
                $copy = $r;
                $rowsByKey[$attemptKey] = $copy;
            }
            $rowsByKey[$k]['attempt'] = null;
            unset($rowsByKey[$k]['sitting_display']);
        }
    }

    /** Put each MCS exam sitting on the list for the cohort, same as student My Tests. */
    protected function attachCimaCohortSittingsToAssignedRows(array &$rowsByKey, $assignments, $assessmentsById): void
    {
        $cohort = $this->cimaCohortUsers();
        if ($cohort->isEmpty()) {
            return;
        }
        foreach (collect($assignments) as $assignment) {
            if (! $assignment || empty($assignment->id)) {
                continue;
            }
            $assessment = $assignment->assessment
                ?? ($assessmentsById instanceof \Illuminate\Support\Collection
                    ? $assessmentsById->get((int) ($assignment->assessment_id ?? 0))
                    : null);
            if ($assignment && method_exists($this->assessments, 'alignSittingWithExistingExamRule')) {
                $this->assessments->alignSittingWithExistingExamRule($assignment, $assessment);
            }
            if (method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting()) {
                continue;
            }
            if (! $this->isCimaCohortSitting($assessment, $assignment, $cohort)) {
                continue;
            }
            $ex = collect();
            try {
                if (Schema::hasColumn($assignment->getTable(), 'excluded_user_ids')) {
                    $ex = collect($assignment->excluded_user_ids ?? [])->map(fn ($id) => (int) $id);
                }
            } catch (\Throwable $e) {
                $ex = collect();
            }
            foreach ($cohort as $student) {
                $uid = (int) ($student->id ?? 0);
                if ($uid <= 0 || $ex->contains($uid) || $this->isRemovedTestTaker($student, $assessment)) {
                    continue;
                }
                $key = (int) $assignment->id . ':' . $uid;
                if (isset($rowsByKey[$key])) {
                    continue;
                }
                $rowsByKey[$key] = [
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $assignment,
                    'attempt' => null,
                ];
            }
        }
    }

    protected function expandSittingStudents($students, $assignment, $assessment, array &$batchStudentCache)
    {
        $students = collect($students)->filter(fn ($row) => $row && ! empty($row->id))->values();
        if ($assignment && method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting()) {
            $ids = $students->pluck('id');
            if (! empty($assignment->student_id)) {
                $ids->push((int) $assignment->student_id);
            }
            if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                $ids = $ids->merge(collect($assignment->roster_user_ids ?? []));
            }
            $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
            if ($ids->isEmpty()) {
                return collect();
            }
            $found = User::query()->whereIn('id', $ids->all())->get();
            $have = $found->pluck('id')->map(fn ($id) => (int) $id)->all();
            $students = $found->concat($students);
            foreach ($ids as $sid) {
                if (! in_array((int) $sid, $have, true)) {
                    $students->push((object) [
                        'id' => (int) $sid,
                        'name' => 'User #' . (int) $sid,
                        'email' => '',
                    ]);
                }
            }

            return $students
                ->unique(fn ($row) => (int) ($row->id ?? 0))
                ->filter(fn ($row) => $this->studentOnSitting($row, $assignment, $assessment))
                ->values();
        }
        if ($this->looksLikeCimaScsNovember($assessment) && ! $this->isCimaScsAssignedMock($assessment)) {
            return collect();
        }
        if ($this->isCimaScsAssignedMock($assessment)) {
            $ids = $students->pluck('id');
            if ($assignment && ! empty($assignment->student_id)) {
                $ids->push((int) $assignment->student_id);
            }
            if ($assignment && Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                $ids = $ids->merge(collect($assignment->roster_user_ids ?? []));
            }
            $self = $assignment && method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting();
            $batchId = (int) ($assignment->class_batch_id ?? 0);
            if (! $self) {
                try {
                    $ids = $ids->merge(ClassBatch::studentUserIdsForSitting($assignment));
                } catch (\Throwable $e) {
                    // keep named/roster ids
                }
                if ($batchId <= 0) {
                    $batch = $this->loadCimaScsNovemberBatch();
                    $batchId = (int) ($batch->id ?? 0);
                }
                if ($batchId > 0) {
                    try {
                        $ids = $ids->merge(ClassBatch::membersOnBatchAtAssignment($batchId, $assignment));
                    } catch (\Throwable $e) {
                        // keep named/roster ids
                    }
                }
            }
            $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
            if ($ids->isNotEmpty()) {
                $found = User::query()->whereIn('id', $ids->all())->get();
                $have = $found->pluck('id')->map(fn ($id) => (int) $id)->all();
                $students = $found->concat($students);
                foreach ($ids as $sid) {
                    if (! in_array((int) $sid, $have, true)) {
                        $students->push((object) [
                            'id' => (int) $sid,
                            'name' => 'User #' . (int) $sid,
                            'email' => '',
                        ]);
                    }
                }
            }

            return $students
                ->unique(fn ($row) => (int) ($row->id ?? 0))
                ->filter(fn ($row) => $this->studentOnSitting($row, $assignment, $assessment))
                ->values();
        }
        if ($this->isMock1PersonSitting($assignment, $assessment)
            || (method_exists($this->assessments, 'isMock1FourthNovemberSitting')
                && $this->assessments->isMock1FourthNovemberSitting($assignment, $assessment))) {
            return $students
                ->unique(fn ($row) => (int) ($row->id ?? 0))
                ->filter(fn ($row) => $this->studentOnSitting($row, $assignment, $assessment))
                ->values();
        }
        if ($assignment) {
            $snap = ClassBatch::studentUserIdsForSitting($assignment);
            if ($snap->isNotEmpty()) {
                $snapUsers = User::query()->whereIn('id', $snap->all())->get();
                $students = $students->concat($snapUsers);
                $foundSnap = $snapUsers->pluck('id')->map(fn ($id) => (int) $id)->all();
                foreach ($snap as $sid) {
                    if (! in_array((int) $sid, $foundSnap, true)) {
                        $students->push((object) [
                            'id' => (int) $sid,
                            'name' => 'User #' . (int) $sid,
                            'email' => '',
                        ]);
                    }
                }
            }
        }
        if ($this->isCimaCohortSitting($assessment, $assignment, $students)) {
            $cohort = $this->cimaCohortUsers();
            if ($cohort->isNotEmpty()) {
                $students = $students->concat($cohort);
                $bat = ClassBatch::query()->where('code', 'BAT-0003')->first();
                if ($bat && $assignment && empty($assignment->class_batch_id)) {
                    $assignment->setRelation('batch', $bat);
                }
            }
        }
        return $students
            ->unique(fn ($row) => (int) ($row->id ?? 0))
            ->filter(fn ($row) => $this->studentOnSitting($row, $assignment, $assessment))
            ->values();
    }

    protected function isMock1PersonSitting($assignment, $assessment): bool
    {
        if (! $assignment || ! $assessment) {
            return false;
        }
        if (! method_exists($this->assessments, 'isCimaScsMock1') || ! $this->assessments->isCimaScsMock1($assessment)) {
            return false;
        }

        return ! empty($assignment->student_id) && empty($assignment->class_batch_id);
    }

    /** Same for every test: one unused class sitting per person. Keep the original (lowest id). Taken and self-test rows stay. */
    protected function keepOriginalUnusedClassSitting(array $rowsByKey): array
    {
        $byPair = [];
        foreach ($rowsByKey as $k => $r) {
            $aid = (int) ($r['assessment']->id ?? 0);
            $uid = (int) ($r['student']->id ?? 0);
            if ($aid <= 0 || $uid <= 0) {
                continue;
            }
            $byPair[$aid . ':' . $uid][$k] = $r;
        }
        $drop = [];
        foreach ($byPair as $rows) {
            $unusedClass = [];
            foreach ($rows as $k => $r) {
                $asg = $r['assignment'] ?? null;
                if (! $asg || ! empty($r['attempt'])) {
                    continue;
                }
                if (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) {
                    continue;
                }
                $uid = (int) ($r['student']->id ?? 0);
                $named = (int) ($asg->student_id ?? 0) === $uid && empty($asg->class_batch_id);
                if ($named) {
                    continue;
                }
                $unusedClass[$k] = (int) ($asg->id ?? 0);
            }
            if (count($unusedClass) < 2) {
                continue;
            }
            asort($unusedClass);
            $keepKey = array_key_first($unusedClass);
            foreach ($unusedClass as $k => $id) {
                if ($k !== $keepKey) {
                    $drop[$k] = true;
                }
            }
        }
        if ($drop === []) {
            return $rowsByKey;
        }

        return array_filter($rowsByKey, fn ($row, $k) => empty($drop[$k]), ARRAY_FILTER_USE_BOTH);
    }

    /** Drop duplicate lines with no sitting (blank Appointment ID). */
    protected function dropGhostAssignmentRows(array $rowsByKey): array
    {
        $real = [];
        $ghosts = [];
        foreach ($rowsByKey as $k => $r) {
            if ((int) ($r['assignment']->id ?? 0) > 0) {
                $real[$k] = $r;
            } else {
                $ghosts[$k] = $r;
            }
        }
        foreach ($ghosts as $k => $r) {
            $aid = (int) ($r['assessment']->id ?? 0);
            $uid = (int) ($r['student']->id ?? 0);
            $hasReal = collect($real)->contains(function ($row) use ($aid, $uid) {
                return (int) ($row['assessment']->id ?? 0) === $aid
                    && (int) ($row['student']->id ?? 0) === $uid;
            });
            if ($hasReal || empty($r['attempt'])) {
                if (! empty($r['attempt'])) {
                    foreach ($real as $rk => $row) {
                        if ((int) ($row['assessment']->id ?? 0) !== $aid || (int) ($row['student']->id ?? 0) !== $uid) {
                            continue;
                        }
                        if (! empty($row['attempt'])) {
                            continue;
                        }
                        $ownAsg = $row['assignment'] ?? null;
                        if ($ownAsg && method_exists($ownAsg, 'isSelfSitting') && $ownAsg->isSelfSitting()) {
                            continue;
                        }
                        $real[$rk]['attempt'] = $r['attempt'];
                        break;
                    }
                }
                if ($this->isCimaCohortSitting($r['assessment'] ?? null, $r['assignment'] ?? null, collect([$r['student'] ?? null]))) {
                    $real[$k] = $r;
                }
                continue;
            }
            $real[$k] = $r;
        }

        return $real;
    }

    protected function looksLikeCimaScsNovember($assessment): bool
    {
        $title = mb_strtolower(trim((string) ($assessment->title ?? '')));
        if ($title === '' || str_contains($title, 'masterclass')) {
            return false;
        }
        if (str_contains($title, 'management case') || preg_match('/\bmcs\b/', $title)) {
            return false;
        }
        if (str_contains($title, 'scs') || str_contains($title, 'strategic')) {
            return true;
        }

        return str_contains($title, 'cima') && str_contains($title, 'mock');
    }

    /** Only Mock 1 and Mock 2 were assigned to people. Mock 3–7 have no sitters. */
    protected function isCimaScsAssignedMock($assessment): bool
    {
        if (! $assessment) {
            return false;
        }
        if (method_exists($this->assessments, 'isCimaScsMock1') && $this->assessments->isCimaScsMock1($assessment)) {
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

    protected function loadCimaScsNovemberBatch(): ?ClassBatch
    {
        $raw = collect();
        try {
            if (Schema::hasTable('class_batches')) {
                $raw = collect(DB::table('class_batches')->get());
            }
        } catch (\Throwable $e) {
            try {
                $raw = ClassBatch::query()->get();
            } catch (\Throwable $e2) {
                return null;
            }
        }
        $best = null;
        $bestScore = -1;
        foreach ($raw as $row) {
            $code = mb_strtolower(trim((string) ($row->code ?? '')));
            $name = mb_strtolower(trim((string) ($row->name ?? '')));
            if (str_contains($name, 'masterclass') || str_contains($code, 'masterclass')) {
                continue;
            }
            $score = 0;
            if ($code === 'bat-0004' || $code === 'bat0004' || $code === '0004') {
                $score += 1000;
            }
            if (str_contains($code, '0004')) {
                $score += 400;
            }
            if (str_contains($name, 'scs') || str_contains($name, 'strategic')) {
                $score += 80;
            }
            if (str_contains($name, 'november 2026') || str_contains($name, 'nov 2026')) {
                $score += 40;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }
        if (! $best || $bestScore < 80) {
            return null;
        }
        $id = (int) ($best->id ?? 0);
        if ($id <= 0) {
            return null;
        }
        $model = ClassBatch::query()->find($id);
        if ($model) {
            return $model;
        }
        $model = new ClassBatch();
        $model->forceFill([
            'code' => $best->code ?? 'BAT-0004',
            'name' => $best->name ?? '',
            'course_id' => $best->course_id ?? null,
        ]);
        $model->id = $id;
        $model->exists = true;

        return $model;
    }

    protected function loadCimaScsNovemberStudents($assignment = null, $assessment = null)
    {
        $ids = collect();
        $batchId = (int) ($assignment->class_batch_id ?? 0);
        $batch = $batchId > 0 ? ClassBatch::query()->find($batchId) : null;
        if (! $batch) {
            $batch = $this->loadCimaScsNovemberBatch();
            $batchId = (int) ($batch->id ?? 0);
        }
        $courseId = (int) ($assessment->course_id ?? 0);
        if ($courseId <= 0 && $batch) {
            $courseId = (int) ($batch->course_id ?? 0);
        }
        if ($batchId <= 0 && $courseId > 0) {
            try {
                $cands = ClassBatch::query()->where('course_id', $courseId)->get();
                $cands = $cands->filter(function ($row) {
                    $hay = mb_strtolower(trim((string) ($row->code ?? '') . ' ' . (string) ($row->name ?? '')));

                    return ! str_contains($hay, 'masterclass');
                });
                $prefer = $cands->first(function ($row) {
                    return str_contains(mb_strtolower((string) ($row->code ?? '')), '0004')
                        || str_contains(mb_strtolower((string) ($row->name ?? '')), 'scs');
                }) ?: $cands->first();
                if ($prefer) {
                    $batch = $prefer;
                    $batchId = (int) $prefer->id;
                }
            } catch (\Throwable $e) {
                // keep empty batch
            }
        }
        if (! $batch && $batchId <= 0) {
            $this->scsRosterNote = 'no BAT-0004';
        }
        $ids = collect();
        $pivotN = 0;
        if ($batch) {
            $pivotTables = ['class_batch_student', 'class_batch_students', 'batch_student', 'batch_students'];
            foreach ($pivotTables as $table) {
                try {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }
                    $cols = Schema::getColumnListing($table);
                    $batchCol = collect(['class_batch_id', 'batch_id', 'class_id'])->first(fn ($c) => in_array($c, $cols, true));
                    $userCols = collect(['student_id', 'user_id', 'learner_id'])->filter(fn ($c) => in_array($c, $cols, true))->values()->all();
                    if (! $batchCol || $userCols === []) {
                        continue;
                    }
                    $pivot = DB::table($table)->where($batchCol, (int) $batch->id)->get();
                    $pivotN = max($pivotN, $pivot->count());
                    foreach ($pivot as $row) {
                        $arr = (array) $row;
                        foreach ($userCols as $col) {
                            if (! empty($arr[$col])) {
                                $ids->push((int) $arr[$col]);
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $this->scsRosterNote = 'pivot-err';
                }
            }
            try {
                $ids = $ids->merge($batch->students()->pluck('users.id'));
            } catch (\Throwable $e) {
                // keep pivot ids
            }
            try {
                $ids = $ids->merge(ClassBatch::studentUserIdsFor((int) $batch->id));
            } catch (\Throwable $e) {
                // keep pivot ids
            }
        }
        try {
            $yahia = User::query()->where(function ($q) {
                $q->where('name', 'like', '%yahia%')
                    ->orWhere('name', 'like', '%yahya%')
                    ->orWhere('email', 'like', '%yahia%')
                    ->orWhere('email', 'like', '%yahya%')
                    ->orWhere('email', 'like', '%kafi%');
            })->pluck('id');
            $ids = $ids->merge($yahia);
        } catch (\Throwable $e) {
            // keep other ids
        }
        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
        $users = collect();
        if ($ids->isNotEmpty()) {
            try {
                $users = User::query()->whereIn('id', $ids->all())->get();
            } catch (\Throwable $e) {
                $users = collect();
            }
        }
        $found = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($ids as $sid) {
            if (! in_array((int) $sid, $found, true)) {
                $users->push((object) [
                    'id' => (int) $sid,
                    'name' => 'User #' . (int) $sid,
                    'email' => '',
                ]);
            }
        }
        $users = $users->unique(fn ($row) => (int) ($row->id ?? 0))->filter(fn ($row) => (int) ($row->id ?? 0) > 0)->values();
        $this->scsRosterNote = trim((string) (($batch->code ?? '') ?: 'BAT-0004'))
            . ' n=' . $users->count()
            . ' pivot=' . $pivotN;

        return $users;
    }

    protected function seedAssignmentForScs($assessment, $assignments, $rows): ?LmsAssessmentAssignment
    {
        $aid = (int) ($assessment->id ?? 0);
        $cands = collect($assignments)->filter(fn ($a) => (int) ($a->assessment_id ?? 0) === $aid);
        $seed = ($assessment && method_exists($this->assessments, 'isCimaScsMock1') && $this->assessments->isCimaScsMock1($assessment))
            ? ($this->mock1RosterSeed($cands, $assessment) ?: $cands->sortBy('id')->first())
            : $cands->sortByDesc('id')->first();
        if ($seed && ! empty($seed->id)) {
            return $seed;
        }
        foreach ($rows as $r) {
            if ((int) ($r['assessment']->id ?? 0) !== $aid) {
                continue;
            }
            $asg = $r['assignment'] ?? null;
            if ($asg && ! empty($asg->id)) {
                return $asg;
            }
            $attId = (int) ($r['attempt']->assignment_id ?? 0);
            if ($attId > 0) {
                try {
                    $found = LmsAssessmentAssignment::query()->find($attId);
                    if ($found) {
                        return $found;
                    }
                } catch (\Throwable $e) {
                    // try table
                }
            }
        }
        try {
            $found = LmsAssessmentAssignment::query()->where('assessment_id', $aid)->orderByDesc('id')->first();
            if ($found) {
                return $found;
            }
        } catch (\Throwable $e) {
            // next
        }
        try {
            $attId = (int) DB::table('lms_assessment_attempts')
                ->where('assessment_id', $aid)
                ->whereNotNull('assignment_id')
                ->max('assignment_id');
            if ($attId > 0) {
                $found = LmsAssessmentAssignment::query()->find($attId);
                if ($found) {
                    return $found;
                }
                $seed = new LmsAssessmentAssignment();
                $seed->forceFill(['assessment_id' => $aid]);
                $seed->id = $attId;
                $seed->exists = true;

                return $seed;
            }
        } catch (\Throwable $e) {
            // none
        }

        return null;
    }

    /**
     * People who already sat this Test must keep that attempt on the exam sitting.
     * Do not paint them Missed because the attempt has no assignment_id or a dropped sitting.
     */
    protected function attachTakenAttemptsToExamRows($rows, $attempts, $assignments)
    {
        $list = collect($rows);
        $attempts = collect($attempts);
        if ($list->isEmpty() || $attempts->isEmpty()) {
            return is_array($rows) ? $list->all() : $list->values();
        }
        $asgById = collect($assignments)->filter()->keyBy(fn ($a) => (int) ($a->id ?? 0));
        $claimed = [];
        foreach ($list as $r) {
            if (! empty($r['attempt']->id)) {
                $claimed[(int) $r['attempt']->id] = true;
            }
        }
        $titleIds = [];
        foreach ($list as $k => $r) {
            if (! empty($r['attempt'])) {
                continue;
            }
            $asg = $r['assignment'] ?? null;
            $assessment = $r['assessment'] ?? null;
            $uid = (int) ($r['student']->id ?? 0);
            if (! $asg || $uid <= 0) {
                continue;
            }
            if (method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) {
                continue;
            }
            $aid = (int) ($assessment->id ?? $asg->assessment_id ?? 0);
            if ($aid > 0 && ! isset($titleIds[$aid])) {
                $titleIds[$aid] = $assessment ? $this->sameTitleAssessmentIds($assessment) : [$aid];
            }
            $aids = $titleIds[$aid] ?? [$aid];
            $match = $attempts->first(function ($att) use ($uid, $aids, $asg, $asgById, $claimed, $list) {
                if (! $att || isset($claimed[(int) ($att->id ?? 0)])) {
                    return false;
                }
                if ((int) ($att->user_id ?? 0) !== $uid) {
                    return false;
                }
                if (! in_array((int) ($att->assessment_id ?? 0), $aids, true)) {
                    return false;
                }
                $attAsgId = (int) ($att->assignment_id ?? 0);
                $attAsg = $attAsgId > 0 ? $asgById->get($attAsgId) : null;
                if ($attAsg && method_exists($attAsg, 'isSelfSitting') && $attAsg->isSelfSitting()) {
                    return false;
                }
                if ($attAsgId === (int) ($asg->id ?? 0)) {
                    return true;
                }
                if ($attAsgId <= 0 || ! $attAsg) {
                    return true;
                }
                $onOwnSitting = $list->contains(function ($row) use ($attAsgId, $uid) {
                    return (int) ($row['assignment']->id ?? 0) === $attAsgId
                        && (int) ($row['student']->id ?? 0) === $uid
                        && ! empty($row['attempt']);
                });

                return ! $onOwnSitting;
            });
            if ($match) {
                $r['attempt'] = $match;
                $claimed[(int) $match->id] = true;
                $list[$k] = $r;
            }
        }

        return is_array($rows) ? $list->all() : $list->values();
    }

    /** Replace the Mock 1/2 list with the BAT-0004 roster; keep existing attempts. */
    protected function replaceCimaScsRows($rows, $assignments, $selectedAssessment, $assessmentsById, $allAttempts = null)
    {
        $targets = collect();
        if ($selectedAssessment && $this->isCimaScsAssignedMock($selectedAssessment)) {
            $targets->push($selectedAssessment);
        }
        foreach ($rows as $r) {
            $assessment = $r['assessment'] ?? null;
            if ($assessment && $this->isCimaScsAssignedMock($assessment)) {
                $targets->push($assessment);
            }
        }
        foreach (collect($assignments) as $asg) {
            $aid = (int) ($asg->assessment_id ?? 0);
            $assessment = $asg->assessment
                ?? ($assessmentsById instanceof \Illuminate\Support\Collection ? $assessmentsById->get($aid) : null)
                ?? ($aid > 0 ? LmsAssessment::query()->find($aid) : null);
            if ($assessment && $this->isCimaScsAssignedMock($assessment)) {
                $targets->push($assessment);
            }
        }
        $targets = $targets
            ->filter()
            ->groupBy(fn ($a) => mb_strtolower(trim((string) ($a->title ?? ('id:' . (int) ($a->id ?? 0))))))
            ->map(function ($group) {
                if ($group->count() === 1) {
                    return $group->first();
                }

                return $group->sortByDesc(fn ($a) => (int) ($a->id ?? 0))->first();
            })
            ->values();
        if ($targets->isEmpty()) {
            if ($this->scsRosterNote === '') {
                $this->scsRosterNote = 'no SCS target';
            }

            return $rows;
        }
        foreach ($targets as $assessment) {
            $aid = (int) ($assessment->id ?? 0);
            $seed = $this->seedAssignmentForScs($assessment, $assignments, $rows);
            if (! $seed || empty($seed->id)) {
                $fromRow = (int) collect($rows)->map(fn ($r) => (int) ($r['assignment']->id ?? $r['attempt']->assignment_id ?? 0))->filter()->first();
                $seed = new LmsAssessmentAssignment();
                $seed->forceFill(['assessment_id' => $aid]);
                $seed->id = $fromRow > 0 ? $fromRow : 1;
                $seed->exists = false;
                $this->scsRosterNote .= ' · synthetic-seed';
            }
            $students = collect();
            $excluded = $this->excludedUserIdsForAssessment($assessment);
            $honorExcluded = true;
            $existingByUser = [];
            $kept = collect();
            foreach ($rows as $r) {
                if ((int) ($r['assessment']->id ?? 0) !== $aid) {
                    $kept->push($r);
                    continue;
                }
                $uid = (int) ($r['student']->id ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                $slot = $uid . ':' . $this->sittingKindKey($r['assignment'] ?? null);
                if (! isset($existingByUser[$slot]) || $this->cimaScsRowOutranks($r, $existingByUser[$slot], $assessment)) {
                    $existingByUser[$slot] = $r;
                }
            }
            $added = 0;
            $isMock1 = $assessment && method_exists($this->assessments, 'isCimaScsMock1')
                && $this->assessments->isCimaScsMock1($assessment);
            foreach ($students as $student) {
                $uid = (int) ($student->id ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                if ($honorExcluded && $excluded->contains($uid)) {
                    foreach (array_keys($existingByUser) as $slot) {
                        if (str_starts_with((string) $slot, $uid . ':')) {
                            unset($existingByUser[$slot]);
                        }
                    }
                    continue;
                }
                $isYahia = method_exists($this->assessments, 'isYahiaUser')
                    && $this->assessments->isYahiaUser($student);
                if ($isMock1 && $isYahia) {
                    foreach (array_keys($existingByUser) as $slot) {
                        if (! str_starts_with((string) $slot, $uid . ':')) {
                            continue;
                        }
                        $ex = $existingByUser[$slot];
                        $ownNov = method_exists($this->assessments, 'isMock1YahiaNovemberSitting')
                            && $this->assessments->isMock1YahiaNovemberSitting($ex['assignment'] ?? null, $assessment, $student);
                        $named = (int) (($ex['assignment']->student_id ?? 0));
                        if ($ownNov || $named === $uid || ! empty($ex['attempt']) || $this->sittingKindKey($ex['assignment'] ?? null) === 'self') {
                            $kept->push($ex);
                        }
                        unset($existingByUser[$slot]);
                    }
                    continue;
                }
                foreach (array_keys($existingByUser) as $slot) {
                    if (! str_starts_with((string) $slot, $uid . ':')) {
                        continue;
                    }
                    $kept->push($existingByUser[$slot]);
                    unset($existingByUser[$slot]);
                }
                if (collect($kept)->contains(fn ($r) => (int) ($r['student']->id ?? 0) === $uid && (int) ($r['assessment']->id ?? 0) === $aid)) {
                    continue;
                }
                $row = [
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $seed,
                    'attempt' => null,
                    'attempt_number' => 1,
                    'attempt_total' => max(1, (int) ($assessment->max_attempts ?? 1)),
                ];
                try {
                    $row = $this->applySittingDisplayToRow($row);
                } catch (\Throwable $e) {
                    $row['sitting_display'] = [
                        'status' => 'Missed',
                        'appt_id' => $seed->displayCodeForStudent($uid),
                        'appointment' => method_exists($seed, 'appointmentStartLabel')
                            ? $seed->appointmentStartLabel()
                            : ($seed->windowStart()?->format('d/m/Y, H:i') ?: '—'),
                        'timezone' => $seed->timezoneLabel() ?: 'Asia/Dubai · GST',
                        'assessor' => 'Admin / course instructors',
                        'batch' => 'BAT-0004',
                        'created_by' => 'Admin',
                    ];
                }
                $kept->push($row);
                $added++;
            }
            foreach ($existingByUser as $r) {
                $kept->push($r);
            }
            $this->scsRosterNote .= ' · +' . $added . ' excl=' . $excluded->count() . ' total=' . $kept->filter(fn ($r) => (int) ($r['assessment']->id ?? 0) === $aid)->count();
            $rows = $kept->values();
        }

        $rows = $this->collapseDuplicateCimaScsRows($rows);
        $rows = $this->attachTakenAttemptsToExamRows($rows, $allAttempts ?? collect(), $assignments);
        $rows = collect($rows)->map(fn ($r) => $this->applySittingDisplayToRow($r))->values();

        return $rows;
    }

    /** One row per student per SCS test title (two Mock 1 records must not list the class twice). */
    protected function collapseDuplicateCimaScsRows($rows)
    {
        $other = collect();
        $best = [];
        foreach (collect($rows) as $r) {
            $assessment = $r['assessment'] ?? null;
            if (! $this->looksLikeCimaScsNovember($assessment)) {
                $other->push($r);
                continue;
            }
            $uid = (int) ($r['student']->id ?? 0);
            if ($uid <= 0) {
                $other->push($r);
                continue;
            }
            if ($this->excludedUserIdsForAssessment($assessment)->contains($uid)) {
                continue;
            }
            $key = $this->cimaScsCollapseKey($assessment) . ':' . $uid . ':' . $this->sittingKindKey($r['assignment'] ?? null);
            if (! isset($best[$key])) {
                $best[$key] = $r;
            } elseif ($this->cimaScsRowOutranks($r, $best[$key], $assessment)) {
                $keptAttempt = $best[$key]['attempt'] ?? null;
                $best[$key] = $r;
                if (empty($best[$key]['attempt']) && $keptAttempt) {
                    $best[$key]['attempt'] = $keptAttempt;
                }
            } elseif (empty($best[$key]['attempt']) && ! empty($r['attempt'])) {
                $best[$key]['attempt'] = $r['attempt'];
            }
        }

        return $other->concat(collect($best)->values())->values();
    }

    /** Admin and instructor lists: same sitting id as student My Tests. */
    protected function pinAssignedRowsToCanonicalSitting($rows)
    {
        $out = collect();
        $seen = [];
        foreach (collect($rows) as $r) {
            $student = $r['student'] ?? null;
            $assessment = $r['assessment'] ?? null;
            $isSelfRow = ! empty($r['assignment']) && method_exists($r['assignment'], 'isSelfSitting') && $r['assignment']->isSelfSitting();
            if (! $isSelfRow && $student && $assessment && method_exists($this->assessments, 'canonicalSittingForStudent')) {
                $canonical = $this->assessments->canonicalSittingForStudent($student, $assessment);
                if ($canonical) {
                    $r['assignment'] = $canonical;
                    $aid = (int) ($canonical->assessment_id ?? 0);
                    if ($aid > 0 && (int) ($assessment->id ?? 0) !== $aid) {
                        $found = LmsAssessment::with('course')->find($aid);
                        if ($found) {
                            $r['assessment'] = $found;
                            $assessment = $found;
                        }
                    }
                }
            }
            try {
                $r = $this->applySittingDisplayToRow($r);
            } catch (\Throwable $e) {
                // keep existing display
            }
            $uid = (int) ($r['student']->id ?? 0);
            $asgId = (int) ($r['assignment']->id ?? 0);
            $key = $this->cimaScsCollapseKey($r['assessment'] ?? $assessment) . ':' . $uid . ':' . $asgId;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out->push($r);
        }

        return $out->values();
    }

    protected function sittingKindKey($asg): string
    {
        if ($asg && method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) {
            return 'self';
        }

        return 'exam';
    }

    /** Mock 1 duplicate records (and Mock 2) collapse as one test, not separate titles. */
    protected function cimaScsCollapseKey($assessment): string
    {
        $title = mb_strtolower(trim((string) ($assessment->title ?? '')));
        if ($title === '') {
            return 'id:' . (int) ($assessment->id ?? 0);
        }
        if (preg_match('/mock\s*(\d+)/', $title, $m)) {
            $kind = (str_contains($title, 'mcs') || str_contains($title, 'management case')) ? 'mcs' : 'scs';

            return $kind . ':mock' . $m[1];
        }

        return $title !== '' ? $title : ('id:' . (int) ($assessment->id ?? 0));
    }

    /** After collapse, Attempt N of max — not leftover deleted sittings. */
    protected function renumberCollapsedAttempts($rows)
    {
        $grouped = collect($rows)->groupBy(function ($r) {
            $assessment = $r['assessment'] ?? null;
            $uid = (int) ($r['student']->id ?? 0);

            return $this->cimaScsCollapseKey($assessment) . ':' . $uid . ':' . $this->sittingKindKey($r['assignment'] ?? null);
        });
        $out = collect();
        foreach ($grouped as $group) {
            $ordered = $group->sortBy(fn ($r) => (int) ($r['assignment']->id ?? 0))->values();
            $max = max(1, (int) ($ordered->first()['assessment']->max_attempts ?? 1));
            foreach ($ordered as $i => $r) {
                $r['attempt_number'] = min($max, $i + 1);
                $r['attempt_total'] = $max;
                $out->push($r);
            }
        }

        return $out->values();
    }

    /** Keep the class sitting, or Yahia's own upcoming sitting, not extra unused copies. */
    protected function cimaScsRowOutranks(array $candidate, array $current, $assessment): bool
    {
        return $this->cimaScsRowScore($candidate, $assessment) > $this->cimaScsRowScore($current, $assessment);
    }

    protected function cimaScsRowScore(array $row, $assessment): int
    {
        $score = 0;
        if (! empty($row['attempt'])) {
            $score += 10000;
        }
        $student = $row['student'] ?? null;
        $asg = $row['assignment'] ?? null;
        $yahia = method_exists($this->assessments, 'isYahiaUser') && $this->assessments->isYahiaUser($student);
        $start = null;
        try {
            $start = $asg && method_exists($asg, 'windowStart') ? $asg->windowStart() : ($asg->available_from ?? null);
        } catch (\Throwable $e) {
            $start = $asg->available_from ?? null;
        }
        $future = false;
        try {
            $future = $start && now()->lt(\Carbon\Carbon::parse($start));
        } catch (\Throwable $e) {
            $future = false;
        }
        if ($yahia && $future) {
            $score += 5000;
        }
        if ($yahia && ! empty($asg->student_id) && (int) $asg->student_id === (int) ($student->id ?? 0)) {
            $score += 2000;
        }
        if ($start) {
            try {
                $ts = \Carbon\Carbon::parse($start)->timestamp;
                if ($yahia && $future) {
                    $score += (int) min(1000, $ts / 1000000);
                } else {
                    $score += (int) max(0, 2000000000 - $ts) / 100000;
                }
            } catch (\Throwable $e) {
                // keep base
            }
        }

        return $score;
    }

    /** Duplicate test titles (e.g. two Mock 1 rows) collapse to one dropdown option. */
    protected function uniqueAssessmentsByTitle($assessments, $assignments = null)
    {
        return collect($assessments)
            ->filter(fn ($a) => ! empty($a->id))
            ->groupBy(function ($a) {
                $title = mb_strtolower(trim((string) ($a->title ?? '')));

                return $title !== '' ? $title : ('id:' . (int) $a->id);
            })
            ->map(function ($group) use ($assignments) {
                if ($group->count() === 1) {
                    return $group->first();
                }

                return $group->sortByDesc(function ($a) use ($assignments) {
                    $n = collect($assignments)->where('assessment_id', (int) ($a->id ?? 0))->count();

                    return $n * 100000 + (int) ($a->id ?? 0);
                })->first();
            })
            ->filter()
            ->values();
    }

    protected function sameTitleAssessmentIds($assessment): array
    {
        $id = (int) ($assessment->id ?? 0);
        $title = mb_strtolower(trim((string) ($assessment->title ?? '')));
        if ($id <= 0) {
            return [];
        }
        if ($title === '') {
            return [$id];
        }
        try {
            $ids = LmsAssessment::query()->get(['id', 'title'])
                ->filter(fn ($row) => mb_strtolower(trim((string) ($row->title ?? ''))) === $title)
                ->pluck('id')
                ->map(fn ($rowId) => (int) $rowId)
                ->filter()
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            $ids = [$id];
        }

        return $ids === [] ? [$id] : $ids;
    }

    /** Original Mock 1 exam sitting (the one students already sat), not a later person assign. */
    protected function mock1RosterSeed($assignments, $assessment): ?LmsAssessmentAssignment
    {
        $cands = collect($assignments)->filter(function ($a) use ($assessment) {
            if ((int) ($a->assessment_id ?? 0) !== (int) ($assessment->id ?? 0)) {
                return false;
            }

            return ! (method_exists($a, 'isSelfSitting') && $a->isSelfSitting());
        })->values();
        $original = $cands->filter(function ($a) use ($assessment) {
            return ! (method_exists($this->assessments, 'isMock1FourthNovemberSitting')
                && $this->assessments->isMock1FourthNovemberSitting($a, $assessment));
        })->values();
        if ($original->isNotEmpty()) {
            $cands = $original;
        }
        if ($cands->isEmpty()) {
            return null;
        }
        $counts = collect();
        try {
            $counts = LmsAssessmentAttempt::query()
                ->whereIn('assignment_id', $cands->pluck('id')->all())
                ->selectRaw('assignment_id, COUNT(*) as n')
                ->groupBy('assignment_id')
                ->pluck('n', 'assignment_id');
        } catch (\Throwable $e) {
            $counts = collect();
        }

        return $cands->sortBy(function ($a) use ($counts) {
            $n = (int) ($counts[(int) $a->id] ?? $counts[(string) $a->id] ?? 0);
            $batch = ! empty($a->class_batch_id) ? 1 : 0;

            return sprintf('%04d-%d-%010d', 9999 - $n, 1 - $batch, (int) $a->id);
        })->first();
    }

    protected function mock1ExtraRosterStudents($seed, $assessment, array $rowsByKey)
    {
        $ids = collect();
        $batchId = (int) ($seed->class_batch_id ?? 0);
        if ($batchId <= 0) {
            $batch = $this->loadCimaScsNovemberBatch();
            $batchId = (int) ($batch->id ?? 0);
        }
        if ($batchId > 0) {
            try {
                $ids = $ids->merge(ClassBatch::studentUserIdsFor($batchId));
            } catch (\Throwable $e) {
                // keep other sources
            }
            try {
                $ids = $ids->merge(ClassBatch::membersOnBatchAtAssignment($batchId, $seed));
            } catch (\Throwable $e) {
                // keep other sources
            }
        }
        foreach ($rowsByKey as $r) {
            if ((int) ($r['assessment']->id ?? 0) === (int) ($assessment->id ?? 0)) {
                $ids->push((int) ($r['student']->id ?? 0));
            }
        }
        try {
            $ids = $ids->merge(
                LmsAssessmentAttempt::query()
                    ->where('assessment_id', (int) ($assessment->id ?? 0))
                    ->pluck('user_id')
            );
        } catch (\Throwable $e) {
            // keep other sources
        }
        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }
        $users = User::query()->whereIn('id', $ids->all())->get();
        $found = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($ids as $sid) {
            if (! in_array((int) $sid, $found, true)) {
                $users->push((object) [
                    'id' => (int) $sid,
                    'name' => 'User #' . (int) $sid,
                    'email' => '',
                ]);
            }
        }

        return $users;
    }

    /** Original Mock 1 sitting (BEXT72): full BAT-0004 roster including Yahia. Never a 4 Nov sitting. */
    protected function ensureMock1OriginalRoster(array &$rowsByKey, $assignments, $assessmentsById): void
    {
        $assessments = collect($rowsByKey)->pluck('assessment')->filter()
            ->concat(collect($assessmentsById))
            ->filter(fn ($row) => $this->isCimaScsAssignedMock($row))
            ->unique(fn ($row) => mb_strtolower(trim((string) ($row->title ?? ('id:' . (int) ($row->id ?? 0))))));
        foreach ($assessments as $assessment) {
            $aid = (int) ($assessment->id ?? 0);
            $cands = collect($assignments)->filter(fn ($a) => (int) ($a->assessment_id ?? 0) === $aid);
            $seed = $this->mock1RosterSeed($cands, $assessment);
            if (! $seed || empty($seed->id)) {
                $seed = collect($rowsByKey)->first(function ($r) use ($aid) {
                    return (int) ($r['assessment']->id ?? 0) === $aid && ! empty($r['assignment']->id)
                        && ! (method_exists($r['assignment'], 'isSelfSitting') && $r['assignment']->isSelfSitting());
                })['assignment'] ?? null;
            }
            if (! $seed || empty($seed->id)) {
                continue;
            }
            $roster = $this->mock1ExtraRosterStudents($seed, $assessment, $rowsByKey);
            $roster = $roster->unique(fn ($row) => (int) ($row->id ?? 0))
                ->filter(fn ($row) => (int) ($row->id ?? 0) > 0)
                ->values();
            $excluded = $this->excludedUserIdsForAssessment($assessment);
            $titleKey = mb_strtolower(trim((string) ($assessment->title ?? '')));
            foreach ($roster as $student) {
                $uid = (int) ($student->id ?? 0);
                if (method_exists($this->assessments, 'isYahiaUser') && $this->assessments->isYahiaUser($student)) {
                    continue;
                }
                if ($excluded->contains($uid)) {
                    continue;
                }
                $key = (int) $seed->id . ':' . $uid;
                if (isset($rowsByKey[$key])) {
                    continue;
                }
                $alreadyAttempted = collect($rowsByKey)->contains(function ($r) use ($aid, $uid) {
                    return (int) ($r['assessment']->id ?? 0) === $aid
                        && (int) ($r['student']->id ?? 0) === $uid
                        && ! empty($r['attempt']);
                });
                if ($alreadyAttempted) {
                    continue;
                }
                $rowsByKey[$key] = [
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $seed,
                    'attempt' => null,
                ];
            }
        }
    }

    /** After collapse, put BAT-0004 classmates back on CIMA SCS November mocks. */
    protected function forceCimaScsBatchRoster(array &$rowsByKey, $assignments, $assessmentsById, array &$batchStudentCache): void
    {
        $students = $this->loadCimaScsNovemberStudents();
        $byAssessment = collect($rowsByKey)->groupBy(fn ($r) => (int) ($r['assessment']->id ?? 0));
        $aids = $byAssessment->keys()->merge(
            collect($assignments)->pluck('assessment_id')->map(fn ($id) => (int) $id)
        )->unique()->filter();
        foreach ($aids as $aid) {
            $group = $byAssessment->get((int) $aid) ?? $byAssessment->get((string) $aid);
            $assessment = $assessmentsById->get((int) $aid)
                ?: $assessmentsById->get((string) $aid)
                ?: (is_iterable($group) ? (collect($group)->first()['assessment'] ?? null) : null);
            if (! $assessment || ! $this->looksLikeCimaScsNovember($assessment)) {
                continue;
            }
            $cands = collect($assignments)->filter(fn ($a) => (int) ($a->assessment_id ?? 0) === (int) $aid);
            $seed = ($assessment && method_exists($this->assessments, 'isCimaScsMock1') && $this->assessments->isCimaScsMock1($assessment))
                ? $this->mock1RosterSeed($cands, $assessment)
                : $cands->sortByDesc('id')->first();
            if (! $seed || empty($seed->id)) {
                $first = is_iterable($group) ? collect($group)->first() : null;
                $seed = $first['assignment'] ?? null;
            }
            if (! $seed || empty($seed->id)) {
                continue;
            }
            $batch = $this->loadCimaScsNovemberBatch();
            if ($batch && empty($seed->class_batch_id)) {
                $seed->class_batch_id = (int) $batch->id;
                $seed->setRelation('batch', $batch);
            }
            $isMock1 = $assessment && method_exists($this->assessments, 'isCimaScsMock1') && $this->assessments->isCimaScsMock1($assessment);
            $roster = $students;
            if ($isMock1) {
                $roster = $roster->concat($this->mock1ExtraRosterStudents($seed, $assessment, $rowsByKey))
                    ->unique(fn ($row) => (int) ($row->id ?? 0))
                    ->filter(fn ($row) => (int) ($row->id ?? 0) > 0)
                    ->values();
            }
            if ($roster->isEmpty()) {
                continue;
            }
            $excluded = $this->excludedUserIdsForAssessment($assessment);
            foreach ($roster as $student) {
                $uid = (int) ($student->id ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                if ($isMock1 && method_exists($this->assessments, 'isYahiaUser') && $this->assessments->isYahiaUser($student)) {
                    continue;
                }
                if ($excluded->contains($uid)) {
                    continue;
                }
                $key = (int) $seed->id . ':' . $uid;
                if (isset($rowsByKey[$key])) {
                    continue;
                }
                if ($assessment && method_exists($this->assessments, 'isCimaScsMock1') && $this->assessments->isCimaScsMock1($assessment)) {
                    $alreadyAttempted = collect($rowsByKey)->contains(function ($r) use ($aid, $uid) {
                        return (int) ($r['assessment']->id ?? 0) === (int) $aid
                            && (int) ($r['student']->id ?? 0) === $uid
                            && ! empty($r['attempt']);
                    });
                    if ($alreadyAttempted) {
                        continue;
                    }
                }
                $rowsByKey[$key] = [
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $seed,
                    'attempt' => null,
                ];
            }
        }
    }

    /** Add people saved on each sitting's assign snapshot (not today's batch). */
    protected function fillMissingBatchMates(array &$rowsByKey, $assignments, $assessmentsById, array &$batchStudentCache): void
    {
        foreach (collect($assignments) as $assignment) {
            if (empty($assignment->id)) {
                continue;
            }
            $assessment = $assignment->assessment
                ?? $assessmentsById->get((int) ($assignment->assessment_id ?? 0));
            if (! $assessment) {
                continue;
            }
            if (method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting()) {
                continue;
            }
            $snap = ClassBatch::studentUserIdsForSitting($assignment);
            if ($this->looksLikeCimaScsNovember($assessment) && ! $this->isCimaScsAssignedMock($assessment)) {
                continue;
            }
            if ($this->isCimaScsAssignedMock($assessment)) {
                if (method_exists($assignment, 'isSelfSitting') && $assignment->isSelfSitting()) {
                    continue;
                }
                $snap = collect($assignment->roster_user_ids ?? []);
                if (! empty($assignment->student_id)) {
                    $snap->push((int) $assignment->student_id);
                }
                if ($snap->filter()->isEmpty() && ! empty($assignment->class_batch_id)) {
                    $snap = ClassBatch::membersOnBatchAtAssignment((int) $assignment->class_batch_id, $assignment);
                }
            } elseif ($this->isMock1PersonSitting($assignment, $assessment)) {
                continue;
            }
            if ($this->isCimaCohortSitting($assessment, $assignment, collect())) {
                $snap = $snap->merge($this->cimaCohortUsers()->pluck('id'))->map(fn ($id) => (int) $id)->unique()->filter()->values();
            }
            if ($snap->isEmpty() && ! empty($assignment->class_batch_id)) {
                $snap = ClassBatch::membersOnBatchAtAssignment((int) $assignment->class_batch_id, $assignment);
            }
            if ($snap->isEmpty()) {
                continue;
            }
            $users = User::query()->whereIn('id', $snap->all())->get()->keyBy('id');
            foreach ($snap as $uid) {
                $key = (int) $assignment->id . ':' . $uid;
                if (isset($rowsByKey[$key])) {
                    continue;
                }
                $student = $users->get($uid) ?: (object) [
                    'id' => $uid,
                    'name' => 'User #' . $uid,
                    'email' => '',
                ];
                if ($this->isRemovedTestTaker($student, $assessment)) {
                    continue;
                }
                $rowsByKey[$key] = [
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $assignment,
                    'attempt' => null,
                ];
            }
        }
    }

    /** When the batch pivot is empty, list every student on that course's batches and enrollments. */
    protected function courseRosterFallback($assessment, $assignment)
    {
        $users = collect();
        $courseId = (int) ($assessment->course_id ?? 0);
        if ($courseId <= 0 && $assignment && ! empty($assignment->class_batch_id)) {
            $batch = $assignment->relationLoaded('batch') ? $assignment->batch : ClassBatch::query()->find((int) $assignment->class_batch_id);
            $courseId = (int) ($batch->course_id ?? 0);
        }
        if ($courseId <= 0) {
            return $users;
        }
        try {
            $batchIds = ClassBatch::query()->where('course_id', $courseId)->pluck('id');
            foreach ($batchIds as $bid) {
                $users = $users->concat($this->studentsForBatchId((int) $bid));
            }
        } catch (\Throwable $e) {
            report($e);
        }
        try {
            $users = $users->concat($this->lms->studentsForCourse($courseId, 500));
        } catch (\Throwable $e) {
            report($e);
        }

        return $users->unique(fn ($row) => (int) ($row->id ?? 0))->filter(fn ($row) => (int) ($row->id ?? 0) > 0)->values();
    }

    protected function batchIdsForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }
        $ids = collect();
        if (Schema::hasTable('class_batch_student')) {
            if (Schema::hasColumn('class_batch_student', 'student_id')) {
                $ids = $ids->merge(DB::table('class_batch_student')->where('student_id', $userId)->pluck('class_batch_id'));
            }
            if (Schema::hasColumn('class_batch_student', 'user_id')) {
                $ids = $ids->merge(DB::table('class_batch_student')->where('user_id', $userId)->pluck('class_batch_id'));
            }
        }
        return $ids->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
    }

    /** One selected student → assign every classmate on that batch (snapshot at save). */
    protected function batchIdsForStudents(array $studentIds, LmsAssessment $assessment): array
    {
        $found = collect();
        $courseId = (int) ($assessment->course_id ?? 0);
        $scs = $this->looksLikeCimaScsNovember($assessment);
        foreach ($studentIds as $uid) {
            $uid = (int) $uid;
            $batchIds = $this->batchIdsForUser($uid);
            if ($batchIds === []) {
                continue;
            }
            $batches = ClassBatch::query()->whereIn('id', $batchIds)->get();
            $batches = $batches->filter(function ($batch) {
                $hay = mb_strtolower(trim((string) ($batch->code ?? '') . ' ' . (string) ($batch->name ?? '')));

                return ! str_contains($hay, 'masterclass');
            });
            $matched = $courseId > 0
                ? $batches->filter(fn ($batch) => (int) ($batch->course_id ?? 0) === $courseId)
                : collect();
            if ($scs) {
                $scsBatches = $batches->filter(function ($batch) {
                    $hay = mb_strtolower(trim((string) ($batch->code ?? '') . ' ' . (string) ($batch->name ?? '')));

                    return str_contains($hay, 'bat-0004')
                        || str_contains($hay, 'scs')
                        || str_contains($hay, 'strategic')
                        || str_contains($hay, 'november');
                });
                if ($scsBatches->isNotEmpty()) {
                    $matched = $scsBatches;
                }
            }
            $pick = $matched->isNotEmpty() ? $matched : $batches;
            $found = $found->merge($pick->pluck('id'));
        }

        return $found->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
    }

    protected function sittingForCohort($assessment, $assignments): ?LmsAssessmentAssignment
    {
        $aids = collect([(int) ($assessment->id ?? 0)])->filter()->values();
        try {
            $more = LmsAssessment::withTrashed()
                ->where(function ($q) {
                    $q->where('title', 'like', '%CIMA MCS%')
                        ->orWhere('title', 'like', '%Management Case Study%');
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id);
            $aids = $aids->merge($more)->unique()->filter()->values();
        } catch (\Throwable $e) {
            // keep assessment id only
        }
        $cands = collect($assignments)->filter(
            fn ($row) => $aids->contains((int) ($row->assessment_id ?? 0))
                && ! (method_exists($row, 'isSelfSitting') && $row->isSelfSitting())
        )->values();
        if ($cands->isEmpty() && $aids->isNotEmpty()) {
            try {
                $cands = LmsAssessmentAssignment::query()->whereIn('assessment_id', $aids->all())->orderBy('id')->get()
                    ->filter(fn ($row) => ! (method_exists($row, 'isSelfSitting') && $row->isSelfSitting()))
                    ->values();
            } catch (\Throwable $e) {
                $cands = collect();
            }
        }
        $picked = $cands->first(fn ($row) => ! empty($row->class_batch_id) && ! empty($row->available_from))
            ?: $cands->first(fn ($row) => ! empty($row->available_from))
            ?: $cands->first(fn ($row) => ! empty($row->class_batch_id))
            ?: $cands->first(fn ($row) => ! empty($row->marker_user_id))
            ?: $cands->sortBy('id')->first();

        return $picked ?: null;
    }

    protected function ensureCimaCohortAssignment($assessment): ?LmsAssessmentAssignment
    {
        if (! $assessment) {
            return null;
        }
        try {
            $foundRows = LmsAssessmentAssignment::query()
                ->where('assessment_id', (int) $assessment->id)
                ->orderBy('id')
                ->get();
            $found = $foundRows->first(fn ($row) => ! (method_exists($row, 'isSelfSitting') && $row->isSelfSitting()) && ! empty($row->available_from))
                ?: $foundRows->first(fn ($row) => ! (method_exists($row, 'isSelfSitting') && $row->isSelfSitting()))
                ?: $foundRows->first();
            if ($found) {
                $this->hydrateSittingDisplay($found, $assessment);

                return $found;
            }
            $batch = ClassBatch::query()->where('code', 'BAT-0003')->first();
            $asg = new LmsAssessmentAssignment();
            $asg->assessment_id = (int) $assessment->id;
            if (Schema::hasColumn($asg->getTable(), 'class_batch_id') && $batch) {
                $asg->class_batch_id = (int) $batch->id;
            }
            if (Schema::hasColumn($asg->getTable(), 'timezone')) {
                $asg->timezone = 'Asia/Dubai';
            }
            if (Schema::hasColumn($asg->getTable(), 'sitting_kind')) {
                $asg->sitting_kind = LmsAssessmentAssignment::KIND_EXAM;
            }
            if (Schema::hasColumn($asg->getTable(), 'available_from')) {
                $fromLocal = \Carbon\Carbon::parse('2026-10-26 20:30:00', 'Asia/Dubai');
                $asg->available_from = $fromLocal->copy()->timezone(config('app.timezone', 'Asia/Dubai'))->format('Y-m-d H:i:s');
                $mins = (int) ($assessment->durationMinutes() ?? 180);
                if ($mins > 0 && Schema::hasColumn($asg->getTable(), 'available_until')) {
                    $asg->available_until = $fromLocal->copy()->addMinutes($mins)
                        ->timezone(config('app.timezone', 'Asia/Dubai'))
                        ->format('Y-m-d H:i:s');
                }
            }
            $asg->save();
            $this->hydrateSittingDisplay($asg, $assessment);

            return $asg;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    protected function assignedRowMatchesSearch(array $r, string $search): bool
    {
        $needle = mb_strtolower(trim($search));
        if ($needle === '') {
            return true;
        }
        $disp = $r['sitting_display'] ?? [];
        $student = $r['student'] ?? null;
        $assessment = $r['assessment'] ?? null;
        $assignment = $r['assignment'] ?? null;
        $attempt = $r['attempt'] ?? null;
        $hay = mb_strtolower(trim(implode(' ', [
            (string) ($student->name ?? ''),
            (string) ($student->email ?? ''),
            (string) ($student->id ?? ''),
            (string) ($assessment->title ?? ''),
            (string) (optional(optional($assessment)->course)->title ?? ''),
            ($assessment && method_exists($assessment, 'displayCode')) ? (string) $assessment->displayCode() : '',
            (string) ($disp['appt_id'] ?? ''),
            (string) ($disp['appointment'] ?? ''),
            (string) ($disp['assessor'] ?? ''),
            (string) ($disp['batch'] ?? ''),
            (string) ($disp['status'] ?? ''),
            (string) (optional(optional($assignment)->batch)->code ?? ''),
            (string) (optional(optional($assignment)->batch)->name ?? ''),
            (string) ($assignment->id ?? ''),
            $assignment ? ('bext' . (int) $assignment->id) : '',
            (string) ($assessment->id ?? ''),
            (string) ($attempt->id ?? ''),
        ])));
        $hay = preg_replace('/\s+/', ' ', $hay) ?: '';
        if (str_contains($hay, $needle)) {
            return true;
        }
        $tokens = preg_split('/\s+/', $needle) ?: [];
        if (count($tokens) > 1) {
            foreach ($tokens as $token) {
                if ($token === '' || ! str_contains($hay, $token)) {
                    return false;
                }
            }

            return true;
        }
        if (ctype_digit($search)) {
            $n = (int) $search;

            return $n === (int) ($assessment->id ?? 0)
                || $n === (int) ($student->id ?? 0)
                || $n === (int) ($assignment->id ?? 0)
                || $n === (int) ($attempt->id ?? 0);
        }

        return false;
    }

    /** Put display + any started attempt back on the row so In progress is never painted Missed. */
    protected function applySittingDisplayToRow(array $r): array
    {
        $disp = $this->sittingDisplayForRow($r);
        if (! empty($disp['_assignment'])) {
            $r['assignment'] = $disp['_assignment'];
        }
        if (! empty($disp['_attempt'])) {
            $r['attempt'] = $disp['_attempt'];
        }
        unset($disp['_assignment'], $disp['_attempt']);
        $r['sitting_display'] = $disp;

        return $r;
    }

    /** Exam sitting with a started attempt keeps that attempt. Missed is only when nobody started. */
    protected function startedExamAttemptForRow(array $row): ?LmsAssessmentAttempt
    {
        $uid = (int) ($row['student']->id ?? 0);
        $assessment = $row['assessment'] ?? null;
        $asg = $row['assignment'] ?? null;
        if ($uid <= 0 || ! $assessment) {
            return null;
        }
        if ($asg && method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) {
            return null;
        }
        try {
            $aids = $this->sameTitleAssessmentIds($assessment);
            if ($aids === []) {
                $aids = [(int) $assessment->id];
            }
            $q = LmsAssessmentAttempt::query()
                ->whereIn('assessment_id', $aids)
                ->where('user_id', $uid)
                ->where(function ($q) {
                    $q->whereNotNull('started_at')
                        ->orWhereIn('status', ['in_progress', 'submitted', 'graded', 'expired']);
                })
                ->orderByDesc('id');
            $selfIds = LmsAssessmentAssignment::query()
                ->whereIn('assessment_id', $aids)
                ->get()
                ->filter(fn ($a) => method_exists($a, 'isSelfSitting') && $a->isSelfSitting())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();
            if ($selfIds !== []) {
                $q->where(function ($q) use ($selfIds) {
                    $q->whereNull('assignment_id')->orWhereNotIn('assignment_id', $selfIds);
                });
            }

            return $q->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function sittingDisplayForRow(array $row): array
    {
        $asg = $row['assignment'] ?? null;
        $assessment = $row['assessment'] ?? null;
        $attempt = $row['attempt'] ?? $this->startedExamAttemptForRow($row);
        if ($this->isCimaCohortSitting($assessment, $asg, collect([$row['student'] ?? null])) && ! $asg && $assessment) {
            $asg = $this->ensureCimaCohortAssignment($assessment);
            $row['assignment'] = $asg;
        }
        $this->hydrateSittingDisplay($asg, $assessment);
        if ($asg && method_exists($this->assessments, 'alignSittingWithExistingExamRule')) {
            $this->assessments->alignSittingWithExistingExamRule($asg, $assessment);
        }
        $studentId = (int) ($row['student']->id ?? 0);
        $apptId = ($asg && ! empty($asg->id))
            ? $asg->displayCodeForStudent($studentId)
            : null;
        if (! $apptId && ! empty($attempt?->assignment_id)) {
            $apptId = 'BEXT' . (int) $attempt->assignment_id . ($studentId > 0 ? ('-' . $studentId) : '');
        }
        $start = $asg && method_exists($asg, 'appointmentStartLabel')
            ? $asg->appointmentStartLabel()
            : ($asg && method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()
                ? 'Start anytime'
                : ($asg?->windowStart()?->format('d/m/Y, H:i') ?: null));
        $assessor = $asg && method_exists($asg, 'assessorDisplayName')
            ? $asg->assessorDisplayName()
            : ($asg?->marker?->name ?: null);
        $assessorId = $asg?->marker_user_id ?? ($asg?->marker?->id ?? null);
        if ($this->assessorLooksBlank($assessor)) {
            $namedMarker = $this->namedAssessorForAssessment($assessment, $asg, $attempt);
            if ($namedMarker) {
                $assessor = $namedMarker->name;
                $assessorId = (int) $namedMarker->id;
            }
        }
        $created = $asg?->createdByLabel() ?: null;
        $tz = $asg?->timezoneLabel() ?: null;
        $isCohort = $this->isCimaCohortSitting($assessment, $asg, collect([$row['student'] ?? null]));
        $isMock1 = $assessment && method_exists($this->assessments, 'isCimaScsMock1')
            && $this->assessments->isCimaScsMock1($assessment);
        $isMcs1 = $assessment && method_exists($this->assessments, 'isCimaMcsMock1')
            && $this->assessments->isCimaMcsMock1($assessment);
        $isSelf = $asg && method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting();
        if ($isMcs1 && $asg) {
            if (Schema::hasColumn($asg->getTable(), 'sitting_kind')) {
                $asg->sitting_kind = \App\Models\LmsAssessmentAssignment::KIND_EXAM;
            }
            $asg->applyMissingExamWindow('2026-10-26 20:30:00', 'Asia/Dubai', 180);
            $isSelf = false;
        }
        if ($isMock1 && ! $isSelf && $asg) {
            $asg->applyMissingExamWindow('2026-10-30 20:15:00', 'Asia/Dubai', 180);
        }
        if ($asg && ! $isSelf) {
            $start = method_exists($asg, 'appointmentStartLabel')
                ? $asg->appointmentStartLabel()
                : ($asg->windowStart()?->format('d/m/Y, H:i') ?: $start);
            $tz = method_exists($asg, 'timezoneLabel') ? ($asg->timezoneLabel() ?: $tz) : $tz;
        }
        if ($isCohort && ! $isMock1) {
            $apptId = ($apptId && $apptId !== '—' && $apptId !== 'BEXT0') ? $apptId : (($asg && ! empty($asg->id)) ? $asg->displayCodeForStudent($studentId) : $apptId);
            if ($asg && ! $asg->isSelfSitting()) {
                $asg->applyMissingExamWindow('2026-10-26 20:30:00', 'Asia/Dubai', 180);
            }
            $start = $asg && method_exists($asg, 'appointmentStartLabel')
                ? $asg->appointmentStartLabel()
                : ($asg?->windowStart()?->format('d/m/Y, H:i') ?: '26/10/2026, 20:30');
            if ($start === 'Open (no appointment set)' || $start === 'Start anytime' || $start === '—') {
                $start = $asg?->windowStart()?->format('d/m/Y, H:i') ?: '26/10/2026, 20:30';
            }
            $tz = ($tz && $tz !== '') ? $tz : 'Asia/Dubai · GST';
            $created = ($created && $created !== '') ? $created : 'Admin';
        }
        $status = 'Assigned';
        if ($isSelf) {
            $start = $asg && method_exists($asg, 'appointmentStartLabel')
                ? $asg->appointmentStartLabel()
                : 'Open to start the test';
            $tz = '';
        } elseif ($this->appointmentLooksBlank($start) && $assessment && ! empty($assessment->id) && ! $isMock1 && ! $isSelf) {
            try {
                $started = LmsAssessmentAttempt::query()
                    ->where('assessment_id', (int) $assessment->id)
                    ->whereNotNull('started_at')
                    ->orderBy('started_at')
                    ->first();
                if ($started && $started->started_at) {
                    $start = $asg && method_exists($asg, 'formatInTimezone')
                        ? $asg->formatInTimezone($started->started_at)
                        : $started->started_at->format('d/m/Y, H:i');
                }
            } catch (\Throwable $e) {
                // keep blank
            }
        }
        if ($attempt) {
            $status = $attempt->statusLabel();
        } else {
            $status = $this->unusedSittingStatus($asg, $assessment, $isCohort, $row['student'] ?? null);
        }

        return [
            'appt_id' => $apptId ?: '—',
            'appointment' => $start ?: '—',
            'timezone' => $tz ?: (string) config('app.timezone', 'Asia/Dubai'),
            '_attempt' => $attempt,
            'created_by' => $created ?: 'Admin',
            'assessor' => $assessor ?: 'Admin / course instructors',
            'assessor_id' => $assessorId,
            'batch' => $asg?->batch?->code ?: ($isMock1 ? 'BAT-0004' : ($isCohort ? 'BAT-0003' : null)),
            'course' => $assessment?->course?->title ?? ($isCohort ? 'CIMA Management Case Study' : null),
            'status' => $status,
            '_assignment' => $asg,
        ];
    }

    protected function hydrateSittingDisplay($asg, $assessment): void
    {
        if (! $asg instanceof LmsAssessmentAssignment) {
            return;
        }
        if ((! $asg->relationLoaded('batch') || ! $asg->batch) && ! empty($asg->class_batch_id)) {
            $batch = ClassBatch::query()->find($asg->class_batch_id);
            if ($batch) {
                $asg->setRelation('batch', $batch);
            }
        }
        if ((! $asg->relationLoaded('marker') || ! $asg->marker) && ! empty($asg->marker_user_id)) {
            $marker = User::query()->find($asg->marker_user_id);
            if ($marker) {
                $asg->setRelation('marker', $marker);
            }
        }
        if (trim((string) ($asg->timezone ?? '')) === '') {
            $asg->timezone = 'Asia/Dubai';
        }
        if (! $asg->relationLoaded('assignedByUser') && ! empty($asg->assigned_by_user_id)) {
            $creator = User::query()->find($asg->assigned_by_user_id);
            if ($creator) {
                $asg->setRelation('assignedByUser', $creator);
            }
        }
        if ($assessment && ! $assessment->relationLoaded('course') && ! empty($assessment->course_id)) {
            $assessment->loadMissing('course');
        }
        if ($assessment) {
            $asg->setRelation('assessment', $assessment);
        }
    }

    /**
     * One line per sitting (assignment + student). The same attempt must not appear twice.
     * A later Assign again sitting (different BEXT) is kept.
     */
    protected function dedupeAssignedSittingRows($rows)
    {
        $wasKeyedArray = is_array($rows);
        $bestBySitting = [];
        $sittingOfAttempt = [];
        foreach (collect($rows) as $r) {
            if (! is_array($r)) {
                continue;
            }
            $uid = (int) ($r['student']->id ?? 0);
            $asgId = (int) ($r['assignment']->id ?? 0);
            $attId = (int) ($r['attempt']->id ?? 0);
            $kind = $this->sittingKindKey($r['assignment'] ?? null);
            $testKey = $this->cimaScsCollapseKey($r['assessment'] ?? null);
            $sittingKey = $testKey . ':' . $uid . ':' . $kind . ':' . $asgId;
            if ($asgId <= 0) {
                $sittingKey = $testKey . ':' . $uid . ':' . $kind . ':att:' . max(0, $attId);
            }
            if (! isset($bestBySitting[$sittingKey]) || $this->sittingRowBeats($r, $bestBySitting[$sittingKey])) {
                $bestBySitting[$sittingKey] = $r;
            }
            if ($attId > 0) {
                if (! isset($sittingOfAttempt[$attId]) || $this->sittingRowBeats($r, $bestBySitting[$sittingOfAttempt[$attId]] ?? [])) {
                    $sittingOfAttempt[$attId] = $sittingKey;
                }
            }
        }
        $usedAttempts = [];
        $out = [];
        foreach ($bestBySitting as $sittingKey => $r) {
            $attId = (int) ($r['attempt']->id ?? 0);
            if ($attId > 0) {
                if (isset($usedAttempts[$attId])) {
                    continue;
                }
                if (($sittingOfAttempt[$attId] ?? $sittingKey) !== $sittingKey) {
                    continue;
                }
                $usedAttempts[$attId] = true;
            }
            $asgId = (int) ($r['assignment']->id ?? 0);
            $uid = (int) ($r['student']->id ?? 0);
            $key = $asgId > 0 ? ($asgId . ':' . $uid) : ('attempt:' . $attId);
            $out[$key] = $r;
        }
        if ($wasKeyedArray) {
            return $out;
        }

        return collect(array_values($out));
    }

    protected function sittingRowBeats(array $candidate, array $current): bool
    {
        $cAtt = ! empty($candidate['attempt']);
        $kAtt = ! empty($current['attempt']);
        if ($cAtt !== $kAtt) {
            return $cAtt;
        }
        $cId = (int) ($candidate['attempt']->id ?? 0);
        $kId = (int) ($current['attempt']->id ?? 0);
        if ($cId !== $kId) {
            return $cId > $kId;
        }

        return (int) ($candidate['assignment']->id ?? 0) <= (int) ($current['assignment']->id ?? 0);
    }

    /** Keep every exam sitting and every attempt. Only drop extra unused self-test duplicates. */
    protected function collapseUnusedRetakeRows(array $rowsByKey): array
    {
        $groups = [];
        foreach ($rowsByKey as $k => $r) {
            $pair = (int) ($r['assessment']->id ?? 0) . ':' . (int) ($r['student']->id ?? 0);
            $groups[$pair][$k] = $r;
        }
        $out = [];
        foreach ($groups as $rows) {
            $cima = false;
            foreach ($rows as $r) {
                if ($this->isCimaCohortSitting($r['assessment'] ?? null, $r['assignment'] ?? null, collect([$r['student'] ?? null]))) {
                    $cima = true;
                    break;
                }
            }
            if ($cima) {
                foreach ($rows as $k => $r) {
                    $out[$k] = $r;
                }
                continue;
            }
            $used = [];
            $unusedExam = [];
            $unusedOther = [];
            foreach ($rows as $k => $r) {
                $asg = $r['assignment'] ?? null;
                $asgId = (int) ($asg->id ?? 0);
                if (! empty($r['attempt'])) {
                    $used[$k] = $r;
                    continue;
                }
                $isSelf = $asg && method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting();
                $examWindow = $asg && (! empty($asg->class_batch_id) || (method_exists($asg, 'windowStart') && $asg->windowStart()));
                $title = mb_strtolower(trim((string) ($r['assessment']->title ?? '')));
                $cimaCase = str_contains($title, 'mcs')
                    || str_contains($title, 'scs')
                    || str_contains($title, 'management case')
                    || str_contains($title, 'strategic case');
                if ($asgId > 0 && (! $isSelf || $examWindow || $cimaCase)) {
                    $unusedExam[$k] = $r;
                } else {
                    $unusedOther[$k] = $r;
                }
            }
            foreach ($unusedExam as $k => $r) {
                $out[$k] = $r;
            }
            foreach ($used as $k => $r) {
                $out[$k] = $r;
            }
            if ($used === [] && $unusedExam === []) {
                $first = array_key_first($unusedOther);
                if ($first !== null) {
                    $out[$first] = $unusedOther[$first];
                }
            }
        }

        return $out;
    }

    protected function keepFirstCimaCohortAttempt(array $rowsByKey): array
    {
        $picked = [];
        $out = [];
        foreach ($rowsByKey as $k => $r) {
            $asg = $r['assignment'] ?? null;
            $assessment = $r['assessment'] ?? null;
            if (! $this->isCimaCohortSitting($assessment, $asg, collect([$r['student'] ?? null]))) {
                $out[$k] = $r;
                continue;
            }
            $pair = (int) ($assessment->id ?? 0) . ':' . (int) ($r['student']->id ?? 0);
            if (! isset($picked[$pair]) || $this->cimaRowBeats($r, $rowsByKey[$picked[$pair]] ?? [])) {
                $picked[$pair] = $k;
            }
        }
        foreach ($picked as $k) {
            $max = max(1, (int) ($rowsByKey[$k]['assessment']->max_attempts ?? 1));
            $rowsByKey[$k]['attempt_number'] = (int) ($rowsByKey[$k]['attempt_number'] ?? 1);
            $rowsByKey[$k]['attempt_total'] = max((int) ($rowsByKey[$k]['attempt_total'] ?? 1), $max);
            $out[$k] = $rowsByKey[$k];
        }

        return $out;
    }

    protected function cimaRowBeats(array $candidate, array $current): bool
    {
        $candHas = ! empty($candidate['attempt']);
        $curHas = ! empty($current['attempt']);
        if ($candHas !== $curHas) {
            return $candHas;
        }
        $candId = (int) ($candidate['assignment']->id ?? 0);
        $curId = (int) ($current['assignment']->id ?? 0);
        if ($candId === 0 && $curId > 0) {
            return false;
        }
        if ($curId === 0 && $candId > 0) {
            return true;
        }
        if ($candId !== $curId) {
            return $candId > 0 && ($curId === 0 || $candId < $curId);
        }
        $candNo = (int) ($candidate['attempt_number'] ?? 1);
        $curNo = (int) ($current['attempt_number'] ?? 1);

        return $candNo < $curNo;
    }

    protected function isCimaCohortSitting($assessment, $assignment, $students): bool
    {
        if (! $assessment) {
            return false;
        }
        $title = mb_strtolower(trim((string) ($assessment->title ?? '')));
        $course = '';
        try {
            $course = mb_strtolower(trim((string) ($assessment->course->title ?? '')));
        } catch (\Throwable $e) {
            $course = '';
        }
        $batchCode = mb_strtolower(trim((string) ($assignment?->batch?->code ?? '')));
        $hay = $title . ' ' . $course . ' ' . $batchCode;
        return str_contains($hay, 'bat-0003')
            || str_contains($hay, 'management case')
            || str_contains($hay, 'cima mcs');
    }

    protected function cimaCohortUsers()
    {
        $emails = [
            'k_vinu83@yahoo.com',
            'kartik.chaturvedi469@gmail.com',
            'jahanps@gmail.com',
            'osamaym@hotmail.com',
            'vananthan1@gmail.com',
        ];
        try {
            return User::query()
                ->where(function ($q) use ($emails) {
                    $q->whereIn('email', $emails);
                    foreach ($emails as $email) {
                        $q->orWhereRaw('LOWER(email) = ?', [$email]);
                    }
                })
                ->get(['id', 'name', 'email']);
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function studentOnSitting($student, $assignment = null, $assessment = null): bool
    {
        if (! $student) {
            return false;
        }
        $uid = (int) ($student->id ?? 0);
        if ($uid <= 0) {
            return false;
        }
        if ($this->isRemovedTestTaker($student, $assessment)) {
            return false;
        }
        try {
            if ($assignment && Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
                $ex = collect($assignment->excluded_user_ids ?? [])->map(fn ($id) => (int) $id);
                if ($ex->contains($uid)) {
                    return false;
                }
            }
        } catch (\Throwable $e) {
            // keep named student
        }

        return true;
    }

    /** Hide Imthiyas only on the CIMA MCS sitting, not on other batch tests. */
    protected function isRemovedTestTaker($student, $assessment = null): bool
    {
        if (! $student) {
            return false;
        }
        $uid = (int) ($student->id ?? 0);
        $name = mb_strtolower(trim((string) ($student->name ?? '')));
        $email = mb_strtolower(trim((string) ($student->email ?? '')));
        if (! str_contains($name, 'imthiyas') && ! str_contains($email, 'imthiyas')) {
            return false;
        }
        $hay = mb_strtolower(trim((string) ($assessment->title ?? '') . ' ' . (string) ($assessment->course->title ?? '')));

        return str_contains($hay, 'cima mcs') || str_contains($hay, 'management case');
    }

    protected function excludedUserIdsForAssessment($assessment)
    {
        $aid = (int) ($assessment->id ?? 0);
        if ($aid <= 0 || ! Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
            return collect();
        }
        $cacheKey = mb_strtolower(trim((string) ($assessment->title ?? ''))) ?: ('id:' . $aid);
        if (! isset($this->excludedTakersByAssessment[$cacheKey])) {
            $ids = collect();
            $aids = $this->sameTitleAssessmentIds($assessment);
            try {
                $rows = LmsAssessmentAssignment::query()
                    ->whereIn('assessment_id', $aids)
                    ->get(['id', 'excluded_user_ids']);
                foreach ($rows as $row) {
                    $ids = $ids->merge(collect($row->excluded_user_ids ?? []));
                }
            } catch (\Throwable $e) {
                $ids = collect();
            }
            $this->excludedTakersByAssessment[$cacheKey] = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
        }

        return $this->excludedTakersByAssessment[$cacheKey];
    }

    protected function assignmentForAttempt($att, $assignments, $assignmentsById): ?LmsAssessmentAssignment
    {
        if (! empty($att->assignment_id)) {
            $found = $assignmentsById->get((int) $att->assignment_id)
                ?: LmsAssessmentAssignment::query()->find($att->assignment_id);
            if ($found) {
                return $found;
            }
        }
        $ownSittings = LmsAssessmentAssignment::query()
            ->where('assessment_id', (int) $att->assessment_id)
            ->where('student_id', (int) $att->user_id)
            ->orderBy('id')
            ->get();
        $fromStudent = $ownSittings->first(
            fn ($row) => $this->assessments->attemptBelongsToAssignment($att, $row)
        );
        if ($fromStudent) {
            return $fromStudent;
        }
        $belong = collect($assignments)
            ->filter(fn ($row) => (int) ($row->assessment_id ?? 0) === (int) $att->assessment_id)
            ->sortBy(fn ($row) => (int) $row->id)
            ->first(fn ($row) => $this->assessments->attemptBelongsToAssignment($att, $row));
        if ($belong) {
            return $belong;
        }
        if (Schema::hasTable('class_batch_student')) {
            $batchIds = DB::table('class_batch_student')
                ->where('student_id', (int) $att->user_id)
                ->pluck('class_batch_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            if ($batchIds !== []) {
                $fromBatch = LmsAssessmentAssignment::query()
                    ->where('assessment_id', (int) $att->assessment_id)
                    ->whereIn('class_batch_id', $batchIds)
                    ->orderBy('id')
                    ->get()
                    ->first(fn ($row) => $this->assessments->attemptBelongsToAssignment($att, $row));
                if ($fromBatch) {
                    return $fromBatch;
                }
            }
        }

        return null;
    }

    protected function matchSittingAttempt($candidates, ?LmsAssessmentAssignment $asg, array &$claimedAttemptIds)
    {
        $belonging = collect($candidates)->filter(function ($att) use ($asg, $claimedAttemptIds) {
            return ! isset($claimedAttemptIds[(int) $att->id])
                && $this->assessments->attemptBelongsToAssignment($att, $asg);
        });
        $matched = $asg && $asg->isSelfSitting()
            ? $belonging->sortByDesc('id')->first()
            : $belonging->first();
        if ($matched) {
            $claimedAttemptIds[(int) $matched->id] = true;
        }

        return $matched;
    }

    protected function syncAssignments(LmsAssessment $a, Request $request): void
    {
        // Each Save = new assignment sitting(s) for this same test. Never delete older sittings.
        $tzOptions = LmsAssessmentAssignment::timezoneOptions();
        $timezone = trim((string) $request->input('timezone', ''));
        if ($timezone === '' || ! array_key_exists($timezone, $tzOptions)) {
            $timezone = (string) config('app.timezone', 'Asia/Dubai');
        }

        $sittingKind = $request->input('sitting_kind') === 'self' ? 'self' : 'exam';
        $hasKindCol = Schema::hasColumn('lms_assessment_assignments', 'sitting_kind');
        $from = null;
        $until = null;
        if ($sittingKind !== 'self' && $request->filled('available_from')) {
            // Wall-clock time is in the chosen timezone (not the browser TZ).
            $fromLocal = \Carbon\Carbon::parse($request->input('available_from'), $timezone);
            $from = $fromLocal->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
            // Appointment window length = exam duration from start (student may start within that window).
            $a->loadMissing('questions');
            if ($a->durationMinutes()) {
                $until = $fromLocal->copy()
                    ->addMinutes((int) $a->durationMinutes())
                    ->timezone(config('app.timezone'))
                    ->format('Y-m-d H:i:s');
            } elseif ($request->filled('available_until')) {
                $until = \Carbon\Carbon::parse($request->input('available_until'), $timezone)
                    ->timezone(config('app.timezone'))
                    ->format('Y-m-d H:i:s');
            }
        }
        $byAdmin = $this->lms->isAdminActor() ? Auth::guard('admin')->id() : null;
        $byUser = ! $byAdmin ? Auth::id() : null;
        $markerId = $request->filled('marker_user_id') ? (int) $request->input('marker_user_id') : null;
        $hasMarkerCol = Schema::hasColumn('lms_assessment_assignments', 'marker_user_id');
        if ($hasMarkerCol && empty($markerId) && method_exists($this->assessments, 'canonicalAssessorForSitting')) {
            $defaultMarker = $this->assessments->canonicalAssessorForSitting(null, $a, null);
            if ($defaultMarker) {
                $markerId = (int) $defaultMarker->id;
            }
        }
        $hasTimezoneCol = Schema::hasColumn('lms_assessment_assignments', 'timezone');

        $created = 0;
        $batchIds = collect($request->input('batch_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $studentIds = collect($request->input('student_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $this->pruneEmptyUnusedSittings($a, $sittingKind);
        $covered = collect();
        foreach ($batchIds as $batchId) {
            $batchId = (int) $batchId;
            if ($batchId <= 0) {
                continue;
            }
            $row = [
                'assessment_id' => $a->id,
                'class_batch_id' => $batchId,
                'available_from' => $from,
                'available_until' => $until,
                'assigned_by_admin_id' => $byAdmin,
                'assigned_by_user_id' => $byUser,
            ];
            if ($hasMarkerCol) {
                $row['marker_user_id'] = $markerId;
            }
            if ($hasTimezoneCol) {
                $row['timezone'] = $timezone;
            }
            if ($hasKindCol) {
                $row['sitting_kind'] = $sittingKind;
            }
            if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                $roster = $this->snapshotBatchStudentIds($batchId)->merge($studentIds)->unique()->filter()->values();
                $row['roster_user_ids'] = $roster->all();
                if ($row['roster_user_ids'] === []) {
                    continue;
                }
                $this->clearExclusionsForUsers($a, $roster, $sittingKind);
                $covered = $covered->merge($roster);
            }
            if ($this->hasUnusedDuplicateSitting($a, $row, $hasKindCol)) {
                continue;
            }
            $createdAsg = LmsAssessmentAssignment::create($row);
            $this->assessments->notifyAssignmentCreated($createdAsg);
            $created++;
        }
        foreach ($studentIds as $studentId) {
            $studentId = (int) $studentId;
            if ($studentId <= 0) {
                continue;
            }
            if ($covered->contains($studentId)) {
                continue;
            }
            $row = [
                'assessment_id' => $a->id,
                'student_id' => $studentId,
                'available_from' => $from,
                'available_until' => $until,
                'assigned_by_admin_id' => $byAdmin,
                'assigned_by_user_id' => $byUser,
            ];
            if ($hasMarkerCol) {
                $row['marker_user_id'] = $markerId;
            }
            if ($hasTimezoneCol) {
                $row['timezone'] = $timezone;
            }
            if ($hasKindCol) {
                $row['sitting_kind'] = $sittingKind;
            }
            if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                $row['roster_user_ids'] = [$studentId];
            }
            if ($this->hasUnusedDuplicateSitting($a, $row, $hasKindCol)) {
                continue;
            }
            $createdAsg = LmsAssessmentAssignment::create($row);
            $this->assessments->notifyAssignmentCreated($createdAsg);
            $created++;
        }

        // Stash count for flash message.
        $request->attributes->set('lms_assignments_created', $created);
    }

    /** People on the batch at save time (pivot + students relation). Empty batches are not assigned. */
    protected function snapshotBatchStudentIds(int $batchId)
    {
        $ids = ClassBatch::studentUserIdsFor($batchId);
        if ($ids->isEmpty()) {
            try {
                $batch = ClassBatch::query()->with('students')->find($batchId);
                if ($batch && $batch->students) {
                    $ids = $ids->merge($batch->students->pluck('id'));
                }
            } catch (\Throwable $e) {
                // keep pivot ids
            }
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->filter()->values();
    }

    /** Same test + same people + same start, still unused → do not create another sitting. */
    protected function hasUnusedDuplicateSitting(LmsAssessment $a, array $row, bool $hasKindCol): bool
    {
        $q = LmsAssessmentAssignment::query()->where('assessment_id', $a->id);
        if (! empty($row['class_batch_id'])) {
            $q->where('class_batch_id', (int) $row['class_batch_id']);
        } else {
            $q->where('student_id', (int) ($row['student_id'] ?? 0));
        }
        if ($hasKindCol && ! empty($row['sitting_kind'])) {
            $q->where('sitting_kind', $row['sitting_kind']);
        }
        if (! empty($row['available_from'])) {
            $q->where('available_from', $row['available_from']);
        } else {
            $q->whereNull('available_from');
        }
        $cands = $q->get();
        if ($cands->isEmpty()) {
            return false;
        }
        foreach ($cands as $asg) {
            if (method_exists($this->assessments, 'sittingHasNoPeople') && $this->assessments->sittingHasNoPeople($asg)) {
                try {
                    $asg->delete();
                } catch (\Throwable $e) {
                    // keep looking
                }
                continue;
            }
            if (! Schema::hasColumn('lms_assessment_attempts', 'assignment_id')) {
                return true;
            }
            $used = LmsAssessmentAttempt::query()->where('assignment_id', (int) $asg->id)->exists();
            if (! $used) {
                return true;
            }
        }

        return false;
    }

    protected function pruneEmptyUnusedSittings(LmsAssessment $a, string $sittingKind): void
    {
        try {
            $rows = LmsAssessmentAssignment::query()->where('assessment_id', (int) $a->id)->get();
        } catch (\Throwable $e) {
            return;
        }
        foreach ($rows as $asg) {
            $kind = method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting() ? 'self' : 'exam';
            if ($kind !== $sittingKind) {
                continue;
            }
            if (! $this->assessments->sittingHasNoPeople($asg)) {
                continue;
            }
            try {
                $asg->delete();
            } catch (\Throwable $e) {
                // keep going
            }
        }
    }

    protected function clearExclusionsForUsers(LmsAssessment $a, $userIds, string $sittingKind): void
    {
        $ids = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty() || ! Schema::hasColumn('lms_assessment_assignments', 'excluded_user_ids')) {
            return;
        }
        try {
            $rows = LmsAssessmentAssignment::query()->where('assessment_id', (int) $a->id)->get();
        } catch (\Throwable $e) {
            return;
        }
        foreach ($rows as $asg) {
            $kind = method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting() ? 'self' : 'exam';
            if ($kind !== $sittingKind) {
                continue;
            }
            $ex = collect($asg->excluded_user_ids ?? [])->map(fn ($id) => (int) $id)->reject(fn ($id) => $ids->contains($id))->values()->all();
            if ($ex === collect($asg->excluded_user_ids ?? [])->map(fn ($id) => (int) $id)->values()->all()) {
                continue;
            }
            $asg->excluded_user_ids = $ex;
            try {
                $asg->save();
            } catch (\Throwable $e) {
                // next row
            }
        }
    }

    protected function formData(LmsAssessment $assessment): array
    {
        $questionQuery = LmsQuestion::query()->where('status', 'active')->orderByDesc('id');
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIds = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $questionQuery->whereIn('course_id', $courseIds ?: [0]);
        }
        $questions = $questionQuery->get(['id', 'type', 'stem', 'topic', 'max_marks', 'course_id']);
        $batches = Schema::hasTable('class_batches')
            ? ClassBatch::query()->orderByDesc('id')->get(['id', 'code', 'name'])
            : collect();

        // Assignable users: students + staff (CW, Accountant, Instructor).
        $assignableUsers = User::query()
            ->with('roles:id,name')
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', array_merge(['student'], $this->assessments->staffRoleNames()));
            })
            ->orderBy('name')
            ->limit(800)
            ->get(['id', 'name', 'email']);

        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['instructor', 'head_of_faculty', 'head-of-faculty']))
            ->orderBy('name')
            ->limit(400)
            ->get(['id', 'name', 'email']);
        try {
            $extraIds = collect();
            foreach (\App\Models\Course::query()->get(['instructor_id']) as $course) {
                $extraIds = $extraIds->merge(course_instructor_ids($course));
            }
            if ($extraIds->isNotEmpty()) {
                $instructors = $instructors->merge(
                    User::query()->whereIn('id', $extraIds->map(fn ($id) => (int) $id)->unique()->all())->get(['id', 'name', 'email'])
                )->unique('id')->sortBy('name')->values();
            }
        } catch (\Throwable $e) {
            // keep role list
        }

        $selectedMarker = old(
            'marker_user_id',
            $assessment->exists
                ? $assessment->assignments->firstWhere('marker_user_id', '!=', null)?->marker_user_id
                : null
        );

        return [
            'assessment' => $assessment,
            'kinds' => collect(LmsAssessment::kinds())->when(
                ! $this->assessments->canManageSkillTests(),
                fn ($c) => $c->except(LmsAssessment::KIND_SKILL)
            )->all(),
            'targetRoles' => LmsAssessment::targetRoles(),
            'courses' => $this->lms->coursesForActor(),
            'questions' => $questions,
            'batches' => $batches,
            'students' => $assignableUsers,
            'instructors' => $instructors,
            'selectedQuestionIds' => old('question_ids', $assessment->exists ? $assessment->questions->pluck('id')->all() : []),
            'selectedBatchIds' => old('batch_ids', []),
            'selectedStudentIds' => old('student_ids', []),
            'selectedMarkerId' => $selectedMarker,
            'timezoneOptions' => LmsAssessmentAssignment::timezoneOptions(),
            'selectedTimezone' => old(
                'timezone',
                ($assessment->exists
                    ? $assessment->assignments->firstWhere('timezone', '!=', null)?->timezone
                    : null)
                    ?: config('app.timezone', 'Asia/Dubai')
            ),
            'canSkill' => $this->assessments->canManageSkillTests(),
            'canCreate' => $assessment->isSkill()
                ? $this->assessments->canManageSkillTests()
                : $this->assessments->canCreateStudentAssessments(),
            'zohoWorkDriveReady' => $this->zoho->isWorkDriveReady(),
            'canAssign' => ! $assessment->isDisabled() && (
                $assessment->isSkill()
                    ? $this->assessments->canManageSkillTests()
                    : $this->assessments->canAssignStudentAssessments()
            ),
            'courseRequired' => $this->lms->isInstructorActor()
                && ! $this->lms->isAdminActor()
                && ! panel_sees_all_dashboards()
                && ! $assessment->isSkill(),
        ];
    }

    /** Sitting edit/delete is Admin login only. Instructors get a message, not a Forbidden page. */
    protected function denyNonAdminSittingChange()
    {
        if (Auth::guard('admin')->check()) {
            return null;
        }

        return redirect()
            ->route('admin.assessments.tests.assigned-users')
            ->with('error', 'Only Admin can edit, delete or re-assign sittings. Use Mark, Publish or Download PDF.');
    }

    /** Mock 1 appointment for this sitting only: stored window, else earliest start on this assignment. */
    protected function mock1StartFromThisSitting($asg): ?\Carbon\Carbon
    {
        if (! $asg || empty($asg->id)) {
            return null;
        }
        $id = (int) $asg->id;
        if (array_key_exists($id, $this->mock1SittingStartCache)) {
            return $this->mock1SittingStartCache[$id];
        }
        try {
            $started = LmsAssessmentAttempt::query()
                ->where('assignment_id', $id)
                ->whereNotNull('started_at')
                ->orderBy('started_at')
                ->value('started_at');
            if ($started) {
                $at = $started instanceof \Carbon\Carbon ? $started : \Carbon\Carbon::parse($started);

                return $this->mock1SittingStartCache[$id] = $at;
            }
        } catch (\Throwable $e) {
            // fall through to stored window
        }
        $start = $asg && method_exists($asg, 'windowStart') ? $asg->windowStart() : null;

        return $this->mock1SittingStartCache[$id] = $start;
    }

    /** Upcoming until start; Missed after start + 30 minutes with no attempt. Same in Admin, instructor, student. */
    protected function unusedSittingStatus($asg, $assessment, bool $isCohort = false, $student = null): string
    {
        if ($asg && method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting()) {
            return 'Assigned';
        }
        if (method_exists($this->assessments, 'unusedExamBucket')) {
            $bucket = $this->assessments->unusedExamBucket($asg, $assessment, $student);

            return match ($bucket) {
                'upcoming' => 'Upcoming',
                'missed' => 'Missed',
                default => 'Assigned',
            };
        }
        $start = $asg && method_exists($asg, 'windowStart') ? $asg->windowStart() : null;
        if ($asg && method_exists($asg, 'isNotYetOpen') && $asg->isNotYetOpen()) {
            return 'Upcoming';
        }
        if ($start && now()->lt($start)) {
            return 'Upcoming';
        }
        if ($asg && method_exists($asg, 'isMissedByStartGrace') && $asg->isMissedByStartGrace()) {
            return 'Missed';
        }

        return 'Assigned';
    }

    protected function appointmentLooksBlank(?string $label): bool
    {
        $label = trim((string) $label);

        return $label === ''
            || $label === '—'
            || $label === 'Open'
            || $label === 'Open (no appointment set)'
            || $label === 'Start anytime'
            || $label === 'Self test — start anytime';
    }

    protected function assessorLooksBlank(?string $label): bool
    {
        $label = trim((string) $label);

        return $label === ''
            || $label === '—'
            || strcasecmp($label, 'Admin / course instructors') === 0
            || strcasecmp($label, 'Admin') === 0;
    }

    /** Same assessor rule as student My Tests (canonicalAssessorForSitting). */
    protected function namedAssessorForAssessment($assessment, $asg = null, $attempt = null): ?User
    {
        if (method_exists($this->assessments, 'canonicalAssessorForSitting')) {
            $named = $this->assessments->canonicalAssessorForSitting($asg, $assessment, $attempt);
            if ($named) {
                return $named;
            }
        }

        return $this->batchTrainerForSitting($asg, $assessment);
    }

    protected function batchTrainerForSitting($asg, $assessment): ?User
    {
        $batch = null;
        try {
            if ($asg instanceof LmsAssessmentAssignment) {
                $batch = $asg->relationLoaded('batch') ? $asg->batch : null;
                if (! $batch && ! empty($asg->class_batch_id)) {
                    $batch = ClassBatch::query()->with(['instructors', 'headOfFaculty'])->find((int) $asg->class_batch_id);
                }
            }
            if (! $batch && $this->looksLikeCimaScsNovember($assessment)) {
                $batch = $this->loadCimaScsNovemberBatch();
            }
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
            $hof = $batch->headOfFaculty;
            if ($hof && trim((string) ($hof->name ?? '')) !== '') {
                return $hof;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    protected function graderOnThisTest($assessment): ?User
    {
        $aid = (int) ($assessment->id ?? 0);
        if ($aid <= 0 || ! Schema::hasTable('lms_assessment_attempts')) {
            return null;
        }
        try {
            $uid = LmsAssessmentAttempt::query()
                ->where('assessment_id', $aid)
                ->whereNotNull('graded_by_user_id')
                ->orderByDesc('id')
                ->value('graded_by_user_id');
            if (! $uid) {
                return null;
            }
            $user = User::query()->find((int) $uid);

            return ($user && trim((string) $user->name) !== '') ? $user : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Named assessor saved on any sitting of this same test (newest first). Display only. */
    protected function sameTestNamedMarker($assessment): ?User
    {
        if (! $assessment || empty($assessment->id)) {
            return null;
        }
        try {
            $row = LmsAssessmentAssignment::query()
                ->with('marker')
                ->where('assessment_id', (int) $assessment->id)
                ->whereNotNull('marker_user_id')
                ->orderByDesc('id')
                ->first();
            if (! $row) {
                return null;
            }
            $marker = $row->marker ?: User::query()->find((int) $row->marker_user_id);
            $name = trim((string) ($marker->name ?? ''));
            if ($name === '' || $name === '—') {
                return null;
            }

            return $marker;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Appointment window already ended — unused exam rows become Missed immediately (no extra delay). */
    protected function sittingWindowHasEnded($asg): bool
    {
        if (! $asg instanceof LmsAssessmentAssignment) {
            return false;
        }
        $start = $asg->windowStart();
        if (! $start) {
            return false;
        }
        if (now()->lt($start)) {
            return false;
        }
        $mins = 180;
        try {
            $assessment = $asg->relationLoaded('assessment') ? $asg->assessment : $asg->assessment;
            $dur = $assessment ? (int) ($assessment->durationMinutes() ?? 0) : 0;
            if ($dur > 0) {
                $mins = $dur;
            }
        } catch (\Throwable $e) {
            $mins = 180;
        }
        $end = $asg->appointmentEnd() ?: $start->copy()->addMinutes($mins);

        return now()->gt($end);
    }

    /** Batches this instructor teaches (pivot, HoF, or course they are assigned). */
    protected function instructorBatchIds(int $uid): array
    {
        if ($this->instructorScopeBatchIds !== null) {
            return $this->instructorScopeBatchIds;
        }
        $ids = collect();
        try {
            if ($uid > 0 && Schema::hasTable('class_batch_instructor')) {
                $ids = $ids->merge(
                    DB::table('class_batch_instructor')->where('instructor_id', $uid)->pluck('class_batch_id')
                );
            }
        } catch (\Throwable $e) {
            // next
        }
        try {
            if ($uid > 0 && Schema::hasTable('class_batches') && Schema::hasColumn('class_batches', 'head_of_faculty_id')) {
                $ids = $ids->merge(ClassBatch::query()->where('head_of_faculty_id', $uid)->pluck('id'));
            }
        } catch (\Throwable $e) {
            // next
        }
        $this->instructorScopeBatchIds = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();

        return $this->instructorScopeBatchIds;
    }

    /** Tests this person is assigned to mark, plus unmarked sittings they assigned or can mark. */
    protected function instructorAssessmentIds(int $uid, ?array $courseIds = null): array
    {
        if ($this->instructorScopeAssessmentIds !== null) {
            return $this->instructorScopeAssessmentIds;
        }
        $ids = collect();
        try {
            $batchIds = $this->instructorBatchIds($uid);
            if ($batchIds !== []) {
                $ids = $ids->merge(
                    LmsAssessmentAssignment::query()
                        ->whereIn('class_batch_id', $batchIds)
                        ->pluck('assessment_id')
                );
            }
        } catch (\Throwable $e) {
            // next
        }
        try {
            if (Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')) {
                $ids = $ids->merge(
                    LmsAssessmentAssignment::query()->where('marker_user_id', $uid)->pluck('assessment_id')
                );
            }
        } catch (\Throwable $e) {
            // next
        }
        try {
            if (Schema::hasColumn('lms_assessment_assignments', 'assigned_by_user_id')) {
                $ids = $ids->merge(
                    LmsAssessmentAssignment::query()
                        ->where('assigned_by_user_id', $uid)
                        ->where(function ($q) {
                            if (Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')) {
                                $q->whereNull('marker_user_id')->orWhere('marker_user_id', 0);
                            }
                        })
                        ->pluck('assessment_id')
                );
            }
        } catch (\Throwable $e) {
            // next
        }
        try {
            if (Schema::hasColumn('lms_assessment_attempts', 'graded_by_user_id')) {
                $ids = $ids->merge(
                    LmsAssessmentAttempt::query()->where('graded_by_user_id', $uid)->pluck('assessment_id')
                );
            }
        } catch (\Throwable $e) {
            // next
        }
        try {
            if ($this->actorIsMusa($uid)) {
                $ids = $ids->merge($this->cimaScsMockAssessmentIds());
            }
        } catch (\Throwable $e) {
            // next
        }
        try {
            $emptyMarker = LmsAssessmentAssignment::query();
            if (Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')) {
                $emptyMarker->where(function ($w) {
                    $w->whereNull('marker_user_id')->orWhere('marker_user_id', 0);
                });
            }
            $emptyIds = $emptyMarker->pluck('id');
            if ($emptyIds->isNotEmpty()) {
                $ids = $ids->merge(
                    LmsAssessmentAttempt::query()
                        ->whereIn('assignment_id', $emptyIds->all())
                        ->whereIn('status', ['submitted', 'graded', 'expired'])
                        ->pluck('assessment_id')
                );
            }
        } catch (\Throwable $e) {
            // next
        }
        $this->instructorScopeAssessmentIds = $ids->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();

        return $this->instructorScopeAssessmentIds;
    }

    protected function courseIdsWhereUserIsAssessor(int $uid): array
    {
        if ($uid <= 0 || ! class_exists(\App\Models\Course::class)) {
            return [];
        }
        if (isset($this->courseIdsWhereUserIsAssessorCache[$uid])) {
            return $this->courseIdsWhereUserIsAssessorCache[$uid];
        }
        try {
            $this->courseIdsWhereUserIsAssessorCache[$uid] = \App\Models\Course::query()->get(['id', 'instructor_id'])
                ->filter(fn ($course) => in_array($uid, course_instructor_ids($course), true))
                ->map(fn ($course) => (int) $course->id)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            $this->courseIdsWhereUserIsAssessorCache[$uid] = [];
        }

        return $this->courseIdsWhereUserIsAssessorCache[$uid];
    }

    protected function sittingBatchId($assignment): int
    {
        if (! $assignment) {
            return 0;
        }
        $bid = (int) ($assignment->class_batch_id ?? 0);
        if ($bid > 0) {
            return $bid;
        }
        $title = (string) (optional($assignment->assessment)->title ?? '');
        $sitters = collect();
        if (! empty($assignment->student_id)) {
            $sitters->push((int) $assignment->student_id);
        }
        try {
            if (! empty($assignment->id) && Schema::hasTable('lms_assessment_attempts')) {
                $sitters = $sitters->merge(
                    LmsAssessmentAttempt::query()
                        ->where('assignment_id', (int) $assignment->id)
                        ->pluck('user_id')
                );
            }
        } catch (\Throwable $e) {
            // keep student_id
        }

        return (int) (ClassBatch::idFromSittersSkipMasterclass($sitters->all(), $title) ?? 0);
    }

    protected function applyInstructorAssessmentScope($query, int $uid, ?array $courseIds = null)
    {
        $aids = $this->instructorAssessmentIds($uid, $courseIds);
        if ($aids === []) {
            $query->whereRaw('1 = 0');

            return;
        }
        $query->whereIn('id', $aids);
    }

    /** Course filter must not hide a sitting this instructor is assigned to. */
    protected function sittingMatchesCourseFilter($assignment, $assessment, int $courseId, ?int $instructorUid): bool
    {
        if ($courseId <= 0) {
            return true;
        }
        if ((int) ($assessment->course_id ?? 0) === $courseId) {
            return true;
        }
        $batchId = (int) ($assignment->class_batch_id ?? 0);
        if ($batchId > 0) {
            try {
                $batchCourse = (int) (ClassBatch::query()->where('id', $batchId)->value('course_id') ?? 0);
                if ($batchCourse === $courseId) {
                    return true;
                }
            } catch (\Throwable $e) {
                // next
            }
        }
        if ($instructorUid) {
            return $this->instructorCanSeeSitting($instructorUid, $assignment, $assessment);
        }

        return false;
    }

    protected function instructorCanSeeSitting(int $uid, $assignment, $assessment, ?array $courseIds = null): bool
    {
        if ($this->actorAssessesAssessment($uid, $assessment)) {
            return true;
        }
        if ($uid <= 0 || ! $assignment) {
            return false;
        }
        $markerId = (int) ($assignment->marker_user_id ?? 0);
        if ($markerId === $uid) {
            return true;
        }
        if ($markerId > 0) {
            return false;
        }
        if ((int) ($assignment->assigned_by_user_id ?? 0) === $uid) {
            return true;
        }
        $batchId = (int) ($assignment->class_batch_id ?? 0);
        if ($batchId > 0 && in_array($batchId, $this->instructorBatchIds($uid), true)) {
            return true;
        }
        if (in_array((int) ($assignment->assessment_id ?? $assessment->id ?? 0), $this->instructorAssessmentIds($uid), true)) {
            return true;
        }

        return $this->assignmentHasSubmittedAttempt($assignment);
    }

    protected function instructorCanSeeRow(int $uid, $assignment, $assessment, $attempt = null): bool
    {
        if ($uid <= 0) {
            return false;
        }
        if ($attempt && (int) ($attempt->graded_by_user_id ?? 0) === $uid) {
            return true;
        }
        if ($assignment && $this->instructorOwnsSitting($uid, $assignment, $assessment)) {
            return true;
        }
        if (! $assignment && $this->actorIsMusa($uid) && $this->isCimaScsAssignedMock($assessment)) {
            return true;
        }

        return false;
    }

    /** This instructor is the assessor on the sitting (not merely a course trainer). */
    protected function instructorOwnsSitting(int $uid, $assignment, $assessment): bool
    {
        if ($uid <= 0 || ! $assignment) {
            return false;
        }
        if (method_exists($assessment, 'trashed') && $assessment && $assessment->trashed()) {
            return false;
        }
        $markerId = (int) ($assignment->marker_user_id ?? 0);
        if ($markerId === $uid) {
            return true;
        }
        if ($markerId > 0) {
            return false;
        }
        if ((int) ($assignment->assigned_by_user_id ?? 0) === $uid) {
            return true;
        }
        if ($this->assignmentHasInstructorAttempts($uid, $assignment)) {
            return true;
        }

        return $this->actorIsMusa($uid) && $this->isCimaScsAssignedMock($assessment);
    }

    /** Live attempts this instructor still has on the sitting (not deleted). */
    protected function assignmentHasInstructorAttempts(int $uid, $assignment): bool
    {
        $id = (int) ($assignment->id ?? 0);
        if ($uid <= 0 || $id <= 0 || ! Schema::hasTable('lms_assessment_attempts')) {
            return false;
        }
        try {
            $q = LmsAssessmentAttempt::query()->where('assignment_id', $id);
            if ((int) ($assignment->marker_user_id ?? 0) === $uid
                || (int) ($assignment->assigned_by_user_id ?? 0) === $uid) {
                return $q->exists();
            }

            return $q->where('graded_by_user_id', $uid)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Dropdown: live tests this instructor has assigned people on (Missed included). */
    protected function assessmentsWithAssignedPeople($assessments, $assignments = null, ?int $markerOnlyUid = null)
    {
        return collect($assessments)->filter(function ($a) use ($assignments, $markerOnlyUid) {
            if (! $a || (method_exists($a, 'trashed') && $a->trashed())) {
                return false;
            }
            if ($this->looksLikeCimaScsNovember($a) && ! $this->isCimaScsAssignedMock($a)) {
                return false;
            }
            if ($markerOnlyUid && $this->looksLikeCimaMcs($a)) {
                return $this->instructorAssignedPeopleCount($markerOnlyUid, $a, $assignments) > 0
                    || $this->instructorNamedPeopleCount($markerOnlyUid, $a, $assignments) > 0;
            }
            try {
                if ($markerOnlyUid) {
                    return $this->instructorAssignedPeopleCount($markerOnlyUid, $a, $assignments) > 0;
                }

                return method_exists($a, 'assignedPeopleCount') && $a->assignedPeopleCount() > 0;
            } catch (\Throwable $e) {
                return false;
            }
        })->values();
    }

    /** Unique non-deleted people this instructor still has on the test. */
    protected function instructorAssignedPeopleCount(int $uid, $assessment, $assignments): int
    {
        $aids = $this->sameTitleAssessmentIds($assessment);
        if ($aids === []) {
            $aids = [(int) ($assessment->id ?? 0)];
        }
        $ids = collect();
        $excluded = $this->excludedUserIdsForAssessment($assessment);
        foreach (collect($assignments) as $asg) {
            if (! in_array((int) ($asg->assessment_id ?? 0), $aids, true)) {
                continue;
            }
            $rowAssessment = $asg->assessment ?? $assessment;
            if (! $this->instructorOwnsSitting($uid, $asg, $rowAssessment)) {
                continue;
            }
            if (! empty($asg->student_id)) {
                $ids->push((int) $asg->student_id);
            }
            if (Schema::hasColumn('lms_assessment_assignments', 'roster_user_ids')) {
                $ids = $ids->merge(collect($asg->roster_user_ids ?? []));
            }
            $self = method_exists($asg, 'isSelfSitting') && $asg->isSelfSitting();
            if ($this->isCimaScsAssignedMock($rowAssessment)
                && ! $self && empty($asg->student_id) && ! empty($asg->class_batch_id)
                && collect($asg->roster_user_ids ?? [])->filter()->isEmpty()) {
                try {
                    $ids = $ids->merge(ClassBatch::membersOnBatchAtAssignment((int) $asg->class_batch_id, $asg));
                } catch (\Throwable $e) {
                    // named/roster only
                }
            }
            try {
                if (! empty($asg->id) && Schema::hasTable('lms_assessment_attempts')) {
                    $ids = $ids->merge(
                        LmsAssessmentAttempt::query()->where('assignment_id', (int) $asg->id)->pluck('user_id')
                    );
                }
            } catch (\Throwable $e) {
                // keep assignment ids
            }
        }
        $ids = $ids->map(fn ($id) => (int) $id)->unique()->filter()->diff($excluded)->values();
        if ($ids->isEmpty()) {
            return 0;
        }
        try {
            $alive = User::query()->whereIn('id', $ids->all())->pluck('id')->map(fn ($id) => (int) $id);
            $ids = $ids->intersect($alive)->values();
        } catch (\Throwable $e) {
            // keep ids
        }

        return $ids->filter(function ($id) use ($assessment) {
            return ! $this->isRemovedTestTaker((object) ['id' => (int) $id], $assessment);
        })->count();
    }

    /** Person rows still on this sitting: named student / roster / attempt, not an inferred batch. */
    protected function instructorNamedPeopleCount(int $uid, $assessment, $assignments): int
    {
        $aids = $this->sameTitleAssessmentIds($assessment);
        if ($aids === []) {
            $aids = [(int) ($assessment->id ?? 0)];
        }
        $ids = collect();
        $excluded = $this->excludedUserIdsForAssessment($assessment);
        foreach (collect($assignments) as $asg) {
            if (! in_array((int) ($asg->assessment_id ?? 0), $aids, true)) {
                continue;
            }
            $rowAssessment = $asg->assessment ?? $assessment;
            if (! $this->instructorOwnsSitting($uid, $asg, $rowAssessment)) {
                continue;
            }
            if (! empty($asg->student_id)) {
                $ids->push((int) $asg->student_id);
            }
            try {
                if (! empty($asg->id) && Schema::hasTable('lms_assessment_attempts')) {
                    $ids = $ids->merge(
                        LmsAssessmentAttempt::query()->where('assignment_id', (int) $asg->id)->pluck('user_id')
                    );
                }
            } catch (\Throwable $e) {
                // named only
            }
        }
        try {
            $ids = $ids->merge(
                LmsAssessmentAttempt::query()
                    ->whereIn('assessment_id', $aids)
                    ->where('graded_by_user_id', $uid)
                    ->pluck('user_id')
            );
        } catch (\Throwable $e) {
            // keep named
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->filter()->diff($excluded)->count();
    }

    protected function looksLikeCimaMcs($assessment): bool
    {
        $hay = mb_strtolower(trim((string) ($assessment->title ?? '') . ' ' . (string) ($assessment->course->title ?? '')));

        return str_contains($hay, 'cima mcs')
            || str_contains($hay, 'management case')
            || (bool) preg_match('/\bmcs\b/', $hay);
    }

    /** Course assessor (and Musa on SCS) sees every person-attempt on that test, matching admin. */
    protected function actorAssessesAssessment(int $uid, $assessment): bool
    {
        if (! $assessment) {
            return false;
        }
        $courseId = (int) ($assessment->course_id ?? 0);
        if ($courseId > 0 && in_array($courseId, $this->courseIdsWhereUserIsAssessor($uid), true)) {
            return true;
        }

        return $this->actorIsMusa($uid) && $this->isCimaScsAssignedMock($assessment);
    }

    protected function actorIsMusa(int $uid): bool
    {
        if (function_exists('is_musa_shaikh_user') && is_musa_shaikh_user()) {
            return true;
        }
        if ($uid <= 0) {
            return false;
        }
        try {
            $user = User::query()->find($uid);

            return $user && function_exists('is_musa_shaikh_user') && is_musa_shaikh_user($user);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** CIMA SCS November mock test ids (Mock 1, Mock 2, …) — same set Admin lists for Musa. */
    protected function cimaScsMockAssessmentIds(): array
    {
        try {
            $q = Schema::hasColumn('lms_assessments', 'deleted_at')
                ? LmsAssessment::query()
                : LmsAssessment::query();

            return $q->get(['id', 'title'])
                ->filter(fn ($row) => $this->isCimaScsAssignedMock($row))
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function attemptIsSubmitted($attempt): bool
    {
        if (! $attempt) {
            return false;
        }
        if (! empty($attempt->graded_at) || ! empty($attempt->graded_by_user_id)) {
            return true;
        }

        return in_array((string) ($attempt->status ?? ''), ['submitted', 'graded', 'expired'], true);
    }

    protected function assignmentHasSubmittedAttempt($assignment): bool
    {
        $id = (int) ($assignment->id ?? 0);
        if ($id <= 0) {
            return false;
        }
        if (array_key_exists($id, $this->assignmentHasSubmittedAttemptCache)) {
            return $this->assignmentHasSubmittedAttemptCache[$id];
        }
        try {
            $this->assignmentHasSubmittedAttemptCache[$id] = LmsAssessmentAttempt::query()
                ->where('assignment_id', $id)
                ->where(function ($q) {
                    $q->whereIn('status', ['submitted', 'graded', 'expired'])
                        ->orWhereNotNull('graded_at')
                        ->orWhereNotNull('graded_by_user_id');
                })
                ->exists();
        } catch (\Throwable $e) {
            $this->assignmentHasSubmittedAttemptCache[$id] = false;
        }

        return $this->assignmentHasSubmittedAttemptCache[$id];
    }

    /** Instructors and Musa: only tests they created or were assigned to mark. Real Admin still sees all. */
    protected function shouldScopeTestsToOwnAssignments(): bool
    {
        if ($this->lms->isAdminActor()) {
            return false;
        }
        if ($this->lms->isInstructorActor()) {
            return true;
        }

        return function_exists('is_musa_shaikh_user') && is_musa_shaikh_user();
    }
}
