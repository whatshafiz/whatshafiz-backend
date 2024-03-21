<?php

use App\Http\Controllers\AnswerAttemptController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseTypeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\QuranQuestionController;
use App\Http\Controllers\RegulationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TeacherStudentController;
use App\Http\Controllers\UniversityController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsappGroupController;
use App\Http\Controllers\WhatsappMessengerNumberController;
use Illuminate\Support\Facades\Route;

Route::get('countries', [CountryController::class, 'index']);

Route::post('users/check', [UserController::class, 'check']);
Route::post('register', [UserController::class, 'register'])->name('register');
Route::post('login', [UserController::class, 'login'])->name('login');
Route::post('forgot-password', [UserController::class, 'forgotPassword']);
Route::post('update-password', [UserController::class, 'updatePassword']);

Route::get('regulations/{regulation:slug}', [w::class, 'show']);
Route::get('courses/available', [CourseController::class, 'indexAvailableCourses']);
Route::get('comments/approved/{courseType:slug}', [CommentController::class, 'indexApprovedComments']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::post('users/{user}/ban', [UserController::class, 'banUser']);
    Route::post('users/{user}/roles', [UserController::class, 'assignRole'])->middleware('admin');
    Route::delete('users/{user}/roles/{role}', [UserController::class, 'removeRole']);
    Route::delete('users/{user}/courses/{course}', [UserController::class, 'removeCourse']);
    Route::delete('users/{user}/whatsapp-groups/{whatsapp_group}', [UserController::class, 'removeWhatsappGroup']);

    Route::get('settings', [SettingController::class, 'index']);
    Route::get('settings/paginate', [SettingController::class, 'indexPaginate']);
    Route::get('settings/{setting}', [SettingController::class, 'show']);
    Route::put('settings/{setting}', [SettingController::class, 'update']);

    Route::post('register/verification-code/send', [UserController::class, 'sendVerificationCode']);
    Route::post('register/verification-code/verify', [UserController::class, 'verifyVerificationCode']);

    Route::put('profile', [UserController::class, 'saveProfile']);
    Route::get('profile', [UserController::class, 'profile'])->name('profile');
    Route::post('profile/courses', [UserController::class, 'saveCourse']);
    Route::get('profile/courses', [UserController::class, 'getUserCourses']);

    Route::get('regulations', [RegulationController::class, 'index']);
    Route::post('regulations/{regulation:slug}', [RegulationController::class, 'update']);

    Route::get('countries/paginate', [CountryController::class, 'indexPaginate']);
    Route::get('cities/paginate', [CountryController::class, 'indexCitiesPaginate']);
    Route::get('cities/{city}', [CountryController::class, 'showCity']);
    Route::put('cities/{city}', [CountryController::class, 'updateCity']);
    Route::get('countries/{country}', [CountryController::class, 'show']);
    Route::put('countries/{country}', [CountryController::class, 'update']);
    Route::delete('countries/{country}', [CountryController::class, 'destroy']);
    Route::delete('cities/{city}', [CountryController::class, 'destroyCity']);
    Route::get('countries/{country}/cities', [CountryController::class, 'cities']);
    Route::post('countries/{country}/cities', [CountryController::class, 'storeCity']);

    Route::get('my/courses', [CourseController::class, 'myCourses']);
    Route::get('courses/paginate', [CourseController::class, 'indexPaginate']);
    Route::get('courses/{course}/teacher-students-matchings', [CourseController::class, 'getTeacherStudentsMatchings']);
    Route::post(
        'courses/{course}/teacher-students-matchings',
        [CourseController::class, 'startTeacherStudentsMatchings']
    );
    Route::post('courses/{course}/whatsapp-groups', [CourseController::class, 'organizeWhatsappGroups']);
    Route::apiResource('courses', CourseController::class);

    Route::get('universities/paginate', [UniversityController::class, 'indexPaginate']);
    Route::apiResource('universities', UniversityController::class);
    Route::get('faculties/paginate', [UniversityController::class, 'indexFacultiesPaginate']);
    Route::get('faculties/{faculty}', [UniversityController::class, 'showFaculty']);
    Route::put('faculties/{faculty}', [UniversityController::class, 'updateFaculty']);
    Route::delete('faculties/{faculty}', [UniversityController::class, 'destroyFaculty']);
    Route::get('departments/paginate', [UniversityController::class, 'indexDepartmentsPaginate']);
    Route::get('departments/{department}', [UniversityController::class, 'showDepartment']);
    Route::put('departments/{department}', [UniversityController::class, 'updateDepartment']);
    Route::delete('departments/{department}', [UniversityController::class, 'destroyDepartment']);
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

    Route::get('my/whatsapp-groups', [WhatsappGroupController::class, 'myWhatsappGroups']);
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
    Route::get('my/complaints', [ComplaintController::class, 'myComplaints']);

    Route::apiResource('comments', CommentController::class);
    Route::get('my/comments', [CommentController::class, 'myComments']);

    Route::apiResource('quran-questions', QuranQuestionController::class);
    Route::post('quran-questions-assign', [QuranQuestionController::class, 'assign']);

    Route::apiResource('answer-attempts', AnswerAttemptController::class);
    Route::get('my/answer-attempts', [AnswerAttemptController::class, 'myAnswerAttempts']);

    Route::get('my/teachers', [TeacherStudentController::class, 'myTeachers']);
    Route::put('my/students/{teacherStudent}', [TeacherStudentController::class, 'updateStudentStatus']);
    Route::get('my/students', [TeacherStudentController::class, 'myStudents']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('permissions', [PermissionController::class, 'index']);

    Route::get('course-types/paginate', [CourseTypeController::class, 'indexPaginate']);
    Route::apiResource('course-types', CourseTypeController::class);

    Route::get('whatsapp-messenger-numbers', [WhatsappMessengerNumberController::class, 'index']);
    Route::post('whatsapp-messenger-numbers', [WhatsappMessengerNumberController::class, 'store']);
    Route::post(
        'whatsapp-messenger-numbers/send-test-message',
        [WhatsappMessengerNumberController::class, 'sendTestMessage']
    );

    Route::get('roles/paginate', [RoleController::class, 'indexPaginate']);
    Route::apiResource('roles', RoleController::class);
});
