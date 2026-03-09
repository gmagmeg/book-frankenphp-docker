<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\MercureController;
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

Route::get('/mercure/sse-demo', [MercureController::class, 'page']);
Route::get('/mercure/csv-download', [MercureController::class, 'csvDownload']);
Route::post('/api/mercure/publish', [MercureController::class, 'publish'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/csv/download/{filename}', [DownloadController::class, 'download']);
