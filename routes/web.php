<?php

use App\Http\Controllers\QueueController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Queue testing routes
Route::prefix('queue')->group(function () {
    Route::post('/email', [QueueController::class, 'dispatchEmail']);
    Route::post('/data', [QueueController::class, 'dispatchData']);
    Route::post('/elasticsearch', [QueueController::class, 'dispatchElasticsearch']);
    Route::post('/multiple', [QueueController::class, 'dispatchMultiple']);
});
