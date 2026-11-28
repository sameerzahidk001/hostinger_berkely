<?php

namespace App\Http\Controllers\Admin\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalNewsItem;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
        JournalNewsItem::ensureTable();
    }

    public function index()
    {
        $rows = JournalNewsItem::query()->orderByDesc('published_at')->orderByDesc('id')->get();

        return view('admin.journal.news.index', compact('rows'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:220',
            'type' => 'required|in:news,event',
            'body' => 'nullable|string',
        ]);

        $published = $request->boolean('is_published');
        JournalNewsItem::query()->create([
            'title' => $data['title'],
            'slug' => JournalNewsItem::uniqueSlug($data['title']),
            'type' => $data['type'],
            'body' => $data['body'] ?? '',
            'is_published' => $published,
            'published_at' => $published ? now() : null,
        ]);

        return back()->with('success', 'Item added.');
    }

    public function update(Request $request, string $id)
    {
        $row = JournalNewsItem::query()->findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:220',
            'type' => 'required|in:news,event',
            'body' => 'nullable|string',
        ]);

        $published = $request->boolean('is_published');
        $row->title = $data['title'];
        $row->type = $data['type'];
        $row->body = $data['body'] ?? '';
        $row->slug = JournalNewsItem::uniqueSlug($data['title'], (int) $row->id);
        $row->is_published = $published;
        if ($published && ! $row->published_at) {
            $row->published_at = now();
        }
        if (! $published) {
            $row->published_at = null;
        }
        $row->save();

        return back()->with('success', 'Item updated. Published items are listed on News & events.');
    }

    public function destroy(string $id)
    {
        JournalNewsItem::query()->where('id', $id)->delete();

        return back()->with('success', 'Item deleted.');
    }
}
