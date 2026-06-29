<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public webhook — protected by the secret embedded in the URL (Apps Script posts here).
Route::post('webhook/google/{secret}', [WebhookController::class, 'google']);

// Dashboard API — guarded by a static bearer token (Phase 1, local).
Route::middleware('api.token')->group(function () {
    Route::get('state', [ApiController::class, 'state']);

    Route::post('leads', [ApiController::class, 'storeLead']);
    Route::patch('leads/{lead}', [ApiController::class, 'updateLead']);
    Route::delete('leads/{lead}', [ApiController::class, 'destroyLead']);
    Route::post('leads/{lead}/messages', [ApiController::class, 'storeMessage']);

    Route::post('tasks', [ApiController::class, 'storeTask']);
    Route::patch('tasks/{task}', [ApiController::class, 'updateTask']);
    Route::delete('tasks/{task}', [ApiController::class, 'destroyTask']);

    Route::patch('automations/{automation}', [ApiController::class, 'updateAutomation']);
    Route::put('settings', [ApiController::class, 'updateSettings']);

    Route::post('seed', [ApiController::class, 'seed']);
    Route::post('clear', [ApiController::class, 'clear']);
});
