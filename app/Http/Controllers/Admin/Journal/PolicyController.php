<?php

namespace App\Http\Controllers\Admin\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalPolicy;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;

class PolicyController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index()
    {
        JournalPolicy::ensureDefaults();
        $rows = JournalPolicy::query()->orderBy('title')->get();

        return view('admin.journal.policies.index', compact('rows'));
    }

    public function update(Request $request, string $id)
    {
        $row = JournalPolicy::query()->findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'body' => 'nullable|string',
        ]);
        $row->fill($data);
        $row->is_published = $request->boolean('is_published');
        $row->save();

        return back()->with('success', 'Policy updated. Unpublished policies are not claimed as public indexing.');
    }
}
