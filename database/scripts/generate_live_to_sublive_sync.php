<?php

/**
 * Compare live vs sublive SQL dumps and generate a safe content sync script.
 *
 * Usage:
 *   php database/scripts/generate_live_to_sublive_sync.php ^
 *     "C:/Users/HP/Downloads/live_database.sql" ^
 *     "C:/Users/HP/Downloads/sublive_testberk.sql" ^
 *     "database/live-to-sublive-content-sync.sql"
 */

if ($argc < 4) {
    fwrite(STDERR, "Usage: php generate_live_to_sublive_sync.php <live.sql> <sublive.sql> <output.sql>\n");
    exit(1);
}

[$script, $livePath, $subPath, $outPath] = $argv;

function parseTables(string $path): array
{
    $sql = file_get_contents($path);
    $tables = [];

    if (!preg_match_all('/CREATE TABLE `([^`]+)` \((.*?)\) ENGINE=/s', $sql, $matches, PREG_SET_ORDER)) {
        return $tables;
    }

    foreach ($matches as $match) {
        $name = $match[1];
        $body = $match[2];
        $columns = [];

        foreach (preg_split('/\r\n|\n|\r/', $body) as $line) {
            $line = trim($line);
            $line = rtrim($line, ',');

            if ($line === '' || preg_match('/^(PRIMARY|UNIQUE|KEY|CONSTRAINT|FULLTEXT)/', $line)) {
                continue;
            }

            if (preg_match('/^`([^`]+)`\s+(.+)$/', $line, $colMatch)) {
                $columns[$colMatch[1]] = $colMatch[2];
            }
        }

        $tables[$name] = $columns;
    }

    return $tables;
}

