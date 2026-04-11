<?php

use App\Http\Controllers\Api\Compliance\AlertController;
use App\Http\Controllers\Api\Compliance\CaseController;
use App\Http\Controllers\Api\Compliance\DashboardController;
use App\Http\Controllers\Api\Compliance\EddController;
use App\Http\Controllers\Api\Compliance\FindingController;
use App\Http\Controllers\Api\Compliance\RiskController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Report\RegulatoryReportController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SanctionController;
use App\Http\Controllers\StrController;
use App\Http\Controllers\Transaction\TransactionApprovalController;
use App\Http\Controllers\Transaction\TransactionCancellationController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// DEPRECATED: Use api/v1 routes instead. This file will be removed in future versions.

/*
|--------------------------------------------------------------------------
| API Routes (Legacy)
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| IMPORTANT: This file is deprecated. Please use routes/api_v1.php instead.
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
    Route::post('/transactions/{transaction}/approve', [TransactionApprovalController::class, 'approve'])
        ->middleware(['role:manager', 'mfa.verified']);
    Route::post('/transactions/{transaction}/cancel', [TransactionCancellationController::class, 'cancel'])
        ->middleware(['role:manager', 'mfa.verified']);

    // Customers API
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('throttle:30,1'); // 30 requests per minute
    Route::get('/customers/{customer}', [CustomerController::class, 'show']);
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])
        ->middleware('throttle:30,1');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])
        ->middleware('throttle:15,1'); // Stricter limit for destructive operation
    Route::get('/customers/{customer}/history', [CustomerController::class, 'customerHistory']);
    Route::post('/customers/{customer}/kyc', [CustomerController::class, 'uploadDocument'])
        ->middleware('throttle:30,1');

    // STR API
    Route::get('/str', [StrController::class, 'index']);
    Route::post('/str', [StrController::class, 'store']);
    Route::get('/str/{str}', [StrController::class, 'show']);
    Route::post('/str/{str}/submit', [StrController::class, 'submit']);

    // Sanctions API
    Route::post('/sanctions/search', [SanctionController::class, 'search']);
    Route::post('/sanctions/upload', [SanctionController::class, 'upload']);

    // Reports API
    Route::post('/reports/lctr', [RegulatoryReportController::class, 'generateLCTR'])
        ->name('api.reports.lctr');
    Route::post('/reports/lctr/status', [RegulatoryReportController::class, 'updateLCTRStatus'])
        ->name('api.reports.lctr.status');
    Route::post('/reports/msb2', [RegulatoryReportController::class, 'generateMSB2'])
        ->name('api.reports.msb2');
    Route::post('/reports/msb2/status', [RegulatoryReportController::class, 'updateMSB2Status'])
        ->name('api.reports.msb2.status');
    Route::get('/reports/download/{filename}', [ReportController::class, 'download']);

    // Compliance Findings API
    Route::prefix('compliance')->group(function () {
        Route::get('/findings', [FindingController::class, 'index']);
        Route::get('/findings/stats', [FindingController::class, 'stats']);
        Route::get('/findings/{id}', [FindingController::class, 'show']);
        Route::post('/findings/{id}/dismiss', [FindingController::class, 'dismiss']);

        // Alerts API
        Route::get('/alerts', [AlertController::class, 'index']);
        Route::get('/alerts/summary', [AlertController::class, 'summary']);
        Route::get('/alerts/overdue', [AlertController::class, 'overdue']);
        Route::post('/alerts/bulk-assign', [AlertController::class, 'bulkAssign']);
        Route::post('/alerts/bulk-resolve', [AlertController::class, 'bulkResolve']);
        Route::post('/alerts/auto-assign', [AlertController::class, 'autoAssign']);
        Route::get('/alerts/{id}', [AlertController::class, 'show']);

        // Cases API
        Route::get('/cases', [CaseController::class, 'index']);
        Route::post('/cases', [CaseController::class, 'store']);
        Route::get('/cases/{id}', [CaseController::class, 'show']);
        Route::patch('/cases/{id}', [CaseController::class, 'update']);
        Route::post('/cases/{id}/notes', [CaseController::class, 'addNote']);
        Route::post('/cases/{id}/close', [CaseController::class, 'close']);
        Route::post('/cases/{id}/escalate', [CaseController::class, 'escalate']);
        Route::get('/cases/{id}/timeline', [CaseController::class, 'timeline']);

        // EDD API
        Route::get('/edd', [EddController::class, 'index']);
        Route::get('/edd/templates', [EddController::class, 'templates']);
        Route::get('/edd/{id}', [EddController::class, 'show']);
        Route::post('/edd/{id}/questionnaire', [EddController::class, 'submitQuestionnaire']);
        Route::post('/edd/{id}/approve', [EddController::class, 'approve']);
        Route::post('/edd/{id}/reject', [EddController::class, 'reject']);
    });

    // Risk API
    Route::get('/risk/portfolio', [RiskController::class, 'portfolio']);
    Route::get('/risk/{customerId}', [RiskController::class, 'show']);
    Route::get('/risk/{customerId}/history', [RiskController::class, 'history']);
    Route::post('/risk/{customerId}/recalculate', [RiskController::class, 'recalculate']);
    Route::post('/risk/{customerId}/lock', [RiskController::class, 'lock']);
    Route::post('/risk/{customerId}/unlock', [RiskController::class, 'unlock']);

    // Dashboard API
    Route::prefix('compliance')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'kpis']);
        Route::get('/calendar', [DashboardController::class, 'calendar']);
        Route::get('/case-aging', [DashboardController::class, 'caseAging']);
        Route::get('/audit-trail', [DashboardController::class, 'auditTrail']);
        Route::get('/audit-trail/export', [DashboardController::class, 'auditTrailExport']);
        Route::get('/reports/auto', [DashboardController::class, 'autoReports']);
    });
});
