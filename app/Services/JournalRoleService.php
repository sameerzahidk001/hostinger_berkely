<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\Schema;

class JournalRoleService
{
    /**
     * @return array<string, string> name => description
     */
    public static function definitions(): array
    {
        return [
            'journal_management' => 'Journal Management — approve publisher, title, scope, budget, frequency.',
            'journal_administration' => 'Journal Administration — ISSN application and records.',
            'journal_finance' => 'Journal Finance — Crossref registration and payments.',
            'journal_it' => 'Journal IT — deploy, metadata, DOI, security, preservation.',
            'journal_editorial' => 'Journal Editorial team — editors, reviewers, peer review, issues.',
            'journal_quality' => 'Journal Quality lead — eligibility audit and indexing readiness.',
            'journal_author' => 'Journal Author — submit and track manuscripts.',
            'journal_reviewer' => 'Journal Reviewer — peer review assignments.',
            'journal_editor' => 'Journal Editor — subject editing and decisions.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * Idempotent: create missing journal roles only (never renames LMS roles).
     *
     * @return list<Role>
     */
    public static function ensureRoles(): array
    {
        if (! Schema::hasTable('roles')) {
            return [];
        }

        $created = [];
        foreach (self::definitions() as $name => $description) {
            $role = Role::query()->firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
            if ($role->description !== $description && ! filled($role->description)) {
                $role->description = $description;
                $role->save();
            }
            $created[] = $role;
        }

        return $created;
    }

    public static function authorRoleId(): ?int
    {
        self::ensureRoles();

        return Role::query()->where('name', 'journal_author')->value('id');
    }

    public static function isJournalRoleName(?string $name): bool
    {
        $key = str_replace(['-', ' '], '_', strtolower(trim((string) $name)));

        return str_starts_with($key, 'journal_') && in_array($key, self::names(), true);
    }
}
