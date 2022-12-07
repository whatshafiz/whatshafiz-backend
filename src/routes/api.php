<?php

use App\Http\Controllers\CountryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\RegulationController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsappGroupController;
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

    Route::apiResource('courses', CourseController::class);

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

    Route::apiResource('whatsapp-groups', WhatsappGroupController::class);
    Route::post('whatsapp-groups/{whatsapp_group}/users', [WhatsappGroupController::class, 'createUser']);
    Route::put(
        'whatsapp-groups/{whatsapp_group}/users/{whatsapp_group_user}',
        [WhatsappGroupController::class, 'updateUser']
    );
    Route::delete(
        'whatsapp-groups/{whatsapp_group}/users/{whatsapp_group_user}',
        [WhatsappGroupController::class, 'destroyUser']
    );
});
