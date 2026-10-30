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
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIds = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $uid = (int) (Auth::id() ?? 0);
            $markedIds = ($uid > 0 && Schema::hasColumn('lms_assessment_assignments', 'marker_user_id'))
                ? LmsAssessmentAssignment::query()->where('marker_user_id', $uid)->pluck('assessment_id')->all()
                : [];
            $query->where(function ($q) use ($courseIds, $uid, $markedIds) {
                $q->whereIn('course_id', $courseIds ?: [0])
                    ->orWhere('created_by_user_id', $uid);
                if ($markedIds !== []) {
                    $q->orWhereIn('id', $markedIds);
                }
            });
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

        return view('admin.assessments.tests.index', [
            'rows' => $query->with([
                'course',
                'assignments.marker',
            ])->paginate(20)->withQueryString(),
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
        $a->delete();

        return redirect()->route('admin.assessments.tests.index')->with('success', 'Test removed.');
    }

    public function assignForm($id)
    {
        $this->assessments->ensure();
        $assessment = LmsAssessment::with(['assignments.batch', 'assignments.student', 'assignments.marker', 'course'])->findOrFail($id);
        abort_unless($this->assessments->canManageSittingsFor($assessment), 403);
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
        abort_unless($this->assessments->canManageSittingsFor($assessment), 403);
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
        abort_unless($this->assessments->canManageSittingsFor($a), 403);
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
            ->route('admin.assessments.tests.attempts', $a->id)
            ->with('success', 'Sitting ' . $assignment->displayCode() . ' updated.');
    }

    public function destroyAssignment(Request $request, $assignmentId)
    {
        $this->assessments->ensure();
        $assignment = LmsAssessmentAssignment::with('assessment')->findOrFail($assignmentId);
        abort_unless($assignment->assessment && $this->assessments->canManageSittingsFor($assignment->assessment), 403);

        $attemptId = (int) $request->input('attempt_id', 0);
        if ($attemptId > 0) {
            $attempt = LmsAssessmentAttempt::query()
                ->where('id', $attemptId)
                ->where('assessment_id', $assignment->assessment_id)
                ->first();
            if ($attempt) {
                $attempt->answers()->delete();
                $attempt->delete();
            }
        } else {
            $attemptQuery = LmsAssessmentAttempt::query()
                ->where('assessment_id', $assignment->assessment_id);
            if ($assignment->student_id) {
                $attemptQuery->where('user_id', (int) $assignment->student_id);
            }
            foreach ($attemptQuery->get() as $att) {
                if ($this->assessments->attemptBelongsToAssignment($att, $assignment)) {
                    $att->answers()->delete();
                    $att->delete();
                }
            }
        }

        $code = $assignment->displayCode();
        $assignment->delete();

        return back()->with('success', 'Sitting ' . $code . ' removed.');
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

        $selectedAssessment = $assessmentId > 0
            ? LmsAssessment::query()->find($assessmentId)
            : null;

        if ($selectedAssessment && $selectedAssessment->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        }

        $courseIdsScope = null;
        $markerOnlyUid = null;
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIdsScope = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $markerOnlyUid = (int) (Auth::id() ?? 0);
        }

        $assignQuery = LmsAssessmentAssignment::query()->orderByDesc('id');

        if ($assessmentId > 0) {
            $assignQuery->where('assessment_id', $assessmentId);
        }
        if ($batchId > 0) {
            $batchStudentIds = ClassBatch::find($batchId)?->students()->pluck('users.id')->map(fn ($id) => (int) $id)->all() ?? [];
            $assignQuery->where(function ($q) use ($batchId, $batchStudentIds) {
                $q->where('class_batch_id', $batchId);
                if ($batchStudentIds !== []) {
                    $q->orWhereIn('student_id', $batchStudentIds);
                }
            });
        }
        if ($courseId > 0) {
            $assignQuery->whereHas('assessment', fn ($q) => $q->where('course_id', $courseId));
        }
        if ($kind !== '' && array_key_exists($kind, LmsAssessment::kinds())) {
            $assignQuery->whereHas('assessment', fn ($q) => $q->where('kind', $kind));
        }
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $assignQuery->where(function ($q) use ($like, $search) {
                $q->whereHas('assessment', function ($aq) use ($like) {
                    $aq->where('title', 'like', $like)
                        ->orWhereHas('course', fn ($cq) => $cq->where('title', 'like', $like));
                })
                    ->orWhereHas('student', fn ($sq) => $sq->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orWhereHas('batch', fn ($bq) => $bq->where('name', 'like', $like)->orWhere('code', 'like', $like));
                if (ctype_digit($search)) {
                    $q->orWhere('assessment_id', (int) $search);
                }
            });
        }

        if ($markerOnlyUid) {
            $assignQuery->where(function ($q) use ($markerOnlyUid, $courseIdsScope) {
                $q->where('marker_user_id', $markerOnlyUid)
                    ->orWhere('assigned_by_user_id', $markerOnlyUid)
                    ->orWhereHas('assessment', function ($aq) use ($markerOnlyUid, $courseIdsScope) {
                        $aq->where('created_by_user_id', $markerOnlyUid);
                        if ($courseIdsScope) {
                            $aq->orWhereIn('course_id', $courseIdsScope);
                        }
                    });
            });
        } elseif ($courseIdsScope !== null) {
            $assignQuery->whereHas('assessment', function ($q) use ($courseIdsScope) {
                $q->where(function ($inner) use ($courseIdsScope) {
                    $inner->whereIn('course_id', $courseIdsScope ?: [0]);
                    if ($this->assessments->canManageSkillTests()) {
                        $inner->orWhere('kind', LmsAssessment::KIND_SKILL)
                            ->orWhere('audience', LmsAssessment::AUDIENCE_STAFF);
                    }
                });
            });
        } elseif (! $this->assessments->canManageSkillTests()) {
            $assignQuery->whereHas('assessment', function ($q) {
                $q->where('kind', '!=', LmsAssessment::KIND_SKILL)
                    ->where('audience', '!=', LmsAssessment::AUDIENCE_STAFF);
            });
        }

        try {
            $assignments = $assignQuery->get();
        } catch (\Throwable $e) {
            report($e);
            $assignments = collect();
        }
        if (Schema::hasTable('lms_assessment_assignments')) {
            try {
                $raw = DB::table('lms_assessment_assignments')->orderByDesc('id')->get();
                if ($raw->count() > $assignments->count()) {
                    $assignments = LmsAssessmentAssignment::hydrate($raw->map(fn ($row) => (array) $row)->all());
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $assessmentIds = $assignments->pluck('assessment_id')->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
        if ($assessmentId > 0) {
            $assessmentIds[] = $assessmentId;
            $assessmentIds = array_values(array_unique($assessmentIds));
        }
        try {
            $assessmentsById = $assessmentIds === []
                ? collect()
                : LmsAssessment::query()->with('course')->whereIn('id', $assessmentIds)->get()->keyBy(fn ($row) => (int) $row->id);
        } catch (\Throwable $e) {
            report($e);
            $assessmentsById = $assessmentIds === []
                ? collect()
                : LmsAssessment::query()->whereIn('id', $assessmentIds)->get()->keyBy(fn ($row) => (int) $row->id);
        }
        foreach ($assignments as $assignment) {
            $aid = (int) $assignment->assessment_id;
            $found = $assessmentsById->get($aid) ?: LmsAssessment::query()->find($aid);
            if ($found) {
                $assessmentsById->put($aid, $found);
                $assignment->setRelation('assessment', $found);
            }
        }

        $rowsByKey = [];
        $batchStudentCache = [];

        foreach ($assignments as $assignment) {
            $assessment = $assignment->relationLoaded('assessment')
                ? $assignment->assessment
                : $assessmentsById->get((int) $assignment->assessment_id);
            if (! $assessment) {
                continue;
            }

            $students = collect();
            if ($assignment->student_id) {
                $student = $assignment->student
                    ?: \App\Models\User::query()->find($assignment->student_id);
                if ($student) {
                    $students->push($student);
                }
            } elseif ($assignment->class_batch_id) {
                $bid = (int) $assignment->class_batch_id;
                if ($batchId > 0 && $bid !== $batchId) {
                    continue;
                }
                if (! isset($batchStudentCache[$bid])) {
                    $ids = Schema::hasTable('class_batch_student')
                        ? DB::table('class_batch_student')->where('class_batch_id', $bid)->pluck('student_id')
                        : collect();
                    $batchStudentCache[$bid] = $ids->isEmpty()
                        ? collect()
                        : User::query()->whereIn('id', $ids)->get();
                }
                $students = $batchStudentCache[$bid];
                if ($students->isEmpty()) {
                    $batch = $assignment->batch ?: ClassBatch::query()->find($bid);
                    $students = collect([(object) [
                        'id' => 0,
                        'name' => $batch ? ($batch->code . ' — ' . $batch->name) : ('Batch #' . $bid),
                        'email' => 'Batch assignment (no students linked yet)',
                    ]]);
                }
            }

            if ($batchId > 0 && $assignment->student_id) {
                $inBatch = ClassBatch::find($batchId)?->students()->where('users.id', $assignment->student_id)->exists();
                if (! $inBatch) {
                    continue;
                }
            }

            if ($search !== '') {
                $needle = mb_strtolower($search);
                $students = $students->filter(function ($student) use ($needle, $assessment) {
                    $hay = mb_strtolower(
                        ($student->name ?? '') . ' ' . ($student->email ?? '') . ' '
                        . ($assessment->title ?? '') . ' ' . ($assessment->course->title ?? '')
                    );

                    return str_contains($hay, $needle);
                });
            }

            foreach ($students as $student) {
                if ($this->isRemovedTestTaker($student)) {
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
            if ($assessmentId > 0) {
                $attemptQuery->where('assessment_id', $assessmentId);
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
            foreach (LmsAssessment::query()->whereIn('id', $missingAssessmentIds->all())->get() as $extraAssessment) {
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
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $hay = mb_strtolower(($student->name ?? '') . ' ' . ($student->email ?? '') . ' ' . ($att->assessment->title ?? ''));
                if (! str_contains($hay, $needle)) {
                    continue;
                }
            }
            $assessment = $att->assessment
                ?: $assessmentsById->get((int) $att->assessment_id)
                ?: LmsAssessment::query()->find($att->assessment_id);
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                continue;
            }
            if ($this->isRemovedTestTaker($student)) {
                continue;
            }
            $asg = $this->ensureAssignmentForLiveAttempt($att, $assessment, $assignments, $assignmentsById);
            if (! $asg) {
                $asg = $this->assignmentForAttempt($att, $assignments, $assignmentsById);
            }
            if ($asg) {
                $this->fillEmptySittingFields($asg, $assessment, $assignments);
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
                    if ($asg && $asg->isSelfSitting()) {
                        $max = max(1, (int) ($r['assessment']->max_attempts ?? 1));
                        $rowsByKey[$k]['attempt_number'] = $rowsByKey[$k]['attempt'] ? min($max, max(1, $i + 1)) : 1;
                        $rowsByKey[$k]['attempt_total'] = $max;
                    } else {
                        $rowsByKey[$k]['attempt_number'] = $i + 1;
                        $rowsByKey[$k]['attempt_total'] = $total;
                    }
                }
            }
        }
        foreach ($rowsByKey as $k => $r) {
            if (! isset($r['attempt_number'])) {
                $rowsByKey[$k]['attempt_number'] = 1;
                $rowsByKey[$k]['attempt_total'] = 1;
            }
            $asg = $r['assignment'] ?? null;
            $att = $r['attempt'] ?? null;
            if (! $asg && $att) {
                $asg = $this->assignmentForAttempt($att, $assignments, $assignmentsById);
                if ($asg) {
                    $rowsByKey[$k]['assignment'] = $asg;
                    $r['assignment'] = $asg;
                }
            }
            if ($asg) {
                $this->fillEmptySittingFields($asg, $r['assessment'] ?? $asg->assessment, $assignments);
                $marker = $peopleById->get((int) ($asg->marker_user_id ?? 0));
                if ($marker) {
                    $asg->setRelation('marker', $marker);
                }
                $creator = $peopleById->get((int) ($asg->assigned_by_user_id ?? 0));
                if ($creator) {
                    $asg->setRelation('assignedByUser', $creator);
                }
            }
            $assessment = $r['assessment'] ?? $asg?->assessment;
            if ($assessment && ! $assessment->relationLoaded('course') && ! empty($assessment->course_id)) {
                $assessment->loadMissing('course');
            }
            if ($assessment) {
                $rowsByKey[$k]['assessment'] = $assessment;
            }
            if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
                unset($rowsByKey[$k]);
            }
        }
        $rowsByKey = collect($rowsByKey)->filter(function ($r) {
            $assessment = $r['assessment'] ?? null;
            if (! $assessment) {
                return false;
            }
            if (method_exists($assessment, 'trashed') && $assessment->trashed()) {
                return false;
            }
            if ($this->isRemovedTestTaker($r['student'] ?? null)) {
                return false;
            }

            return true;
        })->all();

        $rows = collect($rowsByKey)->values()->sortByDesc(function ($row) {
            $attempt = $row['attempt'];
            if ($attempt) {
                return optional($attempt->submitted_at ?: $attempt->updated_at)->timestamp ?? 0;
            }

            return optional($row['assignment']?->created_at)->timestamp ?? 0;
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
                if (! $student && $assignment->class_batch_id && Schema::hasTable('class_batch_student')) {
                    $ids = DB::table('class_batch_student')
                        ->where('class_batch_id', (int) $assignment->class_batch_id)
                        ->pluck('student_id');
                    foreach (User::query()->whereIn('id', $ids)->get() as $batchStudent) {
                        $rows->push([
                            'assessment' => $assessment,
                            'student' => $batchStudent,
                            'assignment' => $assignment,
                            'attempt' => null,
                            'attempt_number' => 1,
                            'attempt_total' => 1,
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
                    'attempt_total' => 1,
                ]);
            }
        }

        if ($rows->isEmpty()) {
            try {
                $fallbackAttempts = LmsAssessmentAttempt::query()
                    ->with(['user', 'assessment.course'])
                    ->when($assessmentId > 0, fn ($q) => $q->where('assessment_id', $assessmentId))
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
                if (! $student || $this->isRemovedTestTaker($student)) {
                    continue;
                }
                $asg = $this->ensureAssignmentForLiveAttempt($att, $assessment, $assignments, $assignments->keyBy(fn ($row) => (int) $row->id));
                $rows->push([
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $asg,
                    'attempt' => $att,
                    'attempt_number' => 1,
                    'attempt_total' => 1,
                ]);
            }
        }

        $statusOptions = [
            'Upcoming' => 'Upcoming',
            'Assigned' => 'Assigned',
            'In progress' => 'In progress',
            'Marking Required' => 'Marking Required',
            'Completed' => 'Completed',
            'Completed and Marked' => 'Completed and Marked',
            'Missed' => 'Missed',
        ];
        if ($statusFilter !== '' && array_key_exists($statusFilter, $statusOptions)) {
            $rows = $rows->filter(function ($row) use ($statusFilter) {
                $attempt = $row['attempt'];
                $assignment = $row['assignment'];
                if ($attempt) {
                    $statusText = $attempt->statusLabel();
                } elseif ($assignment && $assignment->isNotYetOpen()) {
                    $statusText = 'Upcoming';
                } elseif ($assignment && $assignment->isExpired()) {
                    $statusText = 'Missed';
                } else {
                    $statusText = 'Assigned';
                }

                return $statusText === $statusFilter;
            })->values();
        }

        $assessmentQuery = LmsAssessment::query()->orderBy('title');
        if ($markerOnlyUid) {
            $markedIds = Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')
                ? LmsAssessmentAssignment::query()->where('marker_user_id', $markerOnlyUid)->pluck('assessment_id')->all()
                : [];
            $assessmentQuery->where(function ($q) use ($markerOnlyUid, $courseIdsScope, $markedIds) {
                $q->where('created_by_user_id', $markerOnlyUid);
                if ($courseIdsScope) {
                    $q->orWhereIn('course_id', $courseIdsScope);
                }
                if ($markedIds !== []) {
                    $q->orWhereIn('id', $markedIds);
                }
            });
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
        $perPage = 10;
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
            'selectedAssessment' => $selectedAssessment,
            'assessments' => $assessmentQuery->get(['id', 'title', 'kind']),
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
            ],
        ]);
    }

    public function attempts(Request $request, $id)
    {
        $this->assessments->ensure();
        $assessment = LmsAssessment::with('course:id,title')->findOrFail($id);
        $isMarker = Schema::hasColumn('lms_assessment_assignments', 'marker_user_id')
            && LmsAssessmentAssignment::query()
                ->where('assessment_id', $assessment->id)
                ->where('marker_user_id', Auth::id())
                ->exists();
        if ($assessment->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } elseif (! $isMarker) {
            abort_unless($this->assessments->canManageStudentAssessments(), 403);
            $this->assertInstructorOwnsCourse((int) $assessment->course_id);
        }

        try {
            // Results = every assigned sitting for this test (started or not), same idea as Assigned Tests.
            $assignments = LmsAssessmentAssignment::query()
                ->with(['marker:id,name', 'assignedByUser:id,name', 'batch:id,code,name', 'student:id,name,email'])
                ->where('assessment_id', $assessment->id)
                ->orderBy('id')
                ->get();
            $assignments->each(fn ($a) => $a->setRelation('assessment', $assessment));

            $batchStudentCache = [];
            $rowsByKey = [];

            foreach ($assignments as $assignment) {
                $students = collect();
                if ($assignment->student_id) {
                    $student = $assignment->student ?: User::query()->find($assignment->student_id);
                    // Always keep the sitting visible even if the user row is missing.
                    $students->push($student ?: (object) [
                        'id' => (int) $assignment->student_id,
                        'name' => 'User #' . $assignment->student_id,
                        'email' => '',
                    ]);
                } elseif ($assignment->class_batch_id && Schema::hasTable('class_batches')) {
                    $bid = (int) $assignment->class_batch_id;
                    if (! isset($batchStudentCache[$bid])) {
                        $batch = ClassBatch::query()->find($bid);
                        $batchStudentCache[$bid] = $batch
                            ? $batch->students()->get()
                            : collect();
                    }
                    $students = $batchStudentCache[$bid];
                    if ($students->isEmpty()) {
                        $students = collect([(object) [
                            'id' => 0,
                            'name' => 'Batch #' . $bid . ' (no students)',
                            'email' => '',
                        ]]);
                    }
                } else {
                    // Orphan assignment row — still list it.
                    $students = collect([(object) [
                        'id' => 0,
                        'name' => '—',
                        'email' => '',
                    ]]);
                }

                foreach ($students as $student) {
                    $sid = (int) ($student->id ?? 0);
                    $key = (int) $assignment->id . ':' . $sid;
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

            $allAttempts = LmsAssessmentAttempt::query()
                ->with('user:id,name,email')
                ->where('assessment_id', $assessment->id)
                ->orderByDesc('id')
                ->get();
            $attemptsByUser = $allAttempts->groupBy(fn ($a) => (int) $a->user_id);
            $claimedAttemptIds = [];

            // Attempt N of T per student (oldest assignment = attempt 1).
            $sittingsByUser = collect($rowsByKey)->groupBy(fn ($r) => (int) ($r['student']->id ?? 0));
            $attemptMeta = [];
            foreach ($sittingsByUser as $uid => $sittings) {
                $ordered = $sittings->sortBy(fn ($r) => (int) $r['assignment']->id)->values();
                $total = $ordered->count();
                foreach ($ordered as $i => $r) {
                    $attemptMeta[(int) $r['assignment']->id . ':' . $uid] = [
                        'number' => $i + 1,
                        'total' => $total,
                    ];
                }
            }

            foreach (collect($rowsByKey)->groupBy(fn ($r) => (int) ($r['student']->id ?? 0)) as $uid => $sittings) {
                $candidates = $uid > 0 ? ($attemptsByUser->get($uid) ?? collect())->sortBy('id')->values() : collect();
                foreach ($sittings->sortBy(fn ($r) => (int) $r['assignment']->id) as $sitting) {
                    $asg = $sitting['assignment'];
                    $key = (int) $asg->id . ':' . (int) $uid;
                    $matched = $this->matchSittingAttempt($candidates, $asg, $claimedAttemptIds);
                    $rowsByKey[$key]['attempt'] = $matched;
                    $meta = $attemptMeta[$key] ?? ['number' => 1, 'total' => max(1, $assignments->count())];
                    $rowsByKey[$key]['attempt_number'] = $meta['number'];
                    $rowsByKey[$key]['attempt_total'] = $meta['total'];
                }
            }

            // Include any attempt that never matched an assignment sitting (legacy / edge cases).
            foreach ($allAttempts as $att) {
                if (isset($claimedAttemptIds[(int) $att->id])) {
                    continue;
                }
                $student = $att->user ?: (object) [
                    'id' => (int) $att->user_id,
                    'name' => 'User #' . $att->user_id,
                    'email' => '',
                ];
                $rowsByKey['attempt:' . $att->id] = [
                    'assessment' => $assessment,
                    'student' => $student,
                    'assignment' => $assignments->first(),
                    'attempt' => $att,
                    'attempt_number' => 1,
                    'attempt_total' => max(1, $assignments->count()),
                ];
            }

            $rows = collect($rowsByKey)->values()->sortByDesc(function ($row) {
                $attempt = $row['attempt'];
                if ($attempt) {
                    return optional($attempt->submitted_at ?: $attempt->updated_at)->timestamp ?? 0;
                }

                return (int) ($row['assignment']->id ?? 0);
            })->values();
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.assessments.tests.index')
                ->with('error', 'Could not load Results for this test. Please try again or open Assigned Tests.');
        }

        if ($request->query('export') === 'excel') {
            return $this->streamAssignedRowsExcel($rows, 'results-' . $assessment->id . '_' . now()->format('Y-m-d') . '.csv');
        }

        return view('admin.assessments.tests.attempts', [
            'assessment' => $assessment,
            'rows' => $rows,
            'canDeleteAttempts' => $this->lms->isAdminActor() || panel_sees_all_dashboards(),
            'canAssign' => $this->assessments->canAssignStudentAssessments()
                || $this->assessments->canManageSkillTests(),
        ]);
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

        $feedbackMax = 2000;
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
            return redirect()->route('admin.assessments.tests.assigned-users', ['assessment_id' => $attempt->assessment_id])
                ->with('success', 'Marks saved and results published to the student.');
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
                } elseif ($assignment && $assignment->isExpired()) {
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
                fputcsv($handle, [
                    $assignment ? $assignment->displayCode() : '—',
                    $assessment->title ?? '—',
                    method_exists($assessment, 'kindLabel') ? $assessment->kindLabel() : ($assessment->kind ?? ''),
                    $student->name ?? '—',
                    'Attempt ' . (int) ($row['attempt_number'] ?? 1) . ' of ' . max(1, (int) ($row['attempt_total'] ?? 1)),
                    $assignment ? $assignment->appointmentWindowLabel() : '—',
                    ($assignment && $attempt?->started_at) ? $assignment->formatInTimezone($attempt->started_at) : (optional($attempt?->started_at)->format('d/m/y H:i') ?? '—'),
                    ($assignment && $attempt?->submitted_at) ? $assignment->formatInTimezone($attempt->submitted_at) : (optional($attempt?->submitted_at)->format('d/m/y H:i') ?? '—'),
                    $attempt ? $attempt->assessorLabel($assignment) : ($assignment?->marker?->name ?? '—'),
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

    protected function isRemovedTestTaker($student): bool
    {
        if (! $student) {
            return false;
        }
        $name = mb_strtolower(trim((string) ($student->name ?? '')));
        $email = mb_strtolower(trim((string) ($student->email ?? '')));

        return str_contains($name, 'imthiyas') || str_contains($email, 'imthiyas');
    }

    protected function siblingSitting($assessment, $assignments): ?LmsAssessmentAssignment
    {
        $aid = (int) ($assessment->id ?? 0);
        $fromList = collect($assignments)->first(
            fn ($row) => (int) ($row->assessment_id ?? 0) === $aid && ! empty($row->marker_user_id)
        );
        if ($fromList) {
            return $fromList;
        }
        $any = collect($assignments)->first(fn ($row) => (int) ($row->assessment_id ?? 0) === $aid);
        if ($any) {
            return $any;
        }
        try {
            return LmsAssessmentAssignment::query()
                ->where('assessment_id', $aid)
                ->orderByDesc('id')
                ->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function fillEmptySittingFields($asg, $assessment, $assignments): void
    {
        if (! $asg instanceof LmsAssessmentAssignment) {
            return;
        }
        $sibling = $this->siblingSitting($assessment, $assignments);
        $dirty = false;
        if (trim((string) ($asg->timezone ?? '')) === '') {
            $asg->timezone = trim((string) ($sibling?->timezone ?? '')) ?: 'Asia/Dubai';
            $dirty = true;
        }
        if (empty($asg->marker_user_id) && ! empty($sibling?->marker_user_id)) {
            $asg->marker_user_id = (int) $sibling->marker_user_id;
            $dirty = true;
        }
        if (empty($asg->assigned_by_user_id) && empty($asg->assigned_by_admin_id)) {
            if (! empty($sibling?->assigned_by_user_id) || ! empty($sibling?->assigned_by_admin_id)) {
                $asg->assigned_by_user_id = $sibling->assigned_by_user_id;
                $asg->assigned_by_admin_id = $sibling->assigned_by_admin_id;
            } else {
                $asg->assigned_by_user_id = $assessment->created_by_user_id ?? null;
                $asg->assigned_by_admin_id = $assessment->created_by_admin_id ?? null;
            }
            $dirty = true;
        }
        if ($dirty) {
            try {
                $asg->save();
            } catch (\Throwable $e) {
                report($e);
            }
        }
        if (! empty($asg->marker_user_id) && ! $asg->relationLoaded('marker')) {
            $marker = User::query()->find($asg->marker_user_id);
            if ($marker) {
                $asg->setRelation('marker', $marker);
            }
        }
        if (! empty($asg->assigned_by_user_id) && ! $asg->relationLoaded('assignedByUser')) {
            $creator = User::query()->find($asg->assigned_by_user_id);
            if ($creator) {
                $asg->setRelation('assignedByUser', $creator);
            }
        }
    }

    protected function ensureAssignmentForLiveAttempt($att, $assessment, $assignments, $assignmentsById): ?LmsAssessmentAssignment
    {
        $found = $this->assignmentForAttempt($att, $assignments, $assignmentsById);
        if ($found) {
            if (Schema::hasColumn('lms_assessment_attempts', 'assignment_id') && empty($att->assignment_id)) {
                try {
                    $att->assignment_id = (int) $found->id;
                    $att->save();
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return $found;
        }
        if (! $assessment || (method_exists($assessment, 'trashed') && $assessment->trashed())) {
            return null;
        }
        try {
            $sibling = $this->siblingSitting($assessment, $assignments);
            $asg = new LmsAssessmentAssignment();
            $asg->assessment_id = (int) $att->assessment_id;
            $asg->student_id = (int) $att->user_id;
            if (Schema::hasColumn($asg->getTable(), 'timezone')) {
                $asg->timezone = trim((string) ($sibling?->timezone ?? '')) ?: 'Asia/Dubai';
            }
            if (Schema::hasColumn($asg->getTable(), 'marker_user_id')) {
                $asg->marker_user_id = $sibling?->marker_user_id;
            }
            if (Schema::hasColumn($asg->getTable(), 'sitting_kind')) {
                $asg->sitting_kind = $sibling?->sitting_kind ?: LmsAssessmentAssignment::KIND_SELF;
            }
            if (Schema::hasColumn($asg->getTable(), 'assigned_by_user_id')) {
                $asg->assigned_by_user_id = $sibling?->assigned_by_user_id ?? ($assessment->created_by_user_id ?? null);
            }
            if (Schema::hasColumn($asg->getTable(), 'assigned_by_admin_id')) {
                $asg->assigned_by_admin_id = $sibling?->assigned_by_admin_id ?? ($assessment->created_by_admin_id ?? null);
            }
            if (Schema::hasColumn($asg->getTable(), 'available_from') && ! empty($att->started_at)) {
                $asg->available_from = $att->started_at;
            }
            $asg->save();
            if (Schema::hasColumn('lms_assessment_attempts', 'assignment_id')) {
                $att->assignment_id = (int) $asg->id;
                $att->save();
            }
            if (! empty($asg->marker_user_id)) {
                $marker = User::query()->find($asg->marker_user_id);
                if ($marker) {
                    $asg->setRelation('marker', $marker);
                }
            }
            if (! empty($asg->assigned_by_user_id)) {
                $creator = User::query()->find($asg->assigned_by_user_id);
                if ($creator) {
                    $asg->setRelation('assignedByUser', $creator);
                }
            }

            return $asg;
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
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
        $fromStudent = LmsAssessmentAssignment::query()
            ->where('assessment_id', (int) $att->assessment_id)
            ->where('student_id', (int) $att->user_id)
            ->orderByDesc('id')
            ->first();
        if ($fromStudent) {
            return $fromStudent;
        }
        $belong = collect($assignments)->first(
            fn ($row) => $this->assessments->attemptBelongsToAssignment($att, $row)
        );
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
                    ->orderByDesc('id')
                    ->first();
                if ($fromBatch) {
                    return $fromBatch;
                }
            }
        }

        return LmsAssessmentAssignment::query()
            ->where('assessment_id', (int) $att->assessment_id)
            ->orderByDesc('id')
            ->first();
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
        $hasTimezoneCol = Schema::hasColumn('lms_assessment_assignments', 'timezone');

        $created = 0;
        foreach ((array) $request->input('batch_ids', []) as $batchId) {
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
            if ($this->hasUnusedDuplicateSitting($a, $row, $hasKindCol)) {
                continue;
            }
            $createdAsg = LmsAssessmentAssignment::create($row);
            $this->assessments->notifyAssignmentCreated($createdAsg);
            $created++;
        }
        foreach ((array) $request->input('student_ids', []) as $studentId) {
            $studentId = (int) $studentId;
            if ($studentId <= 0) {
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
        $ids = $q->pluck('id');
        if ($ids->isEmpty()) {
            return false;
        }
        if (! Schema::hasColumn('lms_assessment_attempts', 'assignment_id')) {
            return true;
        }
        $used = LmsAssessmentAttempt::query()
            ->whereIn('assignment_id', $ids->all())
            ->pluck('assignment_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $ids->contains(fn ($id) => ! $used->contains((int) $id));
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
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->limit(400)
            ->get(['id', 'name', 'email']);

        $selectedMarker = old(
            'marker_user_id',
            $assessment->assignments->firstWhere('marker_user_id', '!=', null)?->marker_user_id
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
            'selectedQuestionIds' => old('question_ids', $assessment->questions->pluck('id')->all()),
            'selectedBatchIds' => old('batch_ids', $assessment->assignments->whereNotNull('class_batch_id')->pluck('class_batch_id')->all()),
            'selectedStudentIds' => old('student_ids', $assessment->assignments->whereNotNull('student_id')->pluck('student_id')->all()),
            'selectedMarkerId' => $selectedMarker,
            'timezoneOptions' => LmsAssessmentAssignment::timezoneOptions(),
            'selectedTimezone' => old(
                'timezone',
                $assessment->assignments->firstWhere('timezone', '!=', null)?->timezone
                    ?: config('app.timezone', 'Asia/Dubai')
            ),
            'canSkill' => $this->assessments->canManageSkillTests(),
            'canCreate' => $assessment->isSkill()
                ? $this->assessments->canManageSkillTests()
                : $this->assessments->canCreateStudentAssessments(),
            'zohoWorkDriveReady' => $this->zoho->isWorkDriveReady(),
            'canAssign' => $assessment->isSkill()
                ? $this->assessments->canManageSkillTests()
                : $this->assessments->canAssignStudentAssessments(),
            'courseRequired' => $this->lms->isInstructorActor()
                && ! $this->lms->isAdminActor()
                && ! panel_sees_all_dashboards()
                && ! $assessment->isSkill(),
        ];
    }
}
