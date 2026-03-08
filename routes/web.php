<?php

use App\Http\Controllers\CsvController;
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

Route::get('/download/{filename}', [DownloadController::class, 'download'])
    ->where('filename', '[a-zA-Z0-9._-]+');

Route::get('/csv', [CsvController::class, 'page']);
Route::post('/api/csv/generate', [CsvController::class, 'generate'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/mercure/sse-demo', [MercureController::class, 'page']);
Route::get('/mercure/receiver', [MercureController::class, 'receiver']);
Route::post('/api/mercure/publish', [MercureController::class, 'publish'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
