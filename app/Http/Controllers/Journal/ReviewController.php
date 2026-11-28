<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalPeerReview;
use App\Services\JournalSetupService;
use App\Services\JournalWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index()
    {
        $this->requireReviewer();

        $rows = JournalPeerReview::query()
            ->with('manuscript')
            ->where('reviewer_user_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        return view('journal.reviews.index', compact('rows'));
    }

    public function show(string $id)
    {
        $this->requireReviewer();

        $row = JournalPeerReview::query()
            ->with('manuscript.subject')
            ->where('reviewer_user_id', Auth::id())
            ->findOrFail($id);

        if ($row->status === 'accepted') {
            $row->status = 'in_progress';
            $row->save();
        }

        return view('journal.reviews.show', compact('row'));
    }

    public function update(Request $request, string $id)
    {
        $this->requireReviewer();

        $row = JournalPeerReview::query()
            ->where('reviewer_user_id', Auth::id())
            ->findOrFail($id);

        $action = $request->input('action');
        if ($action === 'accept' && $row->status === 'invited') {
            $row->status = 'accepted';
            $row->accepted_at = now();
            $row->save();

            return back()->with('status', 'Invitation accepted. Open the assignment to write your review.');
        }
        if ($action === 'decline' && in_array($row->status, ['invited', 'accepted', 'in_progress'], true)) {
            $row->status = 'declined';
            $row->save();

            return back()->with('status', 'Review declined.');
        }

        if (! in_array($row->status, ['accepted', 'in_progress'], true)) {
            return back()->with('fail', 'This review can no longer be edited.');
        }

        $data = $request->validate([
            'recommendation' => 'required|in:accept,minor_revision,major_revision,reject',
            'comments_to_author' => 'nullable|string',
            'comments_to_editor' => 'nullable|string',
        ]);

        $row->fill($data);
        $row->status = 'completed';
        $row->completed_at = now();
        $row->save();

        return redirect()->route('journal.reviews.index')->with('status', 'Peer review submitted to the editor.');
    }

    private function requireReviewer(): void
    {
        if (! JournalWorkflow::isReviewer(Auth::user())) {
            abort(403, 'Reviewer role required.');
        }
    }
}
