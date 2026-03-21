<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get("/user", function (Request $request) {
    return $request->user();
})->middleware("auth:sanctum");

Route::middleware([\App\Http\Middleware\CheckApiKey::class])->group(function () {
    Route::post("/payments/incoming", [\App\Http\Controllers\IncomingPaymentController::class, "store"]);
    Route::post("/payments/retry", [\App\Http\Controllers\IncomingPaymentController::class, "retry"]);
});

Route::post("/telegram/webhook", [\App\Http\Controllers\TelegramWebhookController::class, "handle"]);

Route::middleware([\App\Http\Middleware\CheckApiKey::class])->group(function () {
    Route::get("/system/health", [\App\Http\Controllers\SystemHealthController::class, "status"]);
});
