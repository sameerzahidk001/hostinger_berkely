<?php
/**
 * One-time: class_batches tables + class_schedules.batch_id
 * /public/_migrate_class_batches.php?t=berkeley-class-batches-2026
 */
$token = $_GET['t'] ?? '';
if ($token !== 'berkeley-class-batches-2026') {
    http_response_code(404);
    echo 'Not found';
    exit;
}

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$result = ['ok' => false];

try {
    if (! Schema::hasTable('class_batches')) {
        Schema::create('class_batches', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('head_of_faculty_id')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
        });
        $result['class_batches'] = 'created';
    } else {
        $result['class_batches'] = 'exists';
    }

    if (! Schema::hasTable('class_batch_instructor')) {
        Schema::create('class_batch_instructor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_batch_id');
            $table->unsignedBigInteger('instructor_id');
            $table->timestamps();
            $table->unique(['class_batch_id', 'instructor_id']);
        });
        $result['class_batch_instructor'] = 'created';
    } else {
        $result['class_batch_instructor'] = 'exists';
    }

    if (! Schema::hasTable('class_batch_student')) {
        Schema::create('class_batch_student', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_batch_id');
            $table->unsignedBigInteger('student_id');
            $table->timestamps();
            $table->unique(['class_batch_id', 'student_id']);
        });
        $result['class_batch_student'] = 'created';
    } else {
        $result['class_batch_student'] = 'exists';
    }

    if (Schema::hasTable('class_schedules') && ! Schema::hasColumn('class_schedules', 'batch_id')) {
        Schema::table('class_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('batch_name');
            $table->index('batch_id');
        });
        $result['batch_id'] = 'added';
    } else {
        $result['batch_id'] = 'exists_or_skip';
    }

    $migration = '2026_09_17_000002_create_class_batches_tables';
    if (Schema::hasTable('migrations') && ! DB::table('migrations')->where('migration', $migration)->exists()) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => ((int) DB::table('migrations')->max('batch')) + 1,
        ]);
        $result['migrations_table'] = 'recorded';
    }

    try {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        $result['cache'] = 'cleared';
    } catch (Throwable $e) {
        $result['cache'] = $e->getMessage();
    }

    $result['ok'] = true;
} catch (Throwable $e) {
    $result['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($result);
if (! empty($result['ok'])) {
    @unlink(__FILE__);
}
