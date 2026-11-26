<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalManuscript;
use App\Models\JournalPeerReview;
use App\Models\Role;
use App\Models\User;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EditorialController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index()
    {
        $rows = JournalManuscript::query()
            ->with(['author', 'subject'])
            ->whereIn('status', ['submitted', 'under_review', 'revision', 'accepted'])
            ->orderByDesc('id')
            ->get();

        return view('journal.editorial.index', compact('rows'));
    }

    public function show(string $id)
    {
        $row = JournalManuscript::query()
            ->with(['author', 'subject', 'reviews.reviewer', 'issue'])
            ->findOrFail($id);

        $reviewerRoleIds = Role::query()->where('name', 'journal_reviewer')->pluck('id');
        $reviewers = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $reviewerRoleIds))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('journal.editorial.show', compact('row', 'reviewers'));
    }

    public function update(Request $request, string $id)
    {
        $row = JournalManuscript::query()->findOrFail($id);
        $action = $request->input('action');

        if ($action === 'assign_self') {
            $row->editor_user_id = Auth::id();
            if ($row->status === 'submitted') {
                $row->status = 'under_review';
            }
            $row->save();

            return back()->with('status', 'You are assigned as editor.');
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
            if ($row->status === 'submitted') {
                $row->status = 'under_review';
                $row->editor_user_id = $row->editor_user_id ?: Auth::id();
                $row->save();
            }

            return back()->with('status', 'Reviewer invited.');
        }

        if ($action === 'decide') {
            $data = $request->validate([
                'status' => 'required|in:revision,accepted,rejected,published',
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

            return back()->with('status', 'Decision saved.');
        }

        return back()->with('fail', 'Unknown action.');
    }
}
