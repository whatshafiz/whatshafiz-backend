<?php

use App\Http\Controllers\RegulationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('users/check', [UserController::class, 'check']);
Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);

Route::get('regulations/{regulation:slug}', [RegulationController::class, 'show']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('regulations', [RegulationController::class, 'index']);
    Route::post('regulations/{regulation:slug}', [RegulationController::class, 'update']);
});
