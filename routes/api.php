<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CloudflareWebhookController;
use App\Http\Controllers\Api\DomainApiController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\NotificationSettingsController;

/*
|--------------------------------------------------------------------------
| Cloudflare Worker Webhook Routes
|--------------------------------------------------------------------------
| These endpoints are called by Cloudflare Workers to check domains.
| Authenticated via Bearer token (CLOUDFLARE_WEBHOOK_SECRET).
*/
Route::prefix('cf')->middleware('cloudflare.webhook')->group(function () {
    Route::get('/domains/due', [CloudflareWebhookController::class, 'due']);
    Route::post('/domains/result', [CloudflareWebhookController::class, 'result']);
    Route::post('/domains/results', [CloudflareWebhookController::class, 'resultsBatch']);
});

Route::middleware('auth')->group(function () {
    Route::get('/domains', [DomainApiController::class, 'index']);
    Route::post('/domains', [DomainApiController::class, 'store']);
    Route::post('/domains/check-all', [DomainApiController::class, 'checkAll']);
    Route::post('/domains/{domain}/check', [DomainApiController::class, 'check']);
    Route::delete('/domains/{domain}', [DomainApiController::class, 'destroy']);
    Route::post('/imports/json', [DomainApiController::class, 'importJson']);
    Route::post('/imports/feed', [DomainApiController::class, 'importLatest']);

    Route::get('/plans', [PlanController::class, 'index']);
    Route::post('/subscriptions', [SubscriptionController::class, 'store']);

    Route::get('/notifications/settings', [NotificationSettingsController::class, 'show']);
    Route::post('/notifications/settings', [NotificationSettingsController::class, 'update']);
});