function convertInsertToUpsert(string $insertSql, array $allowedColumns): ?string
{
    if (!preg_match('/^INSERT INTO `([^`]+)` \(([^)]+)\) VALUES\s*(.+);$/s', trim($insertSql), $m)) {
        return null;
    }

    $table = $m[1];
    $columns = array_map(static fn ($c) => trim($c, " `"), explode(',', $m[2]));
    $valuesPart = rtrim($m[3], ';');

    $useIndexes = [];
    $useColumns = [];

    foreach ($columns as $index => $column) {
        if (in_array($column, $allowedColumns, true)) {
            $useIndexes[] = $index;
            $useColumns[] = $column;
        }
    }

    if ($useColumns === []) {
        return null;
    }

    $rows = [];
  $depth = 0;
    $current = '';
    $inString = false;
    $escape = false;

    $chars = str_split($valuesPart);
    foreach ($chars as $char) {
        if ($inString) {
            $current .= $char;
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($char === '\\') {
                $escape = true;
                continue;
            }
            if ($char === "'") {
                $inString = false;
            }
            continue;
        }

        if ($char === "'") {
            $inString = true;
            $current .= $char;
            continue;
        }

        if ($char === '(') {
            $depth++;
            if ($depth === 1) {
                $current = '';
                continue;
            }
        }

        if ($char === ')') {
            $depth--;
            if ($depth === 0) {
                $rows[] = $current;
                $current = '';
                continue;
            }
        }

        if ($depth > 0) {
            $current .= $char;
        }
    }

    $filteredRows = [];
    foreach ($rows as $row) {
        $parts = [];
        $depth = 0;
        $current = '';
        $inString = false;
        $escape = false;
        $rowValues = [];

        foreach (str_split($row) as $char) {
            if ($inString) {
                $current .= $char;
                if ($escape) {
                    $escape = false;
                    continue;
                }
                if ($char === '\\') {
                    $escape = true;
                    continue;
                }
                if ($char === "'") {
                    $inString = false;
                }
                continue;
            }

            if ($char === "'") {
                $inString = true;
                $current .= $char;
                continue;
            }

            if ($char === ',' && $depth === 0) {
                $rowValues[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ($current !== '') {
            $rowValues[] = trim($current);
        }

        $selected = [];
        foreach ($useIndexes as $idx) {
            if (!array_key_exists($idx, $rowValues)) {
                continue 2;
            }
            $selected[] = $rowValues[$idx];
        }

        $filteredRows[] = '(' . implode(', ', $selected) . ')';
    }

    if ($filteredRows === []) {
        return null;
    }

    $columnSql = implode(', ', array_map(static fn ($c) => "`{$c}`", $useColumns));
    $updates = [];
    foreach ($useColumns as $column) {
        if ($column === 'id') {
            continue;
        }
        $updates[] = "`{$column}` = VALUES(`{$column}`)";
    }

    $sql = "INSERT INTO `{$table}` ({$columnSql}) VALUES\n" . implode(",\n", $filteredRows);
    if ($updates !== []) {
        $sql .= "\nON DUPLICATE KEY UPDATE " . implode(', ', $updates);
    }
    $sql .= ";\n";

    // Redact payment gateway secrets before writing sync file.
    $sql = preg_replace('/sk_(live|test)_[A-Za-z0-9]+/', 'REDACTED_SECRET_KEY', $sql);
    $sql = preg_replace('/pk_(live|test)_[A-Za-z0-9]+/', 'REDACTED_PUBLIC_KEY', $sql);

    return $sql;
}

$liveTables = parseTables($livePath);
$subTables = parseTables($subPath);

$onlyLive = array_values(array_diff(array_keys($liveTables), array_keys($subTables)));
$onlySub = array_values(array_diff(array_keys($subTables), array_keys($liveTables)));

$syncTables = [
    'categories', 'category_course', 'clients', 'countries',
    'courses', 'course_agendas', 'course_beneficiaries', 'course_dynamic_labels',
    'course_enrollments', 'course_faqs', 'course_fees', 'course_fee_packages',
    'course_objectives', 'course_rewards', 'course_structures', 'course_structure_firsts',
    'course_structure_sub_headings', 'course_structure_sub_heading_firsts',
    'course_structure_sub_heading_units', 'course_subject', 'course_syllabus',
    'course_syllabus_highlights', 'course_testimonials', 'currencies', 'emails', 'faqs',
    'fee_packages_features', 'instructors', 'learner_stories', 'menus', 'pages',
    'pages_s_e_o_s', 'page_sections', 'payment_gateways', 'permissions', 'related_courses',
    'roles', 'role_has_permissions', 'schools', 'school_category', 'school_courses',
    'site_settings', 'subjects', 'widgets',
];

$skipTables = [
    'admins', 'users', 'user_has_roles', 'payments', 'installments', 'installment_requests',
    'cart_items', 'page_views', 'user_behavior', 'currency_rates', 'migrations',
    'failed_jobs', 'personal_access_tokens', 'password_resets', 'password_reset_tokens',
];

$out = fopen($outPath, 'wb');
fwrite($out, "-- Live -> Sublive content sync (generated)\n");
fwrite($out, "-- Does NOT delete sublive-only rows. Updates/inserts by primary key.\n");
fwrite($out, "-- KEEP sublive-only tables: " . implode(', ', $onlySub) . "\n\n");
fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

foreach ($syncTables as $table) {
    if (!isset($liveTables[$table], $subTables[$table])) {
        continue;
    }

    $commonColumns = array_values(array_intersect(array_keys($liveTables[$table]), array_keys($subTables[$table])));
    sort($commonColumns);

    fwrite($out, "-- Table: {$table}\n");
}

fwrite($out, "\n-- After this file, run: database/sql/setup-content-writer-accountant-roles.sql\n\n");

$currentInsert = '';
$inInsert = false;
$handle = fopen($livePath, 'rb');
$converted = 0;

while (($line = fgets($handle)) !== false) {
    if (!$inInsert && preg_match('/^INSERT INTO `([^`]+)`/', $line, $m)) {
        $table = $m[1];
        if (in_array($table, $syncTables, true) && isset($liveTables[$table], $subTables[$table])) {
            $inInsert = true;
            $currentInsert = $line;
            $currentTable = $table;
            $allowedColumns = array_values(array_intersect(array_keys($liveTables[$table]), array_keys($subTables[$table])));
            continue;
        }
    }

    if ($inInsert) {
        $currentInsert .= $line;
        if (str_contains(trim($line), ';')) {
            $upsert = convertInsertToUpsert($currentInsert, $allowedColumns);
            if ($upsert) {
                fwrite($out, $upsert . "\n");
                $converted++;
            }
            $inInsert = false;
            $currentInsert = '';
        }
    }
}

fwrite($out, "\nSET FOREIGN_KEY_CHECKS=1;\n");
fclose($handle);
fclose($out);

$reportPath = dirname($outPath) . '/live-to-sublive-sync-report.txt';
$report = [];
$report[] = 'Tables only in LIVE: ' . implode(', ', $onlyLive);
$report[] = 'Tables only in SUBLIVE (kept untouched): ' . implode(', ', $onlySub);
$report[] = 'Converted INSERT blocks: ' . $converted;
$report[] = 'Skipped tables: ' . implode(', ', $skipTables);
$report[] = 'Output: ' . $outPath;
file_put_contents($reportPath, implode(PHP_EOL, $report) . PHP_EOL);

echo implode(PHP_EOL, $report) . PHP_EOL;
