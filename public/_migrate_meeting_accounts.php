<?php
/**
 * One-time live bootstrap: meeting_accounts + class_schedules.meeting_account_id
 * Visit: /public/_migrate_meeting_accounts.php?t=berkeley-meeting-accounts-2026
 * Self-deletes after success.
 */
$token = $_GET['t'] ?? '';
if ($token !== 'berkeley-meeting-accounts-2026') {
    http_response_code(404);
    echo 'Not found';
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\MeetingAccount;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$result = [
    'meeting_accounts' => 'skipped',
    'meeting_account_id' => 'skipped',
    'seed' => 'skipped',
    'migrations_table' => 'skipped',
    'ok' => false,
];

try {
    if (! Schema::hasTable('meeting_accounts')) {
        Schema::create('meeting_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 16);
            $table->string('label');
            $table->string('host_email')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('credentials_json')->nullable();
            $table->string('timezone', 64)->default('Asia/Dubai');
            $table->timestamps();
            $table->index(['provider', 'is_active']);
            $table->index(['provider', 'is_default']);
        });
        $result['meeting_accounts'] = 'created';
    } else {
        $result['meeting_accounts'] = 'already_exists';
    }

    if (Schema::hasTable('class_schedules') && ! Schema::hasColumn('class_schedules', 'meeting_account_id')) {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('meeting_account_id')->nullable()->after('head_of_faculty_id');
            $table->index('meeting_account_id');
        });
        $result['meeting_account_id'] = 'added';
    } else {
        $result['meeting_account_id'] = Schema::hasColumn('class_schedules', 'meeting_account_id')
            ? 'already_exists'
            : 'no_class_schedules';
    }

    MeetingAccount::ensureDefaultZohoFromEnv();
    $result['seed'] = MeetingAccount::query()->where('provider', 'zoho')->exists() ? 'ok' : 'no_env_zoho';

    $migration = '2026_09_17_000001_create_meeting_accounts_table';
    if (Schema::hasTable('migrations')) {
        $exists = DB::table('migrations')->where('migration', $migration)->exists();
        if (! $exists) {
            $batch = (int) DB::table('migrations')->max('batch') + 1;
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
            $result['migrations_table'] = 'recorded';
        } else {
            $result['migrations_table'] = 'already_recorded';
        }
    }

    try {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        $result['cache'] = 'cleared';
    } catch (Throwable $e) {
        $result['cache'] = 'skip:' . $e->getMessage();
    }

    $result['ok'] = true;
    $result['accounts_count'] = Schema::hasTable('meeting_accounts')
        ? MeetingAccount::query()->count()
        : 0;
} catch (Throwable $e) {
    $result['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($result);

if (! empty($result['ok'])) {
    @unlink(__FILE__);
}
