<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Course;
use App\Models\Page;

$failures = [];
$ok = 0;
$tested = 0;

$static = [
    '/',
    '/courses',
    '/contact',
    '/admission',
    '/cart',
    '/search',
    '/certifications',
    '/study-abroad',
    '/our-vision',
    '/complaints-and-misconducts',
    '/calender',
    '/berkeley-square',
    '/berkeley-square-london',
    '/berkeley-china',
    '/berkeley-middle-east-and-africa',
    '/tutor',
    '/admin/login',
    '/login',
    '/register',
    '/privacy-policy',
    '/general-policy',
    '/terms-and-conditions',
];

$urls = collect($static);

Page::query()
    ->whereNull('deleted_at')
    ->pluck('url')
    ->filter()
    ->each(function ($u) use (&$urls) {
        $urls->push('/' . ltrim($u, '/'));
    });

Course::query()
    ->whereNull('deleted_at')
    ->where('status', 1)
    ->pluck('slug')
    ->each(function ($s) use (&$urls) {
        $urls->push('/' . ltrim($s, '/'));
    });

$urls = $urls->unique()->values();

echo 'Auditing ' . $urls->count() . " internal routes...\n\n";

foreach ($urls as $path) {
    $tested++;

    try {
        $request = Illuminate\Http\Request::create($path, 'GET');
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $body = $response->getContent() ?? '';
        $kernel->terminate($request, $response);

        if ($status >= 500) {
            $failures[] = ['url' => $path, 'status' => $status, 'error' => 'HTTP ' . $status];
            if (count($failures) <= 50) {
                echo "FAIL {$status} {$path}\n";
            }
            continue;
        }

        if (str_contains($body, 'Whoops, something went wrong') || str_contains($body, 'Server Error')) {
            $failures[] = ['url' => $path, 'status' => $status, 'error' => 'Laravel error page'];
            if (count($failures) <= 50) {
                echo "FAIL {$status} {$path} (error page)\n";
            }
            continue;
        }

        $ok++;
        if ($tested <= 20 || $tested % 200 === 0) {
            echo "OK   {$status} {$path}\n";
        }
    } catch (Throwable $e) {
        $failures[] = ['url' => $path, 'status' => 0, 'error' => $e->getMessage()];
        if (count($failures) <= 50) {
            echo "FAIL EXC {$path} - {$e->getMessage()}\n";
        }
    }
}

echo "\n=== Summary ===\n";
echo "Tested: {$tested}\n";
echo "OK: {$ok}\n";
echo 'Failed: ' . count($failures) . "\n";

if ($failures) {
    echo "\nFirst failures:\n";
    foreach (array_slice($failures, 0, 30) as $f) {
        echo "- {$f['url']} => {$f['error']}\n";
    }
    file_put_contents(__DIR__ . '/audit-failures.json', json_encode($failures, JSON_PRETTY_PRINT));
    echo "\nFull list saved to audit-failures.json\n";
    exit(1);
}

exit(0);
