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

        $passRate = $this->assessments->passRateReport(
            $assessmentId > 0 ? $assessmentId : null,
            $batchId > 0 ? $batchId : null
        );

        $itemAnalysis = $assessmentId > 0
            ? $this->assessments->mcqItemAnalysis($assessmentId)
            : collect();

        $skillRows = $this->assessments->canManageSkillTests()
            ? $this->assessments->skillReport()->take(50)
            : collect();

        $studentAttempts = collect();
        $selectedAssessment = null;
        if ($assessmentId > 0) {
            $selectedAssessment = LmsAssessment::query()->find($assessmentId);
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

        return view('admin.assessments.reports.index', [
            'passRate' => $passRate,
            'itemAnalysis' => $itemAnalysis,
            'skillRows' => $skillRows,
            'studentAttempts' => $studentAttempts,
            'selectedAssessment' => $selectedAssessment,
            'assessments' => LmsAssessment::query()->orderBy('title')->get(['id', 'title', 'kind']),
            'batches' => Schema::hasTable('class_batches')
                ? ClassBatch::query()->orderByDesc('id')->get(['id', 'code', 'name'])
                : collect(),
            'filters' => [
                'assessment_id' => $assessmentId,
                'batch_id' => $batchId,
            ],
            'canSkill' => $this->assessments->canManageSkillTests(),
        ]);
    }
}
