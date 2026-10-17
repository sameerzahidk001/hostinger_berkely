<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAssignment;
use App\Models\LmsAssessmentAttempt;
use App\Models\User;
use App\Services\LmsAssessmentService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
                $buckets[$key] = $buckets[$key]->merge($general[$key])->unique(
                    fn ($row) => (int) $row['assessment']->id . ':' . (int) ($row['assignment']->id ?? 0)
                );
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
            'isStaff' => $user->roles()->whereIn('name', $this->assessments->staffRoleNames())->exists(),
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

        $essayMax = 15000;
        $errors = [];
        foreach ((array) $request->input('answers', []) as $questionId => $payload) {
            if (! is_array($payload)) {
                continue;
            }
            $text = $payload['text'] ?? null;
            if ($text !== null && mb_strlen((string) $text) > $essayMax) {
                $errors['answers.' . $questionId . '.text'] = 'Answer exceeds ' . number_format($essayMax) . ' characters.';
            }
            foreach ((array) ($payload['parts'] ?? []) as $partKey => $partText) {
                if (mb_strlen((string) $partText) > $essayMax) {
                    $errors['answers.' . $questionId . '.parts.' . $partKey] = 'Answer part exceeds ' . number_format($essayMax) . ' characters.';
                }
            }
        }
        if ($errors !== []) {
            return back()->withErrors($errors)->withInput();
        }

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

        // Retake only if admin assigned a new open sitting for this test.
        $canRetake = $this->assessments->canRetake($user, $attempt->assessment);

        $assignment = \App\Models\LmsAssessmentAssignment::query()
            ->where('assessment_id', $attempt->assessment_id)
            ->orderBy('id')
            ->get()
            ->first(fn ($asg) => $this->assessments->attemptBelongsToAssignment($attempt, $asg));
        $meta = $this->assessments->sittingAttemptMeta((int) $user->id, $attempt->assessment, $assignment);
        $appointmentId = $assignment?->displayCode();
        $attemptLabel = 'Attempt ' . $meta['number'] . ' of ' . $meta['total'];

        return view('user.assessments.result', compact(
            'attempt',
            'canSeeScore',
            'canRetake',
            'appointmentId',
            'attemptLabel'
        ));
    }

    public function resultPdf($attemptId)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['assessment.course', 'assessment.questions', 'answers.question', 'user'])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        $canSeeScore = $attempt->results_published
            || ($attempt->assessment->results_mode === 'instant_mcq' && $attempt->status === 'graded');
        abort_unless($canSeeScore, 403, 'Results are not published yet.');

        $title = Str::slug($attempt->assessment->title ?? 'attempt', '-');
        $filename = 'my-result-' . $attempt->id . '-' . $title . '.pdf';

        return Pdf::loadView('admin.assessments.tests.attempt-pdf', compact('attempt'))
            ->setPaper('a4')
            ->download($filename);
    }

    public function userPreview($userId)
    {
        $me = Auth::user();
        abort_unless($me, 403);
        $this->assessments->ensure();
        $subject = User::query()->with('roles:id,name')->findOrFail($userId);
        $id = (int) $subject->id;

        $batchIds = [];
        if (Schema::hasTable('class_batch_student')) {
            $batchIds = ClassBatch::query()
                ->whereHas('students', fn ($q) => $q->where('users.id', $me->id))
                ->pluck('id')
                ->map(fn ($bid) => (int) $bid)
                ->all();
        }

        $allowed = LmsAssessmentAssignment::query()
            ->where('marker_user_id', $id)
            ->where(function ($q) use ($me, $batchIds) {
                $q->where('student_id', $me->id);
                if ($batchIds !== []) {
                    $q->orWhereIn('class_batch_id', $batchIds);
                }
            })
            ->exists();
        abort_unless($allowed, 403);

        return response()->json($this->assessments->userPreviewPayload($subject));
    }
}
