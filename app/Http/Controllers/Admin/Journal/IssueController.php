<?php

namespace App\Http\Controllers\Admin\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalIssue;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index()
    {
        $rows = JournalIssue::query()->orderByDesc('published_at')->orderByDesc('id')->get();

        return view('admin.journal.issues.index', compact('rows'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:220',
            'volume' => 'nullable|string|max:40',
            'number' => 'nullable|string|max:40',
            'issue_type' => 'required|in:regular,special,previous',
            'published_at' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
        $data['is_current'] = $request->boolean('is_current');
        if ($data['is_current']) {
            JournalIssue::query()->update(['is_current' => false]);
        }
        JournalIssue::create($data);

        return back()->with('success', 'Issue added.');
    }

    public function update(Request $request, string $id)
    {
        $row = JournalIssue::query()->findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:220',
            'volume' => 'nullable|string|max:40',
            'number' => 'nullable|string|max:40',
            'issue_type' => 'required|in:regular,special,previous',
            'published_at' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
        if ($request->boolean('is_current')) {
            JournalIssue::query()->where('id', '!=', $row->id)->update(['is_current' => false]);
            $data['is_current'] = true;
        } else {
            $data['is_current'] = false;
        }
        $row->fill($data)->save();

        return back()->with('success', 'Issue updated.');
    }

    public function destroy(string $id)
    {
        JournalIssue::query()->findOrFail($id)->delete();

        return back()->with('success', 'Issue removed.');
    }
}
