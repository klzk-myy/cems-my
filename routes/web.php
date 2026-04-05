<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnhancedDiligenceController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\JournalEntryWorkflowController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevaluationController;
use App\Http\Controllers\StockCashController;
use App\Http\Controllers\StrController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Root route redirects based on auth status
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return redirect('/login');
})->name('home');

// Protected routes with auth middleware
Route::middleware('auth')->group(function () {
    // Dashboard - All authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Transactions - All authenticated users
    Route::get('/transactions', [TransactionController::class, 'index'])
        ->name('transactions.index');
    Route::get('/transactions/create', [TransactionController::class, 'create'])
        ->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])
        ->name('transactions.store');

    // Also keep 'transactions' name for backward compatibility
    Route::get('/transactions/list', [TransactionController::class, 'index'])
        ->name('transactions');

    // Batch Transaction Upload - Manager only (must be before /transactions/{transaction})
    Route::middleware('role:manager')->group(function () {
        Route::get('/transactions/batch-upload', [TransactionController::class, 'showBatchUpload'])
            ->name('transactions.batch-upload');
        Route::post('/transactions/batch-upload', [TransactionController::class, 'processBatchUpload']);
        Route::get('/transactions/import/{import}', [TransactionController::class, 'showImportResults'])
            ->name('transactions.batch-upload.show');
        Route::get('/transactions/template', [TransactionController::class, 'downloadTemplate'])
            ->name('transactions.template');
    });

    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
        ->name('transactions.show');
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])
        ->name('transactions.receipt')
        ->middleware('auth');
    Route::post('/transactions/{transaction}/approve', [TransactionController::class, 'approve'])
        ->name('transactions.approve')
        ->middleware('role:manager');
    Route::get('/transactions/{transaction}/cancel', [TransactionController::class, 'showCancel'])
        ->name('transactions.cancel.show');
    Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])
        ->name('transactions.cancel');
    Route::get('/customers/create', [DashboardController::class, 'index'])
        ->name('customers.create');

    // Stock/Cash Management - All authenticated users (access controlled in controller)
    Route::get('/stock-cash', [StockCashController::class, 'index'])
        ->name('stock-cash.index');
    Route::post('/stock-cash/open', [StockCashController::class, 'openTill'])
        ->name('stock-cash.open');
    Route::post('/stock-cash/close', [StockCashController::class, 'closeTill'])
        ->name('stock-cash.close');

    // Compliance - Compliance officers and Admin only
    Route::middleware('role:compliance')->group(function () {
        Route::get('/compliance', [DashboardController::class, 'compliance'])
            ->name('compliance');
        Route::get('/compliance/flagged', [DashboardController::class, 'compliance'])
            ->name('compliance.flagged');
        Route::patch('/compliance/flags/{flaggedTransaction}/assign', [DashboardController::class, 'assignFlag'])
            ->name('compliance.flags.assign');
        Route::patch('/compliance/flags/{flaggedTransaction}/resolve', [DashboardController::class, 'resolveFlag'])
            ->name('compliance.flags.resolve');

        // STR Reports
        Route::get('/str', [StrController::class, 'index'])->name('str.index');
        Route::get('/str/create', [StrController::class, 'create'])->name('str.create');
        Route::post('/str', [StrController::class, 'store'])->name('str.store');
        Route::get('/str/{str}', [StrController::class, 'show'])->name('str.show');
        Route::get('/str/{str}/edit', [StrController::class, 'edit'])->name('str.edit');
        Route::put('/str/{str}', [StrController::class, 'update'])->name('str.update');
        Route::post('/str/{str}/submit-review', [StrController::class, 'submitForReview'])->name('str.submit-review');
        Route::post('/str/{str}/submit-approval', [StrController::class, 'submitForApproval'])->name('str.submit-approval');
        Route::post('/str/{str}/approve', [StrController::class, 'approve'])->name('str.approve');
        Route::post('/str/{str}/submit', [StrController::class, 'submit'])->name('str.submit');
        Route::post('/str/{str}/track-acknowledgment', [StrController::class, 'trackAcknowledgment'])->name('str.track-acknowledgment');

        // Generate STR from alert
        Route::post('/compliance/flags/{flaggedTransaction}/generate-str', [StrController::class, 'generateFromAlert'])
            ->name('compliance.flags.generate-str');

        // Enhanced Due Diligence
        Route::get('/compliance/edd', [EnhancedDiligenceController::class, 'index'])->name('compliance.edd.index');
        Route::get('/compliance/edd/create', [EnhancedDiligenceController::class, 'create'])->name('compliance.edd.create');
        Route::post('/compliance/edd', [EnhancedDiligenceController::class, 'store'])->name('compliance.edd.store');
        Route::get('/compliance/edd/{record}', [EnhancedDiligenceController::class, 'show'])->name('compliance.edd.show');
        Route::get('/compliance/edd/{record}/edit', [EnhancedDiligenceController::class, 'edit'])->name('compliance.edd.edit');
        Route::put('/compliance/edd/{record}', [EnhancedDiligenceController::class, 'update'])->name('compliance.edd.update');
        Route::post('/compliance/edd/{record}/submit', [EnhancedDiligenceController::class, 'submitReview'])->name('compliance.edd.submit');
    });

    // EDD Approval - Managers and Compliance officers
    Route::middleware('role:manager,compliance')->group(function () {
        Route::post('/compliance/edd/{record}/approve', [EnhancedDiligenceController::class, 'approve'])->name('compliance.edd.approve');
        Route::post('/compliance/edd/{record}/reject', [EnhancedDiligenceController::class, 'reject'])->name('compliance.edd.reject');
    });

    // Task Management - All authenticated users
    Route::middleware('auth')->group(function () {
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/my', [TaskController::class, 'myTasks'])->name('tasks.my');
        Route::get('/tasks/overdue', [TaskController::class, 'overdue'])->name('tasks.overdue');
        Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        Route::post('/tasks/{task}/acknowledge', [TaskController::class, 'acknowledge'])->name('tasks.acknowledge');
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('/tasks/{task}/cancel', [TaskController::class, 'cancel'])->name('tasks.cancel');
        Route::post('/tasks/{task}/escalate', [TaskController::class, 'escalate'])->name('tasks.escalate');
        Route::get('/api/tasks/stats', [TaskController::class, 'stats'])->name('api.tasks.stats');
    });

    // Accounting - Managers and Admin only
    Route::middleware('role:manager')->group(function () {
        Route::get('/accounting', [DashboardController::class, 'accounting'])
            ->name('accounting');

        // Journal Entries
        Route::get('/accounting/journal', [AccountingController::class, 'index'])->name('accounting.journal');
        Route::get('/accounting/journal/create', [AccountingController::class, 'create'])->name('accounting.journal.create');
        Route::post('/accounting/journal', [AccountingController::class, 'store'])->name('accounting.journal.store');
        Route::get('/accounting/journal/{entry}', [AccountingController::class, 'show'])->name('accounting.journal.show');
        Route::post('/accounting/journal/{entry}/reverse', [AccountingController::class, 'reverse'])->name('accounting.journal.reverse');

        // Ledger
        Route::get('/accounting/ledger', [LedgerController::class, 'index'])->name('accounting.ledger');
        Route::get('/accounting/ledger/{accountCode}', [LedgerController::class, 'account'])->name('accounting.ledger.account');

        // Financial Statements
        Route::get('/accounting/trial-balance', [FinancialStatementController::class, 'trialBalance'])->name('accounting.trial-balance');
        Route::get('/accounting/profit-loss', [FinancialStatementController::class, 'profitLoss'])->name('accounting.profit-loss');
        Route::get('/accounting/balance-sheet', [FinancialStatementController::class, 'balanceSheet'])->name('accounting.balance-sheet');

        // Revaluation
        Route::get('/accounting/revaluation', [RevaluationController::class, 'index'])->name('accounting.revaluation');
        Route::post('/accounting/revaluation/run', [RevaluationController::class, 'run'])->name('accounting.revaluation.run');
        Route::get('/accounting/revaluation/history', [RevaluationController::class, 'history'])->name('accounting.revaluation.history');

        // Accounting Period Management
        Route::get('/accounting/periods', [AccountingController::class, 'periods'])
            ->name('accounting.periods');
        Route::post('/accounting/periods/{period}/close', [AccountingController::class, 'closePeriod'])
            ->name('accounting.period.close');

        // Budget Reports
        Route::get('/accounting/budget', [AccountingController::class, 'budget'])
            ->name('accounting.budget');

        // Bank Reconciliation
        Route::get('/accounting/reconciliation', [AccountingController::class, 'reconciliation'])
            ->name('accounting.reconciliation');

        // Journal Entry Workflow
        Route::get('/accounting/journal/workflow', [JournalEntryWorkflowController::class, 'workflow'])->name('accounting.journal.workflow');
        Route::post('/accounting/journal/{entry}/approve', [JournalEntryWorkflowController::class, 'approve'])->name('accounting.journal.approve');
        Route::post('/accounting/journal/{entry}/submit', [JournalEntryWorkflowController::class, 'submit'])->name('accounting.journal.submit');

        // Cash Flow Statement
        Route::get('/accounting/cash-flow', [FinancialStatementController::class, 'cashFlow'])->name('accounting.cash-flow');

        // Financial Ratios
        Route::get('/accounting/ratios', [FinancialStatementController::class, 'ratios'])->name('accounting.ratios');

        // Fiscal Year Management
        Route::get('/accounting/fiscal-years', [FiscalYearController::class, 'index'])->name('accounting.fiscal-years');
        Route::post('/accounting/fiscal-years', [FiscalYearController::class, 'store'])->name('accounting.fiscal-years.store');
        Route::post('/accounting/fiscal-years/{year}/close', [FiscalYearController::class, 'close'])->name('accounting.fiscal-years.close');
        Route::get('/accounting/fiscal-years/{yearCode}/report', [FiscalYearController::class, 'report'])->name('accounting.fiscal-years.report');
    });

    // Reports - Managers and Admin only (compliance officers use dedicated compliance module)
    Route::get('/reports', [DashboardController::class, 'reports'])
        ->name('reports')
        ->middleware('role:manager,admin');

    Route::middleware('role:manager')->group(function () {
        Route::get('/reports/lctr', [ReportController::class, 'lctr'])->name('reports.lctr');
        Route::get('/reports/lctr/generate', [ReportController::class, 'lctrGenerate'])->name('reports.lctr.generate');
        Route::get('/reports/msb2', [ReportController::class, 'msb2'])->name('reports.msb2');
        Route::get('/reports/msb2/generate', [ReportController::class, 'msb2Generate'])->name('reports.msb2.generate');
        Route::get('/reports/lmca', [ReportController::class, 'lmca'])->name('reports.lmca');
        Route::get('/reports/lmca/generate', [ReportController::class, 'lmcaGenerate'])->name('reports.lmca.generate');
        Route::get('/reports/quarterly-lvr', [ReportController::class, 'quarterlyLvr'])->name('reports.quarterly-lvr');
        Route::get('/reports/quarterly-lvr/generate', [ReportController::class, 'quarterlyLvrGenerate'])->name('reports.quarterly-lvr.generate');
        Route::get('/reports/position-limit', [ReportController::class, 'positionLimit'])->name('reports.position-limit');
        Route::get('/reports/position-limit/generate', [ReportController::class, 'positionLimitGenerate'])->name('reports.position-limit.generate');
        Route::get('/reports/history', [ReportController::class, 'history'])->name('reports.history');
        Route::get('/reports/compare', [ReportController::class, 'compare'])->name('reports.compare');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        // Advanced Reports (Phase 3)
        Route::get('/reports/monthly-trends', [ReportController::class, 'monthlyTrends'])->name('reports.monthly-trends');
        Route::get('/reports/profitability', [ReportController::class, 'profitability'])->name('reports.profitability');
        Route::get('/reports/customer-analysis', [ReportController::class, 'customerAnalysis'])->name('reports.customer-analysis');
        Route::get('/reports/compliance-summary', [ReportController::class, 'complianceSummary'])->name('reports.compliance-summary');
    });

    // User Management - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');
    });

    // Exchange Rate History API
    Route::get('/api/rates/history/{currency}', [DashboardController::class, 'rateHistory'])
        ->name('api.rates.history');

    // Customer Transaction History
    Route::get('/customers/{customer}/history', [TransactionController::class, 'customerHistory'])
        ->name('customers.history');
    Route::get('/customers/{customer}/history/export', [TransactionController::class, 'exportCustomerHistory'])
        ->name('customers.export');

    // Till Reconciliation Report
    Route::get('/stock-cash/reconciliation', [StockCashController::class, 'reconciliationReport'])
        ->name('stock-cash.reconciliation');

    // Audit Log
    Route::middleware('role:manager')->group(function () {
        Route::get('/audit', [\App\Http\Controllers\AuditController::class, 'index'])
            ->name('audit.index');
        Route::get('/audit/dashboard', [\App\Http\Controllers\AuditController::class, 'dashboard'])
            ->name('audit.dashboard');
        Route::get('/audit/rotate', [\App\Http\Controllers\AuditController::class, 'rotate'])
            ->name('audit.rotate');
        Route::get('/audit/{log}', [\App\Http\Controllers\AuditController::class, 'show'])
            ->name('audit.show');
        Route::post('/audit/export', [\App\Http\Controllers\AuditController::class, 'export'])
            ->name('audit.export');
    });

    // Counter Management - All authenticated users
    Route::get('/counters', [\App\Http\Controllers\CounterController::class, 'index'])->name('counters.index');
    Route::get('/counters/{counter}/open', [\App\Http\Controllers\CounterController::class, 'showOpen'])->name('counters.open.show');
    Route::post('/counters/{counter}/open', [\App\Http\Controllers\CounterController::class, 'open'])->name('counters.open');
    Route::get('/counters/{counter}/close', [\App\Http\Controllers\CounterController::class, 'showClose'])->name('counters.close.show');
    Route::post('/counters/{counter}/close', [\App\Http\Controllers\CounterController::class, 'close'])->name('counters.close');
    Route::get('/counters/{counter}/status', [\App\Http\Controllers\CounterController::class, 'status'])->name('counters.status');
    Route::get('/counters/{counter}/history', [\App\Http\Controllers\CounterController::class, 'history'])->name('counters.history');
    Route::get('/counters/{counter}/handover', [\App\Http\Controllers\CounterController::class, 'showHandover'])->name('counters.handover.show');
    Route::post('/counters/{counter}/handover', [\App\Http\Controllers\CounterController::class, 'handover'])->name('counters.handover');
});

require __DIR__.'/auth.php';
