<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalManuscript;
use App\Models\JournalSubject;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicArticleController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $subjectId = $request->query('subject_id');
        $doi = trim((string) $request->query('doi', ''));
        $from = $request->query('from');
        $to = $request->query('to');

        $query = JournalManuscript::query()
            ->with(['subject', 'author'])
            ->where('status', 'published');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', '%' . $q . '%')
                    ->orWhere('abstract', 'like', '%' . $q . '%')
                    ->orWhere('keywords', 'like', '%' . $q . '%')
                    ->orWhere('corresponding_author_name', 'like', '%' . $q . '%');
            });
        }
        if ($subjectId) {
            $query->where('subject_id', (int) $subjectId);
        }
        if ($doi !== '') {
            $query->where('doi', 'like', '%' . $doi . '%');
        }
        if ($from) {
            $query->whereDate('published_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('published_at', '<=', $to);
        }

        $rows = $query->orderByDesc('published_at')->orderByDesc('id')->paginate(20)->withQueryString();
        $subjects = JournalSubject::query()->where('is_active', true)->orderBy('name')->get();

        return view('journal.public.search', compact('rows', 'subjects', 'q', 'subjectId', 'doi', 'from', 'to'));
    }

    public function show(string $slug)
    {
        $row = JournalManuscript::query()
            ->with(['subject', 'author', 'issue'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $row->increment('view_count');

        return view('journal.public.article', compact('row'));
    }

    public function downloadPdf(string $slug)
    {
        remember_path_auth_prefix('journal');

        if (! Auth::check()) {
            return redirect()
                ->guest(path_login_url('journal'))
                ->with('status', 'Journals are free to browse. Sign in or register to download PDFs.');
        }

        $row = JournalManuscript::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        $rel = ltrim(str_replace('\\', '/', (string) $row->pdf_path), '/');
        if ($rel === '') {
            abort(404, 'PDF not available.');
        }

        $full = public_path($rel);
        if (! is_file($full)) {
            abort(404, 'PDF file missing.');
        }

        return response()->download($full, basename($full));
    }
}
