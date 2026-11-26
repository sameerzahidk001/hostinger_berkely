<?php

namespace App\Http\Controllers\Admin\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalBoardMember;
use App\Models\JournalIssue;
use App\Models\JournalManuscript;
use App\Models\JournalPolicy;
use App\Models\JournalSubject;
use App\Services\JournalSetupService;

class OverviewController extends Controller
{
    public function index(JournalSetupService $setup)
    {
        $setup->ensureAll();

        $counts = [
            'subjects' => JournalSubject::query()->count(),
            'policies' => JournalPolicy::query()->count(),
            'issues' => JournalIssue::query()->count(),
            'board' => JournalBoardMember::query()->count(),
            'manuscripts' => JournalManuscript::query()->count(),
            'published' => JournalManuscript::query()->where('status', 'published')->count(),
            'submitted' => JournalManuscript::query()->where('status', 'submitted')->count(),
        ];

        return view('admin.journal.overview', compact('counts'));
    }
}
