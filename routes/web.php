<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/compliance', [DashboardController::class, 'compliance'])
        ->name('compliance');
    Route::get('/accounting', [DashboardController::class, 'accounting'])
        ->name('accounting');
    Route::get('/reports', [DashboardController::class, 'reports'])
        ->name('reports');
});

require __DIR__.'/auth.php';
