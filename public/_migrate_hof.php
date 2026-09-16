<?php
/**
 * One-time Hostinger migration bootstrap. Self-deletes after success.
 * Hit: /_migrate_hof.php?t=TOKEN
 */

$token = 'berkeley-hof-migrate-2026';
if (!isset($_GET['t']) || !hash_equals($token, (string) $_GET['t'])) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "forbidden\n";
    exit;
}

$publicDir = __DIR__;
$root = dirname($publicDir);

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

$results = [];

try {
    if (! Schema::hasTable('class_schedules')) {
        throw new RuntimeException('class_schedules table missing');
    }

    if (! Schema::hasColumn('class_schedules', 'head_of_faculty_id')) {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('head_of_faculty_id')->nullable()->after('instructor_id');
            $table->index('head_of_faculty_id');
        });
        $results['column'] = 'added';
    } else {
        $results['column'] = 'already_exists';
    }

    // Mark migration as run if migrations table exists
    if (Schema::hasTable('migrations')) {
        $migration = '2026_09_16_000001_add_head_of_faculty_to_class_schedules';
        $exists = Illuminate\Support\Facades\DB::table('migrations')
            ->where('migration', $migration)
            ->exists();
        if (! $exists) {
            $batch = (int) Illuminate\Support\Facades\DB::table('migrations')->max('batch');
            Illuminate\Support\Facades\DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch + 1,
            ]);
            $results['migrations_table'] = 'recorded';
        } else {
            $results['migrations_table'] = 'already_recorded';
        }
    }

    try {
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        $results['cache'] = 'cleared';
    } catch (Throwable $e) {
        $results['cache'] = $e->getMessage();
    }

    $results['ok'] = true;
    $results['has_column'] = Schema::hasColumn('class_schedules', 'head_of_faculty_id');
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
    ]);
    exit;
}

@unlink(__FILE__);

header('Content-Type: application/json');
echo json_encode($results);
