<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAttempt;
use App\Services\LmsAssessmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LmsAssessmentPortalController extends Controller
{
    public function __construct(protected LmsAssessmentService $assessments)
    {
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();

        $section = $request->get('section', 'all'); // all|mocks|practice|skill
        $tab = $request->get('tab', 'available'); // available|upcoming|in_progress|submitted|results|missed
        if (! in_array($tab, ['available', 'upcoming', 'in_progress', 'submitted', 'results', 'missed'], true)) {
            $tab = 'available';
        }
        $kind = match ($section) {
            'mocks' => LmsAssessment::KIND_MOCK,
            'practice' => LmsAssessment::KIND_PRACTICE,
            'skill' => LmsAssessment::KIND_SKILL,
            default => null,
        };

        // General tests included in "all"; practice section also shows general
        $buckets = $this->assessments->bucketForUser($user, $kind);
        if ($section === 'practice') {
            $general = $this->assessments->bucketForUser($user, LmsAssessment::KIND_GENERAL);
            foreach (['available', 'upcoming', 'inProgress', 'submitted', 'results', 'missed'] as $key) {
                $buckets[$key] = $buckets[$key]->merge($general[$key])->unique(fn ($row) => $row['assessment']->id);
            }
        }

        $list = match ($tab) {
            'upcoming' => $buckets['upcoming'],
            'in_progress' => $buckets['inProgress'],
            'submitted' => $buckets['submitted'],
            'results' => $buckets['results'],
            'missed' => $buckets['missed'],
            default => $buckets['available'],
        };

        return view('user.assessments.index', [
            'list' => $list,
            'counts' => [
                'available' => $buckets['available']->count(),
                'upcoming' => $buckets['upcoming']->count(),
                'in_progress' => $buckets['inProgress']->count(),
                'submitted' => $buckets['submitted']->count(),
                'results' => $buckets['results']->count(),
                'missed' => $buckets['missed']->count(),
            ],
            'section' => $section,
            'tab' => $tab,
            'isInstructor' => $user->roles()->where('name', 'instructor')->exists(),
        ]);
    }

    public function mocks(Request $request)
    {
        $request->merge(['section' => 'mocks']);

        return $this->index($request);
    }

    public function start($id)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $assessment = LmsAssessment::with('questions')->findOrFail($id);
        $attempt = $this->assessments->startAttempt($user, $assessment);

        return redirect()->route('user.assessments.take', $attempt->id);
    }

    public function take($attemptId)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['assessment.questions', 'answers'])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        if ($attempt->isTimedOut()) {
            $this->assessments->submitAttempt($attempt, true);

            return redirect()->route('user.assessments.result', $attempt->id)
                ->with('fail', 'Time expired. Your attempt was submitted.');
        }
        abort_unless($attempt->isInProgress(), 403);

        $questions = $attempt->assessment->questions;
        if ($attempt->assessment->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }
        $answers = $attempt->answers->keyBy('question_id');

        return view('user.assessments.take', compact('attempt', 'questions', 'answers'));
    }

    public function save(Request $request, $attemptId)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::where('user_id', $user->id)->findOrFail($attemptId);
        $this->assessments->saveAnswers($attempt, (array) $request->input('answers', []));

        if ($request->boolean('submit')) {
            $this->assessments->submitAttempt($attempt->fresh());

            return redirect()->route('user.assessments.result', $attempt->id)
                ->with('success', 'Submitted. Results appear when published (MCQ-only instant results may show immediately).');
        }

        return back()->with('success', 'Answers saved.');
    }

    public function result($attemptId)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['assessment.questions', 'answers.question'])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        $canSeeScore = $attempt->results_published
            || ($attempt->assessment->results_mode === 'instant_mcq' && $attempt->status === 'graded');

        return view('user.assessments.result', compact('attempt', 'canSeeScore'));
    }
}
