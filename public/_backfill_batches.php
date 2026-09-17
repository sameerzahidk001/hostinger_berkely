<?php
/**
 * One-time: backfill ClassBatch from legacy schedules + default meeting account on orphans
 * /public/_backfill_batches.php?t=berkeley-backfill-batches-2026
 */
$token = $_GET['t'] ?? '';
if ($token !== 'berkeley-backfill-batches-2026') {
    http_response_code(404);
    echo 'Not found';
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ClassBatch;
use App\Models\MeetingAccount;
use Illuminate\Support\Facades\Artisan;

$result = ['ok' => false];
try {
    MeetingAccount::ensureDefaultZohoFromEnv();
    $result['stats'] = ClassBatch::backfillFromLegacySchedules();
    $result['ok'] = true;
    try {
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        $result['cache'] = 'cleared';
    } catch (Throwable $e) {
        $result['cache'] = $e->getMessage();
    }
} catch (Throwable $e) {
    $result['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($result);
if (! empty($result['ok'])) {
    @unlink(__FILE__);
}
