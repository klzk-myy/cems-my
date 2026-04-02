<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RevaluationController;
use App\Http\Controllers\StockCashController;
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
        ->name('transactions');
    Route::get('/transactions/create', [TransactionController::class, 'create'])
        ->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])
        ->name('transactions.store');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])
        ->name('transactions.show');
    Route::get('/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])
        ->name('transactions.receipt')
        ->middleware('auth');
    Route::post('/transactions/{transaction}/approve', [TransactionController::class, 'approve'])
        ->name('transactions.approve')
        ->middleware('role:manager');
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
    });

    // Reports - Managers, Compliance, and Admin
    Route::get('/reports', [DashboardController::class, 'reports'])
        ->name('reports')
        ->middleware('role:manager');

    Route::middleware('role:manager')->group(function () {
        Route::get('/reports/lctr', [ReportController::class, 'lctr'])->name('reports.lctr');
        Route::get('/reports/lctr/generate', [ReportController::class, 'lctrGenerate'])->name('reports.lctr.generate');
        Route::get('/reports/msb2', [ReportController::class, 'msb2'])->name('reports.msb2');
        Route::get('/reports/msb2/generate', [ReportController::class, 'msb2Generate'])->name('reports.msb2.generate');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
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
});

require __DIR__.'/auth.php';
