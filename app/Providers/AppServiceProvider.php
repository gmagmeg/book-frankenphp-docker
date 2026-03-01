<?php

namespace App\Providers;

use App\Octane\NgPatterns\Pattern2\ReportContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Kept in-memory per worker process.
     */
    private static ?float $bootedAt = null;
    private static int $bootCount = 0;
    private static int $requestCount = 0;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // NG example for Octane: request-scoped data captured by singleton.
        $this->app->singleton(ReportContext::class, function () {
            return new ReportContext(request()->header('X-Request-Id'));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        self::$bootCount++;
        self::$bootedAt ??= microtime(true);

        Log::info('boot-probe: app booted', [
            'pid' => getmypid(),
            'boot_count' => self::$bootCount,
            'booted_at' => self::$bootedAt,
        ]);
    }

    /**
     * Used by a debug route to prove the worker keeps boot state across requests.
     */
    public static function recordBootProbeRequest(): array
    {
        self::$requestCount++;

        $payload = [
            'pid' => getmypid(),
            'boot_count' => self::$bootCount,
            'request_count' => self::$requestCount,
            'booted_at' => self::$bootedAt,
        ];

        Log::info('boot-probe: request handled', $payload);

        return $payload;
    }
}
