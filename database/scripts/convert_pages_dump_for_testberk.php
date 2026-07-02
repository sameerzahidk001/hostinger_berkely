<?php

/**
 * Convert phpMyAdmin pages export (live) into safe import for testberk.
 *
 * Usage:
 *   php database/scripts/convert_pages_dump_for_testberk.php "C:/Users/HP/Downloads/pages.sql" "C:/Users/HP/Downloads/pages-testberk-import.sql"
 */

if ($argc < 3) {
    fwrite(STDERR, "Usage: php convert_pages_dump_for_testberk.php <pages.sql> <output.sql>\n");
    exit(1);
}

[$script, $inPath, $outPath] = $argv;

$allowedTables = ['pages', 'page_sections', 'pages_s_e_o_s'];
$in = fopen($inPath, 'rb');
$out = fopen($outPath, 'wb');

fwrite($out, "-- Import into u739774248_testberk via phpMyAdmin → Import tab (NOT SQL tab paste)\n");
fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\nSET NAMES utf8mb4;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

$skipBlock = false;
$skipTable = '';
$inCreateTable = false;
$inAlterTable = false;
$converted = 0;

while (($line = fgets($in)) !== false) {
    if (preg_match('/^-- Table structure for table `([^`]+)`/', $line, $m)) {
        $skipTable = $m[1];
        $skipBlock = ! in_array($skipTable, $allowedTables, true);
        continue;
    }

    if ($skipBlock) {
        continue;
    }

    if (preg_match('/^CREATE TABLE `/', $line)) {
        $inCreateTable = true;
        continue;
    }

    if ($inCreateTable) {
        if (preg_match('/\) ENGINE=/', $line)) {
            $inCreateTable = false;
        }
        continue;
    }

    if (preg_match('/^ALTER TABLE `/', $line)) {
        $inAlterTable = true;
        continue;
    }

    if ($inAlterTable) {
        if (preg_match('/;\s*$/', trim($line))) {
            $inAlterTable = false;
        }
        continue;
    }

    if (preg_match('/^(START TRANSACTION|COMMIT|\/\*!)/', trim($line))) {
        continue;
    }

    if (preg_match('/^SET (SQL_MODE|time_zone|AUTOCOMMIT|CHARACTER_SET)/', trim($line))) {
        continue;
    }

    if (preg_match('/^INSERT INTO `([^`]+)`/', $line, $m)) {
        if (in_array($m[1], $allowedTables, true)) {
            $line = preg_replace('/^INSERT INTO/', 'REPLACE INTO', $line, 1);
            $converted++;
        } else {
            continue;
        }
    }

    fwrite($out, $line);
}

fwrite($out, "\nSET FOREIGN_KEY_CHECKS=1;\n");

fclose($in);
fclose($out);

fwrite(STDERR, "Converted INSERT statements: {$converted}\n");
fwrite(STDERR, "Output: {$outPath}\n");
echo "OK\n";
