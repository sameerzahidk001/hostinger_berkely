<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalManuscript;
use App\Models\JournalNewsItem;
use App\Models\JournalPolicy;
use App\Models\JournalSubject;
use App\Services\JournalSetupService;
use Illuminate\Support\Facades\Auth;

class PublicHubController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
        JournalNewsItem::ensureTable();
        JournalPolicy::ensureTable();
    }

    public function journals()
    {
        $subjects = JournalSubject::query()->where('is_active', true)->orderBy('name')->get();
        $recent = JournalManuscript::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        return view('journal.public.journals', compact('subjects', 'recent'));
    }

    public function publish()
    {
        $published = JournalManuscript::query()
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(15);

        $canSubmit = Auth::check();
        $submitUrl = $canSubmit
            ? route('journal.manuscripts.create')
            : path_register_url('journal');

        return view('journal.public.publish', compact('published', 'canSubmit', 'submitUrl'));
    }

    public function newsIndex()
    {
        $rows = JournalNewsItem::query()
            ->where('is_published', true)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('journal.public.news-index', compact('rows'));
    }

    public function newsShow(string $slug)
    {
        $row = JournalNewsItem::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('journal.public.news-show', compact('row'));
    }

    public function policies()
    {
        $rows = JournalPolicy::query()
            ->where('is_published', true)
            ->orderBy('title')
            ->get();

        return view('journal.public.policies', compact('rows'));
    }
}
