<?php

use App\Http\Controllers\Octane\NgPatterns\Pattern1\TenantController;
use App\Http\Controllers\Octane\NgPatterns\Pattern2\RequestSingletonCheckController;
use App\Http\Controllers\Octane\NgPatterns\Pattern2\RequestSingletonPageController;
use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug/boot-check', function () {
    return response()->json([
        'message' => 'Check laravel.log: boot should stay at 1 per worker while request_count increases.',
        'probe' => AppServiceProvider::recordBootProbeRequest(),
    ]);
});

Route::get('/debug/octane/ng-patterns/1/static-tenant', TenantController::class);
Route::get('/debug/octane/ng-patterns/2/request-singleton', RequestSingletonPageController::class);
Route::get('/debug/octane/ng-patterns/2/request-singleton/check', RequestSingletonCheckController::class);
