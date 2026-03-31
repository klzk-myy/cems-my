<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\SanctionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/sanctions/search', [SanctionController::class, 'search']);
    Route::post('/sanctions/upload', [SanctionController::class, 'upload']);
    
    Route::post('/reports/lctr', [ReportController::class, 'generateLCTR']);
    Route::post('/reports/msb2', [ReportController::class, 'generateMSB2']);
    Route::get('/reports/download/{filename}', [ReportController::class, 'download']);
});
