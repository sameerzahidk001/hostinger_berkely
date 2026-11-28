<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalIssue;
use App\Models\JournalManuscript;
use App\Models\JournalPeerReview;
use App\Models\Role;
use App\Models\User;
use App\Services\JournalSetupService;
use App\Services\JournalWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EditorialController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index(Request $request)
    {
        $this->requireEditor();

        $status = trim((string) $request->query('status', ''));
        $query = JournalManuscript::query()
            ->with(['author', 'subject', 'editor'])
            ->whereIn('status', ['submitted', 'under_review', 'revision', 'accepted']);

        if ($status !== '' && in_array($status, ['submitted', 'under_review', 'revision', 'accepted'], true)) {
            $query->where('status', $status);
        }

        $rows = $query
            ->orderByRaw("CASE status WHEN 'submitted' THEN 1 WHEN 'under_review' THEN 2 WHEN 'revision' THEN 3 WHEN 'accepted' THEN 4 ELSE 5 END")
            ->orderByDesc('id')
            ->get();

        return view('journal.editorial.index', compact('rows', 'status'));
    }

    public function show(string $id)
    {
        $this->requireEditor();

        $row = JournalManuscript::query()
            ->with(['author', 'subject', 'reviews.reviewer', 'issue', 'editor'])
            ->findOrFail($id);

        $reviewerRoleIds = Role::query()->where('name', 'journal_reviewer')->pluck('id');
        $reviewers = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $reviewerRoleIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $issues = JournalIssue::query()->orderByDesc('id')->get();
        $decisions = JournalWorkflow::allowedDecisions($row->status);
        $timeline = JournalWorkflow::timelineFor($row->status);

        return view('journal.editorial.show', compact('row', 'reviewers', 'issues', 'decisions', 'timeline'));
    }

    public function update(Request $request, string $id)
    {
        $this->requireEditor();

        $row = JournalManuscript::query()->findOrFail($id);
        $action = $request->input('action');

        if ($action === 'assign_self') {
            $row->editor_user_id = Auth::id();
            if ($row->status === 'submitted') {
                $row->status = 'under_review';
            }
            $row->save();

            return back()->with('status', 'You are handling editor. Status moved to peer review when it was newly submitted.');
        }

        if ($action === 'screen_reject') {
            if ($row->status !== 'submitted') {
                return back()->with('fail', 'Desk reject is only for newly submitted manuscripts.');
            }
            $row->status = 'rejected';
            $row->decision_notes = $request->input('decision_notes') ?: 'Desk rejected after editorial screening.';
            $row->decision_at = now();
            $row->editor_user_id = $row->editor_user_id ?: Auth::id();
            $row->save();

            return back()->with('status', 'Manuscript desk-rejected.');
        }

        if ($action === 'invite_reviewer') {
            $data = $request->validate([
                'reviewer_user_id' => 'required|integer|exists:users,id',
            ]);
            JournalPeerReview::query()->firstOrCreate(
                [
                    'manuscript_id' => $row->id,
                    'reviewer_user_id' => $data['reviewer_user_id'],
                ],
                [
                    'status' => 'invited',
                    'invited_at' => now(),
                ]
            );
            if (in_array($row->status, ['submitted', 'revision'], true)) {
                $row->status = 'under_review';
            }
            $row->editor_user_id = $row->editor_user_id ?: Auth::id();
            $row->save();

            return back()->with('status', 'Reviewer invited. Manuscript is under peer review.');
        }

        if ($action === 'decide') {
            $allowed = JournalWorkflow::allowedDecisions($row->status);
            if ($allowed === []) {
                return back()->with('fail', 'No editorial decisions are available for this status.');
            }
            $data = $request->validate([
                'status' => 'required|in:' . implode(',', $allowed),
                'decision_notes' => 'nullable|string',
                'doi' => 'nullable|string|max:120',
                'issue_id' => 'nullable|integer',
            ]);

            $row->status = $data['status'];
            $row->decision_notes = $data['decision_notes'] ?? $row->decision_notes;
            $row->decision_at = now();
            if (! empty($data['doi'])) {
                $row->doi = $data['doi'];
            }
            if (! empty($data['issue_id'])) {
                $row->issue_id = $data['issue_id'];
            }
            if ($data['status'] === 'published') {
                $row->published_at = $row->published_at ?: now();
            }
            $row->editor_user_id = $row->editor_user_id ?: Auth::id();
            $row->save();

            return back()->with('status', 'Editorial decision saved: ' . JournalWorkflow::statusLabel($row->status));
        }

        return back()->with('fail', 'Unknown action.');
    }

    private function requireEditor(): void
    {
        $user = Auth::user();
        if (! JournalWorkflow::isEditor($user) && ! JournalWorkflow::isStaff($user)) {
            abort(403, 'Editorial access required.');
        }
    }
}
