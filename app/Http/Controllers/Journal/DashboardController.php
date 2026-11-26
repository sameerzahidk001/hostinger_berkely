<?php

namespace App\Http\Controllers\Journal;

use App\Http\Controllers\Controller;
use App\Models\JournalManuscript;
use App\Models\JournalPeerReview;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $roleNames = $user ? $user->roles()->pluck('name')->map(fn ($n) => normalize_role_key($n))->all() : [];

        $stats = [
            'my_manuscripts' => 0,
            'pending_reviews' => 0,
            'assigned_editorial' => 0,
            'published' => 0,
        ];

        try {
            if (class_exists(JournalManuscript::class) && JournalManuscript::ensureTable()) {
                $stats['my_manuscripts'] = JournalManuscript::query()
                    ->where('author_user_id', $user->id)
                    ->count();
                $stats['assigned_editorial'] = JournalManuscript::query()
                    ->where('editor_user_id', $user->id)
                    ->whereNotIn('status', ['published', 'rejected', 'withdrawn'])
                    ->count();
                $stats['published'] = JournalManuscript::query()
                    ->where('status', 'published')
                    ->where(function ($q) use ($user) {
                        $q->where('author_user_id', $user->id)->orWhere('editor_user_id', $user->id);
                    })
                    ->count();
            }
            if (class_exists(JournalPeerReview::class) && JournalPeerReview::ensureTable()) {
                $stats['pending_reviews'] = JournalPeerReview::query()
                    ->where('reviewer_user_id', $user->id)
                    ->whereIn('status', ['invited', 'accepted', 'in_progress'])
                    ->count();
            }
        } catch (\Throwable $e) {
            // Keep dashboard up even if journal tables are not ready yet.
        }

        $cards = $this->cardsForRoles($roleNames);

        return view('journal.dashboard', [
            'user' => $user,
            'roleNames' => $roleNames,
            'stats' => $stats,
            'cards' => $cards,
            'headerBrand' => site_header_branding(null, 'academic-journal'),
        ]);
    }

    /**
     * @param  list<string>  $roleNames
     * @return list<array{title: string, body: string, href: string, cta: string}>
     */
    private function cardsForRoles(array $roleNames): array
    {
        $cards = [];

        if (array_intersect($roleNames, ['journal_author'])) {
            $cards[] = [
                'title' => 'My manuscripts',
                'body' => 'Submit a new manuscript and track peer-review status.',
                'href' => route('journal.manuscripts.index'),
                'cta' => 'Open submissions',
            ];
            $cards[] = [
                'title' => 'Submit manuscript',
                'body' => 'Start a new submission to the Academic Journal.',
                'href' => route('journal.manuscripts.create'),
                'cta' => 'Submit',
            ];
        }

        if (array_intersect($roleNames, ['journal_reviewer'])) {
            $cards[] = [
                'title' => 'Review assignments',
                'body' => 'Accept invitations and submit peer-review reports.',
                'href' => route('journal.reviews.index'),
                'cta' => 'Open reviews',
            ];
        }

        if (array_intersect($roleNames, ['journal_editor', 'journal_editorial'])) {
            $cards[] = [
                'title' => 'Editorial queue',
                'body' => 'Assign reviewers and record editorial decisions.',
                'href' => route('journal.editorial.index'),
                'cta' => 'Open queue',
            ];
        }

        if (array_intersect($roleNames, [
            'journal_management', 'journal_administration', 'journal_finance',
            'journal_it', 'journal_quality', 'journal_editorial',
        ])) {
            $cards[] = [
                'title' => 'Journal public site',
                'body' => 'Open the branded journal hub. Superadmin manages subjects/policies/issues under Admin → Academic Journal.',
                'href' => url('/academic-journal'),
                'cta' => 'Open journal',
            ];
        }

        if ($cards === []) {
            $cards[] = [
                'title' => 'Journal home',
                'body' => 'Browse the public Academic Journal site.',
                'href' => url('/academic-journal'),
                'cta' => 'Visit journal',
            ];
        }

        return $cards;
    }
}
