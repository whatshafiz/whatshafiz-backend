<?php

use App\Http\Controllers\AnswerAttemptController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\QuranQuestionController;
use App\Http\Controllers\RegulationController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsappGroupController;
use Illuminate\Support\Facades\Route;

Route::get('countries', [CountryController::class, 'index']);

Route::post('users/check', [UserController::class, 'check']);
Route::post('register', [UserController::class, 'register'])->name('register');
Route::post('login', [UserController::class, 'login'])->name('login');
Route::post('forgot-password', [UserController::class, 'forgotPassword']);
Route::post('update-password', [UserController::class, 'updatePassword']);

Route::get('regulations/{regulation:slug}', [RegulationController::class, 'show']);
Route::get('courses/available', [CourseController::class, 'indexAvailableCourses']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('users', [UserController::class, 'index']);

    Route::get('settings', [SettingController::class, 'index']);
    Route::put('settings', [SettingController::class, 'update']);

    Route::post('register/verification-code/send', [UserController::class, 'sendVerificationCode']);
    Route::post('register/verification-code/verify', [UserController::class, 'verifyVerificationCode']);

    Route::put('profile', [UserController::class, 'saveProfile']);
    Route::get('profile', [UserController::class, 'profile'])->name('profile');
    Route::post('profile/courses', [UserController::class, 'saveCourse']);
    Route::get('profile/courses', [UserController::class, 'getUserCourses']);

    Route::get('regulations', [RegulationController::class, 'index']);
    Route::post('regulations/{regulation:slug}', [RegulationController::class, 'update']);

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

    Route::apiResource('complaints', ComplaintController::class);
    Route::get('my-complaints', [ComplaintController::class, 'myComplaints']);

    Route::apiResource('comments', CommentController::class);
    Route::get('my-comments', [CommentController::class, 'myComments']);

    Route::apiResource('quran-questions', QuranQuestionController::class);
    Route::post('quran-questions-assign', [QuranQuestionController::class, 'assign']);

    Route::apiResource('answer-attempts', AnswerAttemptController::class);
    Route::get('my-answer-attempts', [AnswerAttemptController::class, 'myAnswerAttempts']);
});
