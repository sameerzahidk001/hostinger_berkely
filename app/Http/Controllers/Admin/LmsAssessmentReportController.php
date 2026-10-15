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
        // Merged into Assigned Tests (one menu for all assigned takers + status/results).
        return redirect()->route('admin.assessments.tests.assigned-users', $request->query());
    }

    public function destroyAttempt($attemptId)
    {
        abort_unless($this->lms->isAdminActor() || panel_sees_all_dashboards(), 403);
        $this->assessments->ensure();

        $attempt = LmsAssessmentAttempt::with('assessment')->findOrFail($attemptId);
        $attempt->answers()->delete();
        $attempt->delete();

        return back()->with('success', 'Attempt removed.');
    }
}
