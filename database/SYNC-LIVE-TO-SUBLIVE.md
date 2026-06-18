# Live → Sublive database sync

Use this when you want **live site content** on the **test/sublive** database **without deleting** test users, test payments, or sublive-only features (e.g. `currency_rates`).

## Files

| File | Purpose |
|------|---------|
| `live-to-sublive-content-sync.sql` | Run on **sublive** DB in phpMyAdmin |
| `sql/setup-content-writer-accountant-roles.sql` | Run **after** sync to restore Content Writer / Accountant roles |
| `scripts/generate_live_to_sublive_sync.php` | Regenerate sync SQL when you have new dumps |

## What gets synced FROM live

Courses, pages, categories, menus, site settings, SEO, FAQs, clients, schools, currencies, emails, roles/permissions, etc.

## What stays on sublive (NOT touched)

- `users`, `user_has_roles` (finance@, contentwriter@, test students)
- `payments`, `installments`, `cart_items`
- `admins` (operations@ login)
- `currency_rates` (test currency setup)
- `page_views`, `user_behavior`
- `migrations`

## Steps on Hostinger

### 1. Backup sublive first
phpMyAdmin → sublive database → **Export** → save `.sql`

### 2. Run content sync
phpMyAdmin → select **sublive** database (`u739774248_testberk`) → **Import** tab

Import file from your PC (generate or use copy):

- `C:\Users\HP\Downloads\live-to-sublive-content-sync.sql` (after running generator)
- Or run generator → `database\live-to-sublive-content-sync.sql` (gitignored, local only)

**Generate fresh file:**
```bat
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -d memory_limit=1024M database\scripts\generate_live_to_sublive_sync.php "C:\Users\HP\Downloads\live_database.sql" "C:\Users\HP\Downloads\sublive_testberk.sql" "C:\Users\HP\Downloads\live-to-sublive-content-sync.sql"
```

- File is ~9 MB — use **Import** tab (not SQL paste) if phpMyAdmin times out.

### 3. Restore panel roles (recommended)
Run: **`database/sql/setup-content-writer-accountant-roles.sql`**

### 4. Verify
- Live courses/pages appear on test site
- Test logins still work: `operations@berkeleyme.com`, `finance@berkeleyme.com`, `contentwriter@berkeleyme.com`
- Currency Rate Setup still has rates

## Regenerate sync SQL (when live dump changes)

```bat
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe -d memory_limit=1024M database\scripts\generate_live_to_sublive_sync.php "C:\path\to\live_database.sql" "C:\path\to\sublive_testberk.sql" database\live-to-sublive-content-sync.sql
```

## Notes

- Uses `INSERT ... ON DUPLICATE KEY UPDATE` — **no DELETE**, sublive-only rows kept
- Rows with same `id` as live are **updated** with live content
- New live rows are **inserted**
- Sublive-only extra columns (`created_by`, `payments.source`) are preserved on update
