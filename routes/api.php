<?php

use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\PlatformLineWebhookController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/line/webhook', LineWebhookController::class)
    ->middleware(['api.monitor', 'throttle:line-webhook'])
    ->name('api.line.webhook');

Route::post('/line/platform-webhook', PlatformLineWebhookController::class)
    ->middleware(['api.monitor', 'throttle:line-webhook'])
    ->name('api.line.platform-webhook');

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->middleware(['api.monitor', 'throttle:stripe-webhook'])
    ->name('api.stripe.webhook');
