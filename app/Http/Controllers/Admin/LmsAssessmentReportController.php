<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\LmsAssessment;
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
        abort_unless($this->assessments->canManageAssessments(), 403);
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

        return view('admin.assessments.reports.index', [
            'passRate' => $passRate,
            'itemAnalysis' => $itemAnalysis,
            'skillRows' => $skillRows,
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
