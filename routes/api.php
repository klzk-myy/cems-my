<?php

use App\Http\Controllers\Api\Compliance\CaseController;
use App\Http\Controllers\Api\Compliance\FindingController;
use App\Http\Controllers\Api\Compliance\RiskController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SanctionController;
use App\Http\Controllers\StrController;
use App\Http\Controllers\TransactionController;
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
    // Transactions API
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('/transactions/{transaction}/approve', [TransactionController::class, 'approve']);
    Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel']);

    // Customers API
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy']);
    Route::get('/customers/{customer}/history', [CustomerController::class, 'customerHistory']);
    Route::post('/customers/{customer}/kyc', [CustomerController::class, 'uploadDocument']);

    // STR API
    Route::get('/str', [StrController::class, 'index']);
    Route::post('/str', [StrController::class, 'store']);
    Route::get('/str/{str}', [StrController::class, 'show']);
    Route::post('/str/{str}/submit', [StrController::class, 'submit']);

    // Sanctions API
    Route::post('/sanctions/search', [SanctionController::class, 'search']);
    Route::post('/sanctions/upload', [SanctionController::class, 'upload']);

    // Reports API
    Route::post('/reports/lctr', [ReportController::class, 'generateLCTR'])
        ->name('api.reports.lctr');
    Route::post('/reports/lctr/status', [ReportController::class, 'updateLCTRStatus'])
        ->name('api.reports.lctr.status');
    Route::post('/reports/msb2', [ReportController::class, 'generateMSB2'])
        ->name('api.reports.msb2');
    Route::post('/reports/msb2/status', [ReportController::class, 'updateMSB2Status'])
        ->name('api.reports.msb2.status');
    Route::get('/reports/download/{filename}', [ReportController::class, 'download']);

    // Compliance Findings API
    Route::prefix('compliance')->group(function () {
        Route::get('/findings', [FindingController::class, 'index']);
        Route::get('/findings/stats', [FindingController::class, 'stats']);
        Route::get('/findings/{id}', [FindingController::class, 'show']);
        Route::post('/findings/{id}/dismiss', [FindingController::class, 'dismiss']);

        // Cases API
        Route::get('/cases', [CaseController::class, 'index']);
        Route::post('/cases', [CaseController::class, 'store']);
        Route::get('/cases/{id}', [CaseController::class, 'show']);
        Route::patch('/cases/{id}', [CaseController::class, 'update']);
        Route::post('/cases/{id}/notes', [CaseController::class, 'addNote']);
        Route::post('/cases/{id}/close', [CaseController::class, 'close']);
        Route::post('/cases/{id}/escalate', [CaseController::class, 'escalate']);
        Route::get('/cases/{id}/timeline', [CaseController::class, 'timeline']);
    });

    // Risk API
    Route::get('/risk/portfolio', [RiskController::class, 'portfolio']);
    Route::get('/risk/{customerId}', [RiskController::class, 'show']);
    Route::get('/risk/{customerId}/history', [RiskController::class, 'history']);
    Route::post('/risk/{customerId}/recalculate', [RiskController::class, 'recalculate']);
    Route::post('/risk/{customerId}/lock', [RiskController::class, 'lock']);
    Route::post('/risk/{customerId}/unlock', [RiskController::class, 'unlock']);
});
