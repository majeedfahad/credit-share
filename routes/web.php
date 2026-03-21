<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FamilyController;

Route::get("/login", [AuthController::class, "showLogin"])->name("login");
Route::post("/login", [AuthController::class, "login"]);
Route::get("/verify-otp", [AuthController::class, "showVerifyOtp"])->name("verify-otp");
Route::post("/verify-otp", [AuthController::class, "verifyOtp"])->name("verify-otp.submit");

Route::middleware("auth")->group(function () {
    Route::post("/logout", [AuthController::class, "logout"])->name("logout");
    
    // Dashboard
    Route::get("/", [DashboardController::class, "index"])->name("dashboard");
    
    // Card details
    Route::get("/card/{card}", [DashboardController::class, "cardDetails"])->name("card.details");
    
    // Update payment category
    Route::post("/payment/{payment}/category", [DashboardController::class, "updateCategory"])->name("payment.category");
    
    // Update budget
    Route::post("/cycle/{cycle}/budget", [DashboardController::class, "updateBudget"])->name("cycle.budget");
    
    // Update payment note
    Route::post("/payment/{payment}/note", [FamilyController::class, "updateNote"])->name("payment.note");
});
