<?php

namespace App\Services;

use App\Models\JournalBoardMember;
use App\Models\JournalIssue;
use App\Models\JournalManuscript;
use App\Models\JournalNewsItem;
use App\Models\JournalPeerReview;
use App\Models\JournalPolicy;
use App\Models\JournalSubject;

class JournalSetupService
{
    /**
     * Ensure journal roles + all additive tables exist (safe on GitHub pull).
     */
    public function ensureAll(): void
    {
        JournalRoleService::ensureRoles();
        JournalSubject::ensureTable();
        JournalPolicy::ensureDefaults();
        JournalNewsItem::ensureTable();
        JournalIssue::ensureTable();
        JournalBoardMember::ensureTable();
        JournalManuscript::ensureTable();
        JournalPeerReview::ensureTable();
    }
}
