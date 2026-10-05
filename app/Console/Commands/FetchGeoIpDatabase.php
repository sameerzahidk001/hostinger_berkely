<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchGeoIpDatabase extends Command
{
    protected $signature = 'berkely:fetch-geoip-db';

    protected $description = 'Download a local country GeoIP database so analytics does not hit a daily IP API limit';

    public function handle(): int
    {
        $path = storage_path('app/geoip.mmdb');
        if (is_file($path) && filemtime($path) > time() - (40 * 86400)) {
            $this->info('GeoIP database is already up to date.');

            return self::SUCCESS;
        }

        $month = now()->format('Y-m');
        $url = "https://download.db-ip.com/free/dbip-country-lite-{$month}.mmdb.gz";

        $this->info('Downloading ' . $url);

        try {
            $response = Http::timeout(60)->get($url);
            if (! $response->successful()) {
                $this->error('Download failed HTTP ' . $response->status());

                return self::FAILURE;
            }

            $decoded = @gzdecode($response->body());
            if ($decoded === false || strlen($decoded) < 1000) {
                $this->error('Could not decompress GeoIP database.');

                return self::FAILURE;
            }

            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }

            file_put_contents($path, $decoded);
            $this->info('Saved ' . $path);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
