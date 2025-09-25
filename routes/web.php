<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', fn() => redirect('/family/view'));
Route::get('/family/view/{card}', [\App\Http\Controllers\FamilyController::class,'index'])->middleware(\App\Http\Middleware\CheckViewToken::class);
Route::post('/family/payments/{payment}/note', [\App\Http\Controllers\FamilyController::class,'updateNote'])->middleware(\App\Http\Middleware\CheckViewToken::class);
