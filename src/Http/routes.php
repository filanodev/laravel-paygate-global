<?php

use Illuminate\Support\Facades\Route;
use PayGate\LaravelPayGateGlobal\Http\Controllers\WebhookController;
use PayGate\LaravelPayGateGlobal\Http\Middleware\VerifyPayGateWebhook;

Route::group([
    'prefix' => 'paygate-global',
    'middleware' => ['web'],
], function () {
    Route::post('webhook', [WebhookController::class, 'handle'])
        ->name('paygate-global.webhook')
        ->middleware(VerifyPayGateWebhook::class);
});