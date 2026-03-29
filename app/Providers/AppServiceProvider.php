<?php

namespace App\Providers;

use App\Octane\NgPatterns\Pattern2\ReportContext;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AppServiceProvider extends ServiceProvider
{
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
        // FrankenPHP の X-Sendfile に対応する。
        // リクエストの X-Sendfile-Type ヘッダーを信頼し、
        // BinaryFileResponse が X-Sendfile レスポンスヘッダーを自動付与するようにする。
        // Octane ではワーカー起動時に1回だけ呼べば十分。
        BinaryFileResponse::trustXSendfileTypeHeader();
    }
}
