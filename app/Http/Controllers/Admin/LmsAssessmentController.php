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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class LmsAssessmentController extends Controller
{
    public function __construct(
        protected LmsAssessmentService $assessments,
        protected StudyMaterialService $lms
    ) {
    }

    public function index(Request $request)
    {
        abort_unless($this->assessments->canManageStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
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
            'rows' => $query->with('course:id,title')->paginate(20)->withQueryString(),
            'kinds' => LmsAssessment::kinds(),
            'kind' => $kind,
            'search' => $search,
            'canSkill' => $this->assessments->canManageSkillTests(),
            'canCreate' => $this->assessments->canCreateStudentAssessments() || $this->assessments->canManageSkillTests(),
            'canAssign' => $this->assessments->canAssignStudentAssessments() || $this->assessments->canManageSkillTests(),
            'canDelete' => $this->assessments->canCreateStudentAssessments() || $this->assessments->canManageSkillTests()
                || $this->lms->isAdminActor() || panel_sees_all_dashboards(),
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
            'pass_percent' => 50,
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
        $this->assessments->ensure();
        $a = LmsAssessment::findOrFail($id);
        if ($a->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests() || $this->lms->isAdminActor() || panel_sees_all_dashboards(), 403);
        } else {
            abort_unless(
                $this->lms->isAdminActor()
                || panel_sees_all_dashboards()
                || $this->assessments->canCreateStudentAssessments(),
                403
            );
            $this->assertInstructorOwnsCourse((int) $a->course_id);
        }
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

        return view('admin.assessments.tests.assign', $this->formData($assessment));
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
            'available_until' => 'nullable|date|after:available_from',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->syncAssignments($a, $request);

        return redirect()->route('admin.assessments.tests.assigned-users')
            ->with('success', 'Assignments saved for “' . $a->title . '”.');
    }

    public function assignedUsers(Request $request)
    {
        abort_unless($this->assessments->canAssignStudentAssessments() || $this->assessments->canManageSkillTests() || $this->assessments->canManageStudentAssessments(), 403);
        $this->assessments->ensure();

        $query = LmsAssessmentAssignment::query()
            ->with([
                'assessment:id,title,kind,course_id,target_role',
                'assessment.course:id,title',
                'batch:id,code,name',
                'student:id,name,email',
                'marker:id,name,email',
            ])
            ->orderByDesc('id');

        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIds = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $query->whereHas('assessment', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds ?: [0]);
            });
        }

        $search = trim((string) $request->get('search', ''));
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($like, $search) {
                $q->whereHas('assessment', fn ($aq) => $aq->where('title', 'like', $like))
                    ->orWhereHas('student', fn ($sq) => $sq->where('name', 'like', $like)->orWhere('email', 'like', $like))
                    ->orWhereHas('batch', fn ($bq) => $bq->where('name', 'like', $like)->orWhere('code', 'like', $like));
                if (ctype_digit($search)) {
                    $q->orWhere('assessment_id', (int) $search);
                }
            });
        }

        return view('admin.assessments.tests.assigned-users', [
            'rows' => $query->paginate(30)->withQueryString(),
            'search' => $search,
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
        if ($this->plainTextLen($request->input('grader_feedback')) > $overallMax) {
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
        $a->pass_percent = $request->filled('pass_percent') ? (float) $request->input('pass_percent') : null;
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
        LmsAssessmentAssignment::query()->where('assessment_id', $a->id)->delete();

        $from = $request->filled('available_from') ? $request->input('available_from') : null;
        $until = $request->filled('available_until') ? $request->input('available_until') : null;
        $byAdmin = $this->lms->isAdminActor() ? Auth::guard('admin')->id() : null;
        $byUser = ! $byAdmin ? Auth::id() : null;
        $markerId = $request->filled('marker_user_id') ? (int) $request->input('marker_user_id') : null;
        $hasMarkerCol = Schema::hasColumn('lms_assessment_assignments', 'marker_user_id');

        foreach ((array) $request->input('batch_ids', []) as $batchId) {
            $row = [
                'assessment_id' => $a->id,
                'class_batch_id' => (int) $batchId,
                'available_from' => $from,
                'available_until' => $until,
                'assigned_by_admin_id' => $byAdmin,
                'assigned_by_user_id' => $byUser,
            ];
            if ($hasMarkerCol) {
                $row['marker_user_id'] = $markerId;
            }
            LmsAssessmentAssignment::create($row);
        }
        foreach ((array) $request->input('student_ids', []) as $studentId) {
            $row = [
                'assessment_id' => $a->id,
                'student_id' => (int) $studentId,
                'available_from' => $from,
                'available_until' => $until,
                'assigned_by_admin_id' => $byAdmin,
                'assigned_by_user_id' => $byUser,
            ];
            if ($hasMarkerCol) {
                $row['marker_user_id'] = $markerId;
            }
            LmsAssessmentAssignment::create($row);
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
