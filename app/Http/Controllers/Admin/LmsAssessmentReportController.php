<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAssignment;
use App\Models\LmsAssessmentAttempt;
use App\Services\LmsAssessmentService;
use App\Services\StudyMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LmsAssessmentReportController extends Controller
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

        $assessmentId = (int) $request->get('assessment_id', 0);
        $batchId = (int) $request->get('batch_id', 0);
        $courseId = (int) $request->get('course_id', 0);

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
                'assessment:id,title,kind,course_id,audience',
                'student:id,name,email',
                'batch:id,code,name',
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

        // Expand batch assignments to individual students.
        $rowsByKey = [];
        $batchStudentCache = [];

        foreach ($assignments as $assignment) {
            /** @var LmsAssessmentAssignment $assignment */
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
                // Direct student assign: include only if student is in filtered batch.
                $inBatch = ClassBatch::find($batchId)?->students()->where('users.id', $assignment->student_id)->exists();
                if (! $inBatch) {
                    continue;
                }
            }

            foreach ($students as $student) {
                $key = (int) $assessment->id . ':' . (int) $student->id;
                // Keep the newest assignment row for the pair.
                if (isset($rowsByKey[$key]) && (int) $rowsByKey[$key]['assignment']->id >= (int) $assignment->id) {
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

        // Attach latest attempt per assessment+user.
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
                $row['attempt'] = optional($attempts->get($key))->first();
            }
            unset($row);
        }

        $rows = collect($rowsByKey)->values()->sortByDesc(function ($row) {
            $attempt = $row['attempt'];
            if ($attempt) {
                return optional($attempt->submitted_at ?: $attempt->updated_at)->timestamp ?? 0;
            }

            return optional($row['assignment']->created_at)->timestamp ?? 0;
        })->values()->take(300);

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

        $courses = $this->lms->coursesForActor();

        return view('admin.assessments.reports.index', [
            'rows' => $rows,
            'selectedAssessment' => $selectedAssessment,
            'assessments' => $assessmentQuery->get(['id', 'title', 'kind']),
            'batches' => Schema::hasTable('class_batches')
                ? ClassBatch::query()->orderByDesc('id')->get(['id', 'code', 'name'])
                : collect(),
            'courses' => $courses,
            'filters' => [
                'assessment_id' => $assessmentId,
                'batch_id' => $batchId,
                'course_id' => $courseId,
            ],
        ]);
    }

    public function destroyAttempt($attemptId)
    {
        abort_unless($this->assessments->canManageStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();

        $attempt = LmsAssessmentAttempt::with('assessment')->findOrFail($attemptId);
        $assessment = $attempt->assessment;
        if ($assessment?->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        } else {
            abort_unless($this->assessments->canManageStudentAssessments(), 403);
            if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
                $courseId = (int) ($assessment?->course_id ?? 0);
                abort_unless($courseId > 0 && $this->lms->instructorAssignedToCourse($courseId), 403);
            }
        }

        $attempt->answers()->delete();
        $attempt->delete();

        return back()->with('success', 'Attempt removed.');
    }
}
