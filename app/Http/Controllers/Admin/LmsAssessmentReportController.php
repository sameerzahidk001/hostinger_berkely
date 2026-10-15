<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAttempt;
use App\Services\LmsAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class LmsAssessmentReportController extends Controller
{
    public function __construct(protected LmsAssessmentService $assessments)
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->assessments->canManageStudentAssessments() || $this->assessments->canManageSkillTests(), 403);
        $this->assessments->ensure();

        $assessmentId = (int) $request->get('assessment_id', 0);
        $batchId = (int) $request->get('batch_id', 0);

        $studentAttempts = collect();
        $selectedAssessment = null;
        if ($assessmentId > 0) {
            $selectedAssessment = LmsAssessment::query()->find($assessmentId);
            if ($selectedAssessment && $selectedAssessment->isSkill()) {
                abort_unless($this->assessments->canManageSkillTests(), 403);
            }
            $query = LmsAssessmentAttempt::query()
                ->with('user:id,name,email')
                ->where('assessment_id', $assessmentId)
                ->whereIn('status', ['submitted', 'graded', 'expired', 'in_progress'])
                ->orderByDesc('id');

            if ($batchId > 0 && Schema::hasTable('class_batches')) {
                $studentIds = ClassBatch::find($batchId)?->students()->pluck('users.id')->all() ?? [];
                $query->whereIn('user_id', $studentIds ?: [0]);
            }

            $studentAttempts = $query->limit(200)->get();
        }

        $assessmentQuery = LmsAssessment::query()->orderBy('title');
        if (! $this->assessments->canManageSkillTests()) {
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
            ],
        ]);
    }
}
