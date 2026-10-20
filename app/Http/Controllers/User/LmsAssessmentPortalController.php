<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\LmsAssessment;
use App\Models\LmsAssessmentAssignment;
use App\Models\LmsAssessmentAttempt;
use App\Models\User;
use App\Services\LmsAssessmentService;
use App\Services\ZohoLmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $this->assessments->autoSubmitTimedOutForUser($user);

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

    public function start(Request $request, $id)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $assessment = LmsAssessment::with('questions')->findOrFail($id);
        $assignmentId = $request->filled('assignment') ? (int) $request->input('assignment') : null;
        $folderId = $request->filled('folder') ? (int) $request->input('folder') : null;
        $attempt = $this->assessments->startAttempt($user, $assessment, $assignmentId ?: null, $folderId ?: null);

        return redirect()->route('user.assessments.take', $attempt->id);
    }

    public function take($attemptId)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::with(['assessment.questions', 'assessment.course', 'answers'])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        if ($attempt->isTimedOut()) {
            $this->assessments->submitAttempt($attempt, true);

            return redirect()->route('user.assessments.result', $attempt->id)
                ->with('success', 'Time was up. Your attempt was submitted.');
        }
        if (! $attempt->isInProgress()) {
            return redirect()->route('user.assessments.result', $attempt->id);
        }

        $questions = $attempt->assessment->questions;
        if ($attempt->assessment->shuffle_questions) {
            $questions = $questions->shuffle()->values();
        }
        $answers = $attempt->answers->keyBy('question_id');
        $assignment = LmsAssessmentAssignment::query()
            ->where('assessment_id', $attempt->assessment_id)
            ->orderBy('id')
            ->get()
            ->first(fn ($asg) => $this->assessments->attemptBelongsToAssignment($attempt, $asg));

        return view('user.assessments.take', compact('attempt', 'questions', 'answers', 'assignment'));
    }

    public function resource(Request $request, $attemptId, $kind, $index)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $kind = $kind === 'preseen' ? 'preseen' : 'reference';
        $attempt = LmsAssessmentAttempt::with('assessment')
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);
        $files = $attempt->assessment->resourceFiles($kind);
        $i = (int) $index;
        abort_unless(isset($files[$i]), 404);
        $path = (string) ($files[$i]['url'] ?? $files[$i]['path'] ?? '');
        abort_unless($path !== '', 404);
        $name = str_replace('"', '', (string) ($files[$i]['name'] ?? 'file'));
        $zoho = app(ZohoLmsService::class);
        $viewKind = $this->examResourceKind($name, (string) ($files[$i]['mime'] ?? ''));

        if (! $request->boolean('raw')) {
            $officeEmbed = null;
            if ($viewKind === 'office') {
                $share = $zoho->resolveWorkDriveShareUrl($path) ?: $path;
                $direct = $zoho->workDriveDirectDownloadUrl($share);
                if ($direct) {
                    $officeEmbed = 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($direct);
                }
            }

            return response()->view('user.assessments.resource-frame', [
                'name' => $name,
                'kind' => $viewKind,
                'rawUrl' => route('user.assessments.resource', [
                    'attemptId' => $attempt->id,
                    'kind' => $kind,
                    'index' => $i,
                    'raw' => 1,
                ]),
                'officeEmbed' => $officeEmbed,
                'mime' => $zoho->mimeFromExtension(pathinfo($name, PATHINFO_EXTENSION)) ?: 'application/pdf',
            ]);
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=120',
            'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
            'X-Frame-Options' => 'SAMEORIGIN',
        ];

        if (preg_match('#workdrive\.zoho#i', $path) || str_starts_with($path, 'http')) {
            $info = $zoho->workDrivePublicFileInfo($path);
            if (! empty($info['extn'])) {
                $headers['Content-Type'] = $zoho->mimeFromExtension($info['extn']) ?: 'application/octet-stream';
            }
            if (! empty($info['download_url'])) {
                return $zoho->proxyRemoteStream(
                    $info['download_url'],
                    $request->header('Range'),
                    $headers,
                    false
                );
            }

            $remote = $zoho->fetchRemoteFile($path);
            abort_unless($remote && ! empty($remote['path']) && is_file($remote['path']), 502, 'File could not be opened.');
            $headers['Content-Type'] = $remote['mime'] ?? ($headers['Content-Type'] ?? 'application/octet-stream');

            return response()->file($remote['path'], $headers);
        }

        abort_unless(Storage::disk('public')->exists($path), 404);
        $localPath = Storage::disk('public')->path($path);
        $headers['Content-Type'] = $zoho->mimeFromExtension(pathinfo($name, PATHINFO_EXTENSION))
            ?: (@mime_content_type($localPath) ?: 'application/octet-stream');

        return response()->file($localPath, $headers);
    }

    protected function examResourceKind(string $name, string $mime = ''): string
    {
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        $mime = strtolower($mime);
        if (str_starts_with($mime, 'image/') || in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            return 'image';
        }
        if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)) {
            return 'office';
        }
        if ($ext === 'pdf' || str_contains($mime, 'pdf')) {
            return 'pdf';
        }

        return 'file';
    }

    public function save(Request $request, $attemptId)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $attempt = LmsAssessmentAttempt::where('user_id', $user->id)->findOrFail($attemptId);
        if (! $attempt->isInProgress()) {
            $redirect = route('user.assessments.result', $attempt->id);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'redirect' => $redirect]);
            }

            return redirect()->to($redirect);
        }

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
        if ($errors !== [] && ! $attempt->isTimedOut()) {
            return back()->withErrors($errors)->withInput();
        }

        $this->assessments->saveAnswers($attempt, (array) $request->input('answers', []));
        $fresh = $attempt->fresh();
        $shouldFinish = $request->boolean('submit')
            || $request->input('save_mode') === '1'
            || ($fresh && ! $fresh->isInProgress());

        if ($shouldFinish) {
            if ($fresh && $fresh->isInProgress()) {
                $this->assessments->submitAttempt($fresh);
            }

            $redirect = route('user.assessments.result', $attempt->id);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['ok' => true, 'redirect' => $redirect]);
            }

            return redirect()->to($redirect)
                ->with('success', 'Submitted. Results appear when published (MCQ-only instant results may show immediately).');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Answers saved.');
    }

    public function result($attemptId)
    {
        $user = Auth::user();
        abort_unless($user, 403);
        $this->assessments->ensure();
        $this->assessments->autoSubmitTimedOutForUser($user);
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
            'assignment',
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

        $isInstructor = $subject->roles->contains(
            fn ($r) => strtolower((string) $r->name) === 'instructor'
        );
        $batchIds = [];
        if (Schema::hasTable('class_batch_student')) {
            $batchIds = ClassBatch::query()
                ->whereHas('students', fn ($q) => $q->where('users.id', $me->id))
                ->pluck('id')
                ->map(fn ($bid) => (int) $bid)
                ->all();
        }
        $isMyAssessor = LmsAssessmentAssignment::query()
            ->where('marker_user_id', $id)
            ->where(function ($q) use ($me, $batchIds) {
                $q->where('student_id', $me->id);
                if ($batchIds !== []) {
                    $q->orWhereIn('class_batch_id', $batchIds);
                }
            })
            ->exists()
            || LmsAssessmentAttempt::query()
                ->where('user_id', $me->id)
                ->where('graded_by_user_id', $id)
                ->exists();
        $allowed = $id === (int) $me->id || $isInstructor || $isMyAssessor;
        abort_unless($allowed, 403);

        return response()->json($this->assessments->userPreviewPayload($subject));
    }
}
