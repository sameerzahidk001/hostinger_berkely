<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalManuscript;
use App\Models\JournalNewsItem;
use App\Models\JournalPeerReview;
use App\Models\JournalPolicy;
use App\Services\JournalSetupService;
use App\Services\JournalWorkflow;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct()
    {
        app(JournalSetupService::class)->ensureAll();
    }

    public function index()
    {
        $user = Auth::user();
        $roleNames = JournalWorkflow::roleNames($user);

        $isAuthor = JournalWorkflow::isAuthor($user);
        $isReviewer = JournalWorkflow::isReviewer($user);
        $isEditor = JournalWorkflow::isEditor($user);
        $isStaff = JournalWorkflow::isStaff($user);

        $pipeline = [];
        foreach (JournalWorkflow::PIPELINE as $status) {
            $pipeline[$status] = 0;
        }
        $pipeline['rejected'] = 0;

        $myManuscripts = collect();
        $pendingReviews = collect();
        $editorialQueue = collect();
        $recentPublished = collect();

        try {
            if (JournalManuscript::ensureTable()) {
                foreach (array_keys($pipeline) as $status) {
                    $pipeline[$status] = JournalManuscript::query()->where('status', $status)->count();
                }

                if ($isAuthor) {
                    $myManuscripts = JournalManuscript::query()
                        ->where('author_user_id', $user->id)
                        ->orderByDesc('id')
                        ->limit(8)
                        ->get();
                }

                if ($isEditor || $isStaff) {
                    $editorialQueue = JournalManuscript::query()
                        ->with(['author', 'subject'])
                        ->whereIn('status', ['submitted', 'under_review', 'revision', 'accepted'])
                        ->orderByRaw("CASE status WHEN 'submitted' THEN 1 WHEN 'under_review' THEN 2 WHEN 'revision' THEN 3 WHEN 'accepted' THEN 4 ELSE 5 END")
                        ->orderByDesc('id')
                        ->limit(12)
                        ->get();
                }

                $recentPublished = JournalManuscript::query()
                    ->where('status', 'published')
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get();
            }

            if ($isReviewer && JournalPeerReview::ensureTable()) {
                $pendingReviews = JournalPeerReview::query()
                    ->with('manuscript')
                    ->where('reviewer_user_id', $user->id)
                    ->whereIn('status', ['invited', 'accepted', 'in_progress'])
                    ->orderByDesc('id')
                    ->limit(8)
                    ->get();
            }
        } catch (\Throwable $e) {
            // Keep dashboard resilient if tables are mid-create.
        }

        $stats = [
            'my_manuscripts' => $isAuthor
                ? JournalManuscript::query()->where('author_user_id', $user->id)->count()
                : 0,
            'pending_reviews' => $isReviewer
                ? JournalPeerReview::query()
                    ->where('reviewer_user_id', $user->id)
                    ->whereIn('status', ['invited', 'accepted', 'in_progress'])
                    ->count()
                : 0,
            'editorial_queue' => ($isEditor || $isStaff)
                ? JournalManuscript::query()
                    ->whereIn('status', ['submitted', 'under_review', 'revision', 'accepted'])
                    ->count()
                : 0,
            'published' => $pipeline['published'] ?? 0,
            'policies_published' => class_exists(JournalPolicy::class)
                ? JournalPolicy::query()->where('is_published', true)->count()
                : 0,
            'news_published' => class_exists(JournalNewsItem::class)
                ? JournalNewsItem::query()->where('is_published', true)->count()
                : 0,
        ];

        return view('journal.dashboard', [
            'user' => $user,
            'roleNames' => $roleNames,
            'isAuthor' => $isAuthor,
            'isReviewer' => $isReviewer,
            'isEditor' => $isEditor,
            'isStaff' => $isStaff,
            'stats' => $stats,
            'pipeline' => $pipeline,
            'myManuscripts' => $myManuscripts,
            'pendingReviews' => $pendingReviews,
            'editorialQueue' => $editorialQueue,
            'recentPublished' => $recentPublished,
        ]);
    }
}
