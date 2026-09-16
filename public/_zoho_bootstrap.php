<?php
/**
 * One-time Hostinger bootstrap: writes Zoho OAuth into .env then self-deletes.
 * Hit: /_zoho_bootstrap.php?token=TOKEN
 * Remove from repo after live is connected.
 */

$token = 'berkeley-zoho-bootstrap-2026-bdm';
if (!isset($_GET['token']) || !hash_equals($token, (string) $_GET['token'])) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "forbidden\n";
    exit;
}

$root = dirname(__DIR__);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envPath) || !is_writable($envPath)) {
    // Hostinger often has Laravel in public_html with .env next to artisan
    $alt = $root . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '.env';
    if (is_file($alt) && is_writable($alt)) {
        $envPath = $alt;
        $root = dirname($alt);
    }
}

if (!is_file($envPath)) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "env_missing\n";
    exit;
}

$pairs = [
    'ZOHO_ACCOUNT_EMAIL' => 'bdm@berkeleyme.com',
    'ZOHO_ACCOUNTS_URL' => 'https://accounts.zoho.com',
    'ZOHO_MEETING_URL' => 'https://meeting.zoho.com',
    'ZOHO_CLIENT_ID' => '1000.WW01EMQ73P97FDWYKHPPPL46PLCG4F',
    'ZOHO_CLIENT_SECRET' => 'df268c77655674d5c29009939053e5a8f929ab1890',
    'ZOHO_REFRESH_TOKEN' => '1000.621d0b7d565917edf2fa28a26ca07c9c.fd6eeb269a175580907d41c10ead466e',
    'ZOHO_ORG_ID' => '667841096',
    'ZOHO_PRESENTER_ZUID' => '813723220',
    'ZOHO_WORKDRIVE_FOLDER_ID' => 'spuntc5376892cd29487691e0c9d20e212f62',
    'ZOHO_CALENDAR_UID' => 'a911f7d47515486dadb3ef2e6a0bcb34',
    'ZOHO_TIMEZONE' => 'Asia/Dubai',
];

$contents = file_get_contents($envPath);
foreach ($pairs as $key => $value) {
    $line = $key . '=' . $value;
    if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $contents)) {
        $contents = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $contents);
    } else {
        $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
    }
}

if (file_put_contents($envPath, $contents) === false) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "write_failed\n";
    exit;
}

// Clear Laravel config cache if present
foreach (['bootstrap/cache/config.php', 'bootstrap/cache/packages.php', 'bootstrap/cache/services.php'] as $rel) {
    $cacheFile = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
}

@unlink(__FILE__);

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'message' => 'Zoho OAuth written to .env; bootstrap removed',
    'env' => basename(dirname($envPath)) . '/.env',
]);
