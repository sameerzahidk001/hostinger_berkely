<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalPeerReview;
use App\Services\JournalSetupService;
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
        $rows = JournalPeerReview::query()
            ->with('manuscript')
            ->where('reviewer_user_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        return view('journal.reviews.index', compact('rows'));
    }

    public function show(string $id)
    {
        $row = JournalPeerReview::query()
            ->with('manuscript')
            ->where('reviewer_user_id', Auth::id())
            ->findOrFail($id);

        return view('journal.reviews.show', compact('row'));
    }

    public function update(Request $request, string $id)
    {
        $row = JournalPeerReview::query()
            ->where('reviewer_user_id', Auth::id())
            ->findOrFail($id);

        $action = $request->input('action');
        if ($action === 'accept' && $row->status === 'invited') {
            $row->status = 'accepted';
            $row->accepted_at = now();
            $row->save();

            return back()->with('status', 'Review invitation accepted.');
        }
        if ($action === 'decline' && in_array($row->status, ['invited', 'accepted'], true)) {
            $row->status = 'declined';
            $row->save();

            return back()->with('status', 'Review declined.');
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

        return redirect()->route('journal.reviews.index')->with('status', 'Review submitted.');
    }
}
