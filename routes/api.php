<?php

use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/line/webhook', LineWebhookController::class)
    ->middleware('throttle:line-webhook')
    ->name('api.line.webhook');

Route::post('/stripe/webhook', StripeWebhookController::class)
    ->middleware('throttle:stripe-webhook')
    ->name('api.stripe.webhook');
