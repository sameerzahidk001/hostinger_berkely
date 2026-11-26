<?php

namespace App\Http\Controllers\Admin\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalSubject;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index()
    {
        $rows = JournalSubject::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.journal.subjects.index', compact('rows'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $data['slug'] = JournalSubject::makeSlug($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        JournalSubject::create($data);

        return back()->with('success', 'Subject added.');
    }

    public function update(Request $request, string $id)
    {
        $row = JournalSubject::query()->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);
        $row->fill($data);
        $row->is_active = $request->boolean('is_active');
        $row->save();

        return back()->with('success', 'Subject updated.');
    }

    public function destroy(string $id)
    {
        JournalSubject::query()->findOrFail($id)->delete();

        return back()->with('success', 'Subject removed.');
    }
}
