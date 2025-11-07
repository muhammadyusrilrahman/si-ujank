<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->removeStaleViteHotFile();
    }

    protected function removeStaleViteHotFile(): void
    {
        if (! app()->isLocal()) {
            return;
        }

        $hotFile = public_path('hot');

        if (! is_file($hotFile)) {
            return;
        }

        $url = trim((string) @file_get_contents($hotFile));

        if ($url === '' || $this->isDevServerReachable($url)) {
            return;
        }

        @unlink($hotFile);
    }

    protected function isDevServerReachable(string $url): bool
    {
        $parts = parse_url($url);

        if (! $parts || empty($parts['host'])) {
            return false;
        }

        $host = trim($parts['host'], '[]');
        $port = $parts['port'] ?? 80;

        $connection = @fsockopen($host, $port, $errno, $errstr, 0.5);

        if ($connection) {
            fclose($connection);

            return true;
        }

        return false;
    }
}
