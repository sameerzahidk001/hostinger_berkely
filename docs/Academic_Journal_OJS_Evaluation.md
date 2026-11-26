# Academic Journal — OJS evaluation (Phase E)

Date: 2026-11-26  
Site: EduBerkeley LMS + Academic Journal under `/academic-journal`  
Constraint: same Superadmin, Hostinger GitHub-only deploys, no LMS breakage.

## Recommendation

**Keep the Laravel-native journal module as the primary engine for now.**  
Evaluate a full Open Journal Systems (OJS) install later only if the client needs DOAJ/Crossref automation that exceeds the Laravel workflow.

## Why Laravel-first (current ship)

| Factor | Laravel module | Full OJS |
|--------|----------------|----------|
| Same Superadmin UX | Fits existing admin + Users roles | Separate OJS admin; SSO/bridging required |
| Hostinger GitHub pull | Additive PHP/tables; low crash risk | Separate app, DB, cron, file storage |
| Path branding `/academic-journal` | Already wired | Needs reverse proxy / subdomain |
| Journal roles in Users | `journal_*` roles already added | Duplicate user stores unless synced |
| Fake claims risk | We control empty DOI/ISSN fields | Still requires discipline; easier to misconfigure plugins |

## When to revisit OJS

Revisit OJS if / when all of the following are true:

1. Crossref membership and DOI prefix are live (Finance/IT).
2. Editorial volume needs OJS’s mature review tooling (multi-round forms, reviewer matching plugins).
3. Client accepts either a subdomain (`journal.eduberkeley.com`) or a carefully isolated path proxy.
4. There is budget for SSO or nightly user sync with EduBerkeley `users` / `journal_*` roles.

## Suggested OJS isolation (if adopted later)

- Host OJS on a subdomain, not inside the LMS document root.
- Keep EduBerkeley Path Branding + public marketing pages on `/academic-journal`.
- Link “Submit” from the Laravel hub to OJS; do **not** delete Laravel admin Subjects/Policies/Board.
- Never auto-display indexing badges until DOAJ/Scopus acceptance is documented.

## Current Laravel coverage (Phases A–D)

- Path logo/menu + path login/register
- Journal roles under Users
- Role dashboards/cards
- Admin: subjects, policies, issues, board, manuscripts list
- Author submit/track, reviewer reports, editorial decisions
- Public article search + article page with honest view counts
- DOI/ORCID fields present but empty until real registration

## Non-goals of this evaluation

- No fake ISSN, indexing, citation counts, or placeholder editors.
- No force-install of OJS on Hostinger in this phase.
