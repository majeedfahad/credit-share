<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\AuthController;
use \App\Http\Controllers\FamilyController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login']);
Route::post('/logout', [AuthController::class,'logout'])->middleware('auth');

Route::get('/', function () {
    $user = auth()->user();
    if ($user && $user->default_card_id) {
        return redirect("/family/view/{$user->default_card_id}");
    }
    // لو مافيه مستخدم أو ماحدد بطاقة، حاول اختر أول بطاقة
    $firstCardId = \App\Models\Card::where('is_active', true)->value('id');
    return $firstCardId ? redirect("/family/view/{$firstCardId}") : view('no-card');
});

Route::get('/family/view/{card}', [FamilyController::class,'index'])->middleware(\App\Http\Middleware\CheckPersonalToken::class);
Route::post('/family/payments/{payment}/note', [FamilyController::class,'updateNote'])->middleware(\App\Http\Middleware\CheckPersonalToken::class);
