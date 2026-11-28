<?php

namespace App\Services;

use App\Models\User;

class JournalWorkflow
{
    /** Real peer-review pipeline stages (display order). */
    public const PIPELINE = [
        'draft',
        'submitted',
        'under_review',
        'revision',
        'accepted',
        'published',
    ];

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted (awaiting editor)',
            'under_review' => 'Under peer review',
            'revision' => 'Revision requested',
            'accepted' => 'Accepted (in production)',
            'rejected' => 'Rejected',
            'published' => 'Published',
            'withdrawn' => 'Withdrawn',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    public static function statusHelp(string $status): string
    {
        return match ($status) {
            'draft' => 'Author is preparing the manuscript.',
            'submitted' => 'Editor screens the submission and may invite reviewers.',
            'under_review' => 'Peer reviewers are assessing the manuscript.',
            'revision' => 'Author revises and resubmits for further review.',
            'accepted' => 'Accepted; editor may assign an issue/DOI and publish.',
            'rejected' => 'Not accepted for publication.',
            'published' => 'Live on the journal site (PDF after sign-in).',
            'withdrawn' => 'Withdrawn by the author.',
            default => '',
        };
    }

    /**
     * @return list<string>
     */
    public static function roleNames(User $user): array
    {
        return $user->roles()
            ->pluck('name')
            ->map(fn ($n) => normalize_role_key($n))
            ->filter(fn ($n) => str_starts_with($n, 'journal_'))
            ->values()
            ->all();
    }

    public static function hasAnyRole(User $user, array $roles): bool
    {
        $mine = self::roleNames($user);

        return count(array_intersect($mine, $roles)) > 0;
    }

    public static function isAuthor(User $user): bool
    {
        return self::hasAnyRole($user, ['journal_author']);
    }

    public static function isReviewer(User $user): bool
    {
        return self::hasAnyRole($user, ['journal_reviewer']);
    }

    public static function isEditor(User $user): bool
    {
        return self::hasAnyRole($user, ['journal_editor', 'journal_editorial']);
    }

    public static function isStaff(User $user): bool
    {
        return self::hasAnyRole($user, [
            'journal_management',
            'journal_administration',
            'journal_finance',
            'journal_it',
            'journal_quality',
            'journal_editorial',
            'journal_editor',
        ]);
    }

    /**
     * Editorial decisions allowed from the current manuscript status.
     *
     * @return list<string>
     */
    public static function allowedDecisions(string $status): array
    {
        return match ($status) {
            'submitted', 'under_review' => ['revision', 'accepted', 'rejected'],
            'revision' => ['under_review', 'accepted', 'rejected'],
            'accepted' => ['published', 'rejected'],
            default => [],
        };
    }

    /**
     * @return list<array{key:string,label:string,done:bool,current:bool}>
     */
    public static function timelineFor(string $status): array
    {
        if (in_array($status, ['rejected', 'withdrawn'], true)) {
            return [
                ['key' => $status, 'label' => self::statusLabel($status), 'done' => true, 'current' => true],
            ];
        }

        $currentIdx = array_search($status, self::PIPELINE, true);
        if ($currentIdx === false) {
            $currentIdx = 0;
        }

        $out = [];
        foreach (self::PIPELINE as $i => $key) {
            $out[] = [
                'key' => $key,
                'label' => self::statusLabel($key),
                'done' => $i < $currentIdx,
                'current' => $i === $currentIdx,
            ];
        }

        return $out;
    }
}
