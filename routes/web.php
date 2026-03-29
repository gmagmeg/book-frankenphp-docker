<?php

use App\Http\Controllers\DownloadController;
use App\Http\Controllers\MercureController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/mercure/sse-demo', [MercureController::class, 'page']);
Route::get('/mercure/csv-download', [MercureController::class, 'csvDownload']);
Route::post('/api/mercure/publish', [MercureController::class, 'publish'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::get('/api/csv/download/{filename}', [DownloadController::class, 'download']);
