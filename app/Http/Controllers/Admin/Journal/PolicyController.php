<?php

namespace App\Http\Controllers\Admin\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalPolicy;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    public function store(Request $request)
    {
        JournalPolicy::ensureTable();
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'body' => 'nullable|string',
        ]);

        $base = Str::slug($data['title']) ?: 'policy';
        $key = $base;
        $i = 2;
        while (JournalPolicy::query()->where('key', $key)->exists()) {
            $key = $base . '-' . $i;
            $i++;
        }

        JournalPolicy::query()->create([
            'key' => $key,
            'title' => $data['title'],
            'body' => $data['body'] ?? '',
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('success', 'Policy added.');
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

        return back()->with('success', 'Policy updated. Published policies appear on the journal site.');
    }

    public function destroy(string $id)
    {
        $row = JournalPolicy::query()->findOrFail($id);
        $row->delete();

        return back()->with('success', 'Policy deleted.');
    }
}
