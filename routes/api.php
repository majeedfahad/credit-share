<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/payments/incoming', [\App\Http\Controllers\IncomingPaymentController::class, 'store'])
    ->middleware([\App\Http\Middleware\CheckApiKey::class]);
