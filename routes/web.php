<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\AuthController;
use \App\Http\Controllers\FamilyController;
use \App\Http\Middleware\CheckPersonalToken;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class,'showLogin'])->name('login');
Route::post('/login', [AuthController::class,'login']);

//Route::get('/', function () {
//    $user = auth()->user();
//    if ($user && $user->default_card_id) {
//        return redirect("/family/view/{$user->default_card_id}");
//    }
//
//    return redirect('login');
//});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class,'logout']);
    Route::get('/', function () {
       return redirect()->route('card.show', ['card' => auth()->user()->default_card_id]);
    });
    Route::get('/{card}', [FamilyController::class,'show'])->name('card.show');
    Route::post('/payments/{payment}/note', [FamilyController::class,'updateNote']);
});
