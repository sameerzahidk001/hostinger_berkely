<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAssignment;
use App\Models\LmsAssessmentAttempt;
use App\Models\LmsQuestion;
use App\Models\User;
use App\Services\LmsAssessmentService;
use App\Services\StudyMaterialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LmsAssessmentController extends Controller
{
    public function __construct(
        protected LmsAssessmentService $assessments,
        protected StudyMaterialService $lms
    ) {
    }

    public function index(Request $request)
    {
        // Create Test page: create permission (or skill admin). Assign-only users use Assigned Tests.
        abort_unless($this->assessments->canCreateStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();

        $kind = trim((string) $request->get('kind', ''));
        $search = trim((string) $request->get('search', ''));
        $query = LmsAssessment::query()->withCount(['questions', 'assignments', 'attempts'])->orderByDesc('id');

        if ($kind !== '' && array_key_exists($kind, LmsAssessment::kinds())) {
            $query->where('kind', $kind);
        }
        if (! $this->assessments->canManageSkillTests()) {
            $query->where('kind', '!=', LmsAssessment::KIND_SKILL)
                ->where('audience', '!=', LmsAssessment::AUDIENCE_STAFF);
        }
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIds = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $query->whereIn('course_id', $courseIds ?: [0]);
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
                'course:id,title',
                'assignments' => fn ($q) => $q->with('marker:id,name')->whereNotNull('marker_user_id'),
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

        return redirect()->route('admin.assessments.tests.edit', $a->id)->with('success', 'Test created. Use Assign to give it to users or batches.');
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
        abort_unless($this->assessments->canAssignStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();
        $assessment = LmsAssessment::with(['assignments.batch', 'assignments.student', 'assignments.marker', 'course'])->findOrFail($id);
        if ($assessment->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canAssignStudentAssessments(), 403);
            $this->assertInstructorOwnsCourse((int) $assessment->course_id);
        }

        // Assign = add new people only — never preload existing assignees.
        return view('admin.assessments.tests.assign', array_merge($this->formData($assessment), [
            'selectedBatchIds' => old('batch_ids', []),
            'selectedStudentIds' => old('student_ids', []),
            'selectedMarkerId' => old('marker_user_id'),
            'selectedTimezone' => old('timezone', config('app.timezone', 'Asia/Dubai')),
            'assignFresh' => true,
        ]));
    }

    public function saveAssign(Request $request, $id)
    {
        abort_unless($this->assessments->canAssignStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();
        $a = LmsAssessment::findOrFail($id);
        if ($a->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canAssignStudentAssessments(), 403);
            $this->assertInstructorOwnsCourse((int) $a->course_id);
        }

        $validator = Validator::make($request->all(), [
            'batch_ids' => 'nullable|array',
            'batch_ids.*' => 'exists:class_batches,id',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:users,id',
            'marker_user_id' => 'nullable|exists:users,id',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'timezone' => 'nullable|string|max:64',
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

        return redirect()->route('admin.assessments.tests.assigned-users', ['assessment_id' => $a->id])
            ->with('success', $created . ' new assignment' . ($created === 1 ? '' : 's') . ' created for “' . $a->title . '” (same test, new sitting).');
    }

    /**
     * One list for all assigned test takers (started or not) with status / score / mark actions.
     * Replaces the separate Test Results menu.
     */
    public function assignedUsers(Request $request)
    {
        abort_unless(
            $this->assessments->canAssignStudentAssessments()
            || $this->assessments->canManageSkillTests()
            || $this->assessments->canManageStudentAssessments(),
            403
        );
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
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIdsScope = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $assignQuery = LmsAssessmentAssignment::query()
            ->with([
                'assessment:id,title,kind,course_id,audience,duration_minutes,target_role',
                'assessment.course:id,title',
                'student:id,name,email',
                'batch:id,code,name',
                'marker:id,name',
                'assignedByUser:id,name',
            ])
            ->orderByDesc('id');

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

        $assignQuery->whereHas('assessment', function ($q) use ($courseIdsScope) {
            if ($courseIdsScope !== null) {
                $q->where(function ($inner) use ($courseIdsScope) {
                    $inner->whereIn('course_id', $courseIdsScope ?: [0]);
                    if ($this->assessments->canManageSkillTests()) {
                        $inner->orWhere('kind', LmsAssessment::KIND_SKILL)
                            ->orWhere('audience', LmsAssessment::AUDIENCE_STAFF);
                    }
                });
            } elseif (! $this->assessments->canManageSkillTests()) {
                $q->where('kind', '!=', LmsAssessment::KIND_SKILL)
                    ->where('audience', '!=', LmsAssessment::AUDIENCE_STAFF);
            }
        });

        $assignments = $assignQuery->limit(400)->get();
        $rowsByKey = [];
        $batchStudentCache = [];

        foreach ($assignments as $assignment) {
            $assessment = $assignment->assessment;
            if (! $assessment) {
                continue;
            }

            $students = collect();
            if ($assignment->student_id && $assignment->student) {
                $students->push($assignment->student);
            } elseif ($assignment->class_batch_id) {
                $bid = (int) $assignment->class_batch_id;
                if ($batchId > 0 && $bid !== $batchId) {
                    continue;
                }
                if (! isset($batchStudentCache[$bid])) {
                    $batch = ClassBatch::query()->find($bid);
                    $batchStudentCache[$bid] = $batch
                        ? $batch->students()->get(['users.id', 'users.name', 'users.email'])
                        : collect();
                }
                $students = $batchStudentCache[$bid];
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

        $pairs = collect($rowsByKey)->values();
        if ($pairs->isNotEmpty()) {
            $assessmentIds = $pairs->pluck('assessment.id')->unique()->all();
            $userIds = $pairs->pluck('student.id')->unique()->all();
            $attempts = LmsAssessmentAttempt::query()
                ->whereIn('assessment_id', $assessmentIds)
                ->whereIn('user_id', $userIds)
                ->orderByDesc('id')
                ->get()
                ->groupBy(fn ($a) => (int) $a->assessment_id . ':' . (int) $a->user_id);

            foreach ($rowsByKey as $key => &$row) {
                $pairKey = (int) $row['assessment']->id . ':' . (int) $row['student']->id;
                $candidates = $attempts->get($pairKey) ?? collect();
                $asg = $row['assignment'];
                // Prefer attempt that started inside this sitting's appointment window.
                $row['attempt'] = $candidates->first(function ($att) use ($asg) {
                    if (! $att->started_at || ! $asg->available_from) {
                        return false;
                    }
                    $end = $asg->appointmentEnd() ?: $asg->available_from->copy()->addDays(7);
                    return $att->started_at->gte($asg->available_from->copy()->subMinutes(5))
                        && $att->started_at->lte($end->copy()->addDay());
                });
                // Fallback: only latest sitting without a window match gets the latest attempt.
                if (! $row['attempt'] && $candidates->isNotEmpty()) {
                    $latestAsgId = collect($rowsByKey)
                        ->filter(fn ($r) => (int) $r['assessment']->id === (int) $row['assessment']->id
                            && (int) $r['student']->id === (int) $row['student']->id)
                        ->max(fn ($r) => (int) $r['assignment']->id);
                    if ((int) $asg->id === (int) $latestAsgId) {
                        $row['attempt'] = $candidates->first();
                    }
                }
            }
            unset($row);
        }

        $rows = collect($rowsByKey)->values()->sortByDesc(function ($row) {
            $attempt = $row['attempt'];
            if ($attempt) {
                return optional($attempt->submitted_at ?: $attempt->updated_at)->timestamp ?? 0;
            }

            return optional($row['assignment']->created_at)->timestamp ?? 0;
        })->values();

        $statusOptions = [
            'Upcoming' => 'Upcoming',
            'Assigned' => 'Assigned',
            'In progress' => 'In progress',
            'Marking Required' => 'Marking Required',
            'Completed and Marked' => 'Completed and Marked',
            'Missed' => 'Missed',
        ];
        if ($statusFilter !== '' && array_key_exists($statusFilter, $statusOptions)) {
            $rows = $rows->filter(function ($row) use ($statusFilter) {
                $attempt = $row['attempt'];
                $assignment = $row['assignment'];
                if ($attempt) {
                    $statusText = $attempt->statusLabel();
                } elseif ($assignment->isNotYetOpen()) {
                    $statusText = 'Upcoming';
                } elseif ($assignment->isExpired()) {
                    $statusText = 'Missed';
                } else {
                    $statusText = 'Assigned';
                }

                return $statusText === $statusFilter;
            })->values();
        }

        $rows = $rows->take(300);

        $assessmentQuery = LmsAssessment::query()->orderBy('title');
        if ($courseIdsScope !== null) {
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

        return view('admin.assessments.tests.assigned-users', [
            'rows' => $rows,
            'selectedAssessment' => $selectedAssessment,
            'assessments' => $assessmentQuery->get(['id', 'title', 'kind']),
            'batches' => Schema::hasTable('class_batches')
                ? ClassBatch::query()->orderByDesc('id')->get(['id', 'code', 'name'])
                : collect(),
            'courses' => $this->lms->coursesForActor(),
            'kinds' => $kinds,
            'statusOptions' => $statusOptions,
            'canDeleteAttempts' => $this->lms->isAdminActor() || panel_sees_all_dashboards(),
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
        abort_unless($this->assessments->canManageStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();
        $assessment = LmsAssessment::findOrFail($id);
        if ($assessment->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canManageStudentAssessments(), 403);
            $this->assertInstructorOwnsCourse((int) $assessment->course_id);
        }
        $rows = LmsAssessmentAttempt::query()
            ->with('user:id,name,email')
            ->where('assessment_id', $assessment->id)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.assessments.tests.attempts', compact('assessment', 'rows'));
    }

    public function mark($attemptId)
    {
        abort_unless($this->assessments->canManageStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['assessment.questions', 'answers.question', 'user'])->findOrFail($attemptId);
        if ($attempt->assessment?->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canManageStudentAssessments(), 403);
            $this->assertInstructorOwnsCourse((int) ($attempt->assessment?->course_id ?? 0));
        }

        return view('admin.assessments.tests.mark', compact('attempt'));
    }

    public function attemptPdf($attemptId)
    {
        abort_unless($this->assessments->canManageStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['assessment.course', 'assessment.questions', 'answers.question', 'user'])->findOrFail($attemptId);
        if ($attempt->assessment?->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canManageStudentAssessments(), 403);
            $this->assertInstructorOwnsCourse((int) ($attempt->assessment?->course_id ?? 0));
        }

        $title = Str::slug($attempt->assessment->title ?? 'attempt', '-');
        $student = Str::slug($attempt->user->name ?? 'student', '-');
        $filename = 'attempt-' . $attempt->id . '-' . $title . '-' . $student . '.pdf';

        return Pdf::loadView('admin.assessments.tests.attempt-pdf', compact('attempt'))
            ->setPaper('a4')
            ->download($filename);
    }

    public function saveMarks(Request $request, $attemptId)
    {
        abort_unless($this->assessments->canManageStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['answers.question', 'assessment'])->findOrFail($attemptId);

        $feedbackMax = 2000;
        $overallMax = 5000;
        $errors = [];
        foreach ((array) $request->input('feedback', []) as $answerId => $feedback) {
            if ($this->plainTextLen($feedback) > $feedbackMax) {
                $errors['feedback.' . $answerId] = 'Feedback exceeds ' . number_format($feedbackMax) . ' characters.';
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

        foreach ((array) $request->input('manual', []) as $answerId => $score) {
            $answer = $attempt->answers->firstWhere('id', (int) $answerId);
            if (! $answer || ! $answer->question || ! $answer->question->needsManualMark()) {
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
            return redirect()->route('admin.assessments.tests.attempts', $attempt->assessment_id)
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
        abort_unless($this->assessments->canManageStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::findOrFail($attemptId);
        $this->assessments->publishResults($attempt);

        return back()->with('success', 'Results published.');
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
            'description' => 'nullable|string|max:5000',
            'kind' => 'required|in:practice,mock,general,skill',
            'course_id' => ($courseRequired ? 'required' : 'nullable') . '|exists:courses,id',
            'target_role' => ($isSkill ? 'required' : 'nullable') . '|in:' . implode(',', array_keys(LmsAssessment::targetRoles())),
            'duration_minutes' => 'nullable|integer|min:1',
            'pass_percent' => 'nullable|numeric|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1',
            'shuffle_questions' => 'nullable|boolean',
            'results_mode' => 'required|in:instant_mcq,after_publish',
            'status' => 'required|in:draft,published,disabled',
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'exists:lms_questions,id',
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
        abort_unless($courseId > 0 && $this->lms->instructorAssignedToCourse($courseId), 403);
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
        $a->max_attempts = $request->filled('max_attempts') ? (int) $request->input('max_attempts') : null;
        $a->shuffle_questions = $request->boolean('shuffle_questions');
        $a->results_mode = $request->input('results_mode', 'after_publish');
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

    protected function syncAssignments(LmsAssessment $a, Request $request): void
    {
        // Each Save = new assignment sitting(s) for this same test. Never delete older sittings.
        $tzOptions = LmsAssessmentAssignment::timezoneOptions();
        $timezone = trim((string) $request->input('timezone', ''));
        if ($timezone === '' || ! array_key_exists($timezone, $tzOptions)) {
            $timezone = (string) config('app.timezone', 'Asia/Dubai');
        }

        $from = null;
        $until = null;
        if ($request->filled('available_from')) {
            // Wall-clock time is in the chosen timezone (not the browser TZ).
            $fromLocal = \Carbon\Carbon::parse($request->input('available_from'), $timezone);
            $from = $fromLocal->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
            // Appointment window length = exam duration from start (student may start within that window).
            if ($a->duration_minutes) {
                $until = $fromLocal->copy()
                    ->addMinutes((int) $a->duration_minutes)
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
            LmsAssessmentAssignment::create($row);
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
            LmsAssessmentAssignment::create($row);
            $created++;
        }

        // Stash count for flash message.
        $request->attributes->set('lms_assignments_created', $created);
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
