<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalManuscript;
use App\Models\JournalSubject;
use App\Services\JournalSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ManuscriptController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index()
    {
        $rows = JournalManuscript::query()
            ->where('author_user_id', Auth::id())
            ->orderByDesc('id')
            ->get();

        return view('journal.manuscripts.index', compact('rows'));
    }

    public function create()
    {
        $subjects = JournalSubject::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('journal.manuscripts.form', [
            'row' => new JournalManuscript(['status' => 'draft']),
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['author_user_id'] = Auth::id();
        $data['slug'] = JournalManuscript::makeSlug($data['title']);
        $data['corresponding_author_name'] = $data['corresponding_author_name'] ?: Auth::user()->name;
        $data['corresponding_author_email'] = $data['corresponding_author_email'] ?: Auth::user()->email;

        if ($request->boolean('submit_now')) {
            $data['status'] = 'submitted';
            $data['submitted_at'] = now();
        } else {
            $data['status'] = 'draft';
        }

        if ($request->hasFile('pdf_file')) {
            $data['pdf_path'] = $this->storePdf($request->file('pdf_file'));
        }

        $row = JournalManuscript::create($data);

        return redirect()->route('journal.manuscripts.show', $row->id)
            ->with('status', $row->status === 'submitted' ? 'Manuscript submitted.' : 'Draft saved.');
    }

    public function show(string $id)
    {
        $row = JournalManuscript::query()
            ->with(['subject', 'reviews.reviewer'])
            ->where('author_user_id', Auth::id())
            ->findOrFail($id);

        return view('journal.manuscripts.show', compact('row'));
    }

    public function edit(string $id)
    {
        $row = JournalManuscript::query()
            ->where('author_user_id', Auth::id())
            ->whereIn('status', ['draft', 'revision'])
            ->findOrFail($id);
        $subjects = JournalSubject::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();

        return view('journal.manuscripts.form', compact('row', 'subjects'));
    }

    public function update(Request $request, string $id)
    {
        $row = JournalManuscript::query()
            ->where('author_user_id', Auth::id())
            ->whereIn('status', ['draft', 'revision'])
            ->findOrFail($id);

        $data = $this->validated($request);
        if ($request->boolean('submit_now')) {
            $data['status'] = 'submitted';
            $data['submitted_at'] = $row->submitted_at ?: now();
        }
        if ($request->hasFile('pdf_file')) {
            $data['pdf_path'] = $this->storePdf($request->file('pdf_file'));
        }

        $row->fill($data)->save();

        return redirect()->route('journal.manuscripts.show', $row->id)
            ->with('status', 'Manuscript updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:500',
            'abstract' => 'nullable|string',
            'keywords' => 'nullable|string|max:500',
            'subject_id' => 'nullable|integer',
            'corresponding_author_name' => 'nullable|string|max:180',
            'corresponding_author_email' => 'nullable|email|max:180',
            'orcid' => 'nullable|string|max:40',
            'html_body' => 'nullable|string',
            'references_text' => 'nullable|string',
            'ai_disclosure' => 'nullable|string',
            'conflict_of_interest' => 'nullable|string',
            'ethics_confirmed' => 'nullable|boolean',
            'pdf_file' => 'nullable|file|mimes:pdf|max:20480',
        ]) + [
            'ethics_confirmed' => $request->boolean('ethics_confirmed'),
        ];
    }

    private function storePdf($file): string
    {
        $name = 'journal-ms-' . time() . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        $dir = public_path('uploads/journal');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        public_upload_move($file, $dir, $name);

        return 'uploads/journal/' . $name;
    }
}
