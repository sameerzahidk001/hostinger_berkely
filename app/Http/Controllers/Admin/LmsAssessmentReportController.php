<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\LmsAssessment;
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
        $status = trim((string) $request->get('status', ''));

        $selectedAssessment = $assessmentId > 0
            ? LmsAssessment::query()->find($assessmentId)
            : null;

        if ($selectedAssessment && $selectedAssessment->isSkill()) {
            abort_unless($this->assessments->canManageSkillTests(), 403);
        }

        $query = LmsAssessmentAttempt::query()
            ->with(['user:id,name,email', 'assessment:id,title,kind,course_id,audience'])
            ->whereIn('status', ['submitted', 'graded', 'expired', 'in_progress'])
            ->orderByDesc('id');

        if ($assessmentId > 0) {
            $query->where('assessment_id', $assessmentId);
        }

        if ($status !== '' && in_array($status, ['in_progress', 'submitted', 'graded', 'expired'], true)) {
            $query->where('status', $status);
        }

        if ($batchId > 0 && Schema::hasTable('class_batches')) {
            $studentIds = ClassBatch::find($batchId)?->students()->pluck('users.id')->all() ?? [];
            $query->whereIn('user_id', $studentIds ?: [0]);
        }

        // Instructors only see attempts for their courses (plus skill if allowed).
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIds = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $query->whereHas('assessment', function ($q) use ($courseIds) {
                $q->where(function ($inner) use ($courseIds) {
                    $inner->whereIn('course_id', $courseIds ?: [0]);
                    if ($this->assessments->canManageSkillTests()) {
                        $inner->orWhere('kind', LmsAssessment::KIND_SKILL)
                            ->orWhere('audience', LmsAssessment::AUDIENCE_STAFF);
                    }
                });
            });
        } elseif (! $this->assessments->canManageSkillTests()) {
            $query->whereHas('assessment', function ($q) {
                $q->where('kind', '!=', LmsAssessment::KIND_SKILL)
                    ->where('audience', '!=', LmsAssessment::AUDIENCE_STAFF);
            });
        }

        $studentAttempts = $query->limit(300)->get();

        $assessmentQuery = LmsAssessment::query()->orderBy('title');
        if ($this->lms->isInstructorActor() && ! $this->lms->isAdminActor() && ! panel_sees_all_dashboards()) {
            $courseIds = $this->lms->coursesForActor()->pluck('id')->map(fn ($id) => (int) $id)->all();
            $assessmentQuery->where(function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds ?: [0]);
                if ($this->assessments->canManageSkillTests()) {
                    $q->orWhere('kind', LmsAssessment::KIND_SKILL)
                        ->orWhere('audience', LmsAssessment::AUDIENCE_STAFF);
                }
            });
        } elseif (! $this->assessments->canManageSkillTests()) {
            $assessmentQuery->where('kind', '!=', LmsAssessment::KIND_SKILL)
                ->where('audience', '!=', LmsAssessment::AUDIENCE_STAFF);
        }

        return view('admin.assessments.reports.index', [
            'studentAttempts' => $studentAttempts,
            'selectedAssessment' => $selectedAssessment,
            'assessments' => $assessmentQuery->get(['id', 'title', 'kind']),
            'batches' => Schema::hasTable('class_batches')
                ? ClassBatch::query()->orderByDesc('id')->get(['id', 'code', 'name'])
                : collect(),
            'filters' => [
                'assessment_id' => $assessmentId,
                'batch_id' => $batchId,
                'status' => $status,
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
