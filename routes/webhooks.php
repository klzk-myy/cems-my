<?php

use App\Http\Controllers\Api\SanctionsWebhookController;
use Illuminate\Support\Facades\Route;

// Rate-limit the webhook so a missing/guessed token cannot be brute-forced
// with unlimited requests.
Route::post('/sanctions/update', [SanctionsWebhookController::class, '__invoke'])
    ->middleware('throttle:10,1')
    ->name('api.v1.webhooks.sanctions.update');

Route::get('/sanctions/health', [SanctionsWebhookController::class, 'health'])
    ->middleware('throttle:30,1')
    ->name('api.v1.webhooks.sanctions.health');
