<?php

namespace App\Http\Controllers\Admin\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalBoardMember;
use App\Models\JournalSubject;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;

class BoardController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index()
    {
        $rows = JournalBoardMember::query()->with('subject')->orderBy('sort_order')->orderBy('name')->get();
        $subjects = JournalSubject::query()->orderBy('name')->get();

        return view('admin.journal.board.index', compact('rows', 'subjects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'role_title' => 'nullable|string|max:120',
            'affiliation' => 'nullable|string|max:255',
            'orcid' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:180',
            'subject_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer|min:0',
            'bio' => 'nullable|string',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        JournalBoardMember::create($data);

        return back()->with('success', 'Board member added. Only add real people.');
    }

    public function update(Request $request, string $id)
    {
        $row = JournalBoardMember::query()->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'role_title' => 'nullable|string|max:120',
            'affiliation' => 'nullable|string|max:255',
            'orcid' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:180',
            'subject_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer|min:0',
            'bio' => 'nullable|string',
        ]);
        $row->fill($data);
        $row->is_active = $request->boolean('is_active');
        $row->save();

        return back()->with('success', 'Board member updated.');
    }

    public function destroy(string $id)
    {
        JournalBoardMember::query()->findOrFail($id)->delete();

        return back()->with('success', 'Board member removed.');
    }
}
