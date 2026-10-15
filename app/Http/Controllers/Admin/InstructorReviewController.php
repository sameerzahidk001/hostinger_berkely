<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBatch;
use App\Models\InstructorStudentReview;
use App\Models\User;
use App\Services\InstructorRatingService;
use App\Services\StudyMaterialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InstructorReviewController extends Controller
{
    public function __construct(
        protected InstructorRatingService $ratings,
        protected StudyMaterialService $lms
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();
        $this->ratings->ensureSchema();

        $instructorId = (int) $request->get('instructor_id', 0);
        $batchId = (int) $request->get('batch_id', 0);
        $dateFrom = trim((string) $request->get('date_from', ''));
        $dateTo = trim((string) $request->get('date_to', ''));
        $published = $request->get('published', '');

        $query = InstructorStudentReview::query()
            ->with(['student:id,name,email', 'instructor:id,name,email', 'batch', 'folder'])
            ->orderByDesc('updated_at');

        if ($this->lms->isInstructorActor() && ! panel_sees_all_dashboards()) {
            $query->where('instructor_id', (int) auth()->id());
        }

        if ($instructorId > 0) {
            $query->where('instructor_id', $instructorId);
        }
        if ($batchId > 0) {
            $query->where('class_batch_id', $batchId);
        }
        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($published === '1') {
            $query->where('is_published', true);
        } elseif ($published === '0') {
            $query->where('is_published', false);
        }

        $rows = $query->paginate(20)->withQueryString();
        $report = $this->ratings->reportByInstructor(
            ($this->lms->isInstructorActor() && ! panel_sees_all_dashboards())
                ? (int) auth()->id()
                : ($instructorId > 0 ? $instructorId : null)
        );

        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'instructor'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $batches = Schema::hasTable('class_batches')
            ? ClassBatch::query()->orderByDesc('id')->get(['id', 'code', 'name'])
            : collect();

        return view('admin.rate-review.index', [
            'rows' => $rows,
            'report' => $report,
            'instructors' => $instructors,
            'batches' => $batches,
            'filters' => [
                'instructor_id' => $instructorId,
                'batch_id' => $batchId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'published' => $published,
            ],
            'isAdmin' => $this->lms->isAdminActor() || panel_sees_all_dashboards(),
        ]);
    }

    public function publish($id)
    {
        $this->authorizeMutate();
        $review = InstructorStudentReview::findOrFail($id);
        $review->is_published = true;
        $review->save();

        return back()->with('success', 'Review published on the public instructor page.');
    }

    public function hide($id)
    {
        $this->authorizeMutate();
        $review = InstructorStudentReview::findOrFail($id);
        $review->is_published = false;
        $review->save();

        return back()->with('success', 'Review hidden from the public instructor page.');
    }

    public function lock($id)
    {
        $this->authorizeMutate();
        $review = InstructorStudentReview::findOrFail($id);
        $review->is_locked = true;
        $review->save();

        return back()->with('success', 'Review locked. Student can no longer edit it.');
    }

    public function unlock($id)
    {
        $this->authorizeMutate();
        $review = InstructorStudentReview::findOrFail($id);
        $review->is_locked = false;
        $review->save();

        return back()->with('success', 'Review unlocked. Student can edit again.');
    }

    public function toggleStudentName($id)
    {
        $this->authorizeMutate();
        $review = InstructorStudentReview::findOrFail($id);
        $review->hide_student_name = ! $review->hide_student_name;
        $review->save();

        return back()->with(
            'success',
            $review->hide_student_name
                ? 'Student name hidden for instructor view.'
                : 'Student name visible for instructor view.'
        );
    }

    public function destroy($id)
    {
        $this->authorizeMutate();
        $review = InstructorStudentReview::findOrFail($id);
        $review->delete();

        return back()->with('success', 'Review removed.');
    }

    protected function authorizeAccess(): void
    {
        if ($this->lms->isAdminActor() || panel_sees_all_dashboards()) {
            return;
        }

        abort_unless($this->lms->isInstructorActor(), 403);
    }

    protected function authorizeMutate(): void
    {
        abort_unless($this->lms->isAdminActor() || panel_sees_all_dashboards(), 403);
    }
}
