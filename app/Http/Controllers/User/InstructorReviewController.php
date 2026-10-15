<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InstructorStudentReview;
use App\Services\InstructorRatingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InstructorReviewController extends Controller
{
    public function __construct(protected InstructorRatingService $ratings)
    {
    }

    public function index()
    {
        abort_unless($this->isStudent(), 403);
        $this->ratings->ensureSchema();

        $targets = $this->ratings->eligibleTargetsForStudent((int) Auth::id());

        return view('user.rate-review.index', [
            'targets' => $targets,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($this->isStudent(), 403);
        $key = trim((string) $request->get('key', ''));
        abort_unless($key !== '', 404);

        $target = $this->ratings->findTarget((int) Auth::id(), $key);
        abort_unless($target, 403);

        if ($target['existing'] && $target['existing']->is_locked) {
            return redirect()
                ->route('user.rate-review.index')
                ->with('fail', 'This rating is locked by Admin and can no longer be edited.');
        }

        return view('user.rate-review.form', [
            'target' => $target,
            'review' => $target['existing'],
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->isStudent(), 403);
        $this->ratings->ensureSchema();

        $key = trim((string) $request->input('key', ''));
        $target = $this->ratings->findTarget((int) Auth::id(), $key);
        abort_unless($target, 403);

        $existing = $target['existing'];
        if ($existing && $existing->is_locked) {
            return redirect()
                ->route('user.rate-review.index')
                ->with('fail', 'This rating is locked by Admin and can no longer be edited.');
        }

        $textRule = ! empty($target['review_text_required']) ? 'required|string|max:2000' : 'nullable|string|max:2000';
        $validator = Validator::make($request->all(), [
            'stars' => 'required|integer|min:1|max:5',
            'review_text' => $textRule,
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $batchId = $target['batch']?->id ? (int) $target['batch']->id : null;
        $folderId = $target['folder']?->id ? (int) $target['folder']->id : null;

        $review = $existing ?: new InstructorStudentReview();
        $review->student_id = (int) Auth::id();
        $review->instructor_id = (int) $target['instructor']->id;
        $review->class_batch_id = $batchId;
        $review->study_material_folder_id = $folderId;
        $review->scope_key = InstructorStudentReview::scopeKey($batchId, $folderId);
        $review->stars = (int) $request->input('stars');
        $review->review_text = trim((string) $request->input('review_text', '')) ?: null;
        if (! $existing) {
            $review->is_published = false;
            $review->is_locked = false;
            $review->hide_student_name = false;
        }
        $review->save();

        return redirect()
            ->route('user.rate-review.index')
            ->with('success', 'Your rating was saved. Thank you.');
    }

    public function myScore()
    {
        $user = Auth::user();
        abort_unless($user && $user->roles()->where('name', 'instructor')->exists(), 403);
        $this->ratings->ensureSchema();

        $score = $this->ratings->scoreForInstructor((int) $user->id, false);
        $reviews = InstructorStudentReview::query()
            ->with(['student:id,name', 'batch', 'folder'])
            ->forInstructor((int) $user->id)
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('user.rate-review.my-score', [
            'score' => $score,
            'reviews' => $reviews,
        ]);
    }

    protected function isStudent(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        return $user->roles()->where('name', 'student')->exists()
            && ! $user->roles()->where('name', 'instructor')->exists();
    }
}
