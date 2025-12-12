<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DomainApiController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\NotificationSettingsController;

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


