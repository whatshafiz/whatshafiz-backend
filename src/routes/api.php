<?php

use App\Http\Controllers\CountryController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\RegulationController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('users/check', [UserController::class, 'check']);
Route::post('register', [UserController::class, 'register'])->name('register');
Route::post('login', [UserController::class, 'login'])->name('login');

Route::get('regulations/{regulation:slug}', [RegulationController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('regulations', [RegulationController::class, 'index']);
    Route::post('regulations/{regulation:slug}', [RegulationController::class, 'update']);

    Route::get('countries', [CountryController::class, 'index']);
    Route::get('countries/{country}/cities', [CountryController::class, 'cities']);
    Route::post('countries/{country}/cities', [CountryController::class, 'storeCity']);

    Route::apiResource('periods', PeriodController::class);

    Route::apiResource('universities', UniversityController::class);
    Route::get('universities/{university}/faculties', [UniversityController::class, 'faculties']);
    Route::post('universities/{university}/faculties', [UniversityController::class, 'storeFaculty']);
    Route::get(
        'universities/{university}/faculties/{faculty}/departments',
        [UniversityController::class, 'departments']
    );
    Route::post(
        'universities/{university}/faculties/{faculty}/departments',
        [UniversityController::class, 'storeDepartment']
    );
});
