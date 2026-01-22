<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DiplomaController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\InstituteController;
use App\Http\Controllers\Api\BookingController; // استدعاء المتحكم الجديد

/*
|--------------------------------------------------------------------------
| Public Routes (المسارات العامة)
|--------------------------------------------------------------------------
*/
// الكورسات
    Route::get('/courses', [CourseController::class, 'index']); // عرض كل الكورسات
    //Route::get('/courses/{course}', [CourseController::class, 'show']); // عرض كورس محدد
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// مسارات العرض العام (تتطلب تسجيل دخول فقط لرؤية حالة المفضلة)
Route::prefix('view')->middleware('auth:sanctum')->group(function () {
    // الكورسات
    //Route::get('/courses', [CourseController::class, 'index']);
    Route::get('/courses/{course}', [CourseController::class, 'show']);

    // الدبلومات
    Route::get('/diplomas', [DiplomaController::class, 'index']);
    Route::get('/diplomas/{diploma}', [DiplomaController::class, 'show']);
    //المعاهد
    Route::get('/institutes', [InstituteController::class, 'index']);
Route::get('/institutes/{institute}', [InstituteController::class, 'show']);
// 2. مسارات عرض الأقسام للعامة (أو الطلاب)
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (المسارات المحمية - تتطلب تسجيل دخول)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // 1. بيانات المستخدم والخروج
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    });

    // 2. المفضلة (لايك)
    Route::post('/like/toggle', [LikeController::class, 'toggle']);

    // 3. نظام الحجوزات - الطالب (جديد)
    Route::post('/booking/bookItem', [BookingController::class, 'bookItem']);

    // 4. إدارة الكورسات والدبلومات والحجوزات (للموظفين فقط)
    // نجمعها تحت ميدل وير الصلاحيات

    // إدارة الكورسات
    Route::middleware(['permission:course.manage'])->prefix('courses')->group(function () {
        Route::post('/store', [CourseController::class, 'store']);
        Route::post('/update/{course}', [CourseController::class, 'update']);
        Route::delete('/destroy/{course}', [CourseController::class, 'destroy']);
    });

    // إدارة الدبلومات
    Route::middleware(['permission:diploma.manage'])->prefix('diplomas')->group(function () {
        Route::post('/store', [DiplomaController::class, 'store']);
        Route::post('/update/{diploma}', [DiplomaController::class, 'update']);
        Route::delete('/destroy/{diploma}', [DiplomaController::class, 'destroy']);
    });
    //إدارة المعاهد
    Route::middleware(['permission:institute.manage'])->prefix('institutes')->group(function () {
        Route::post('/store', [InstituteController::class, 'store']);
        Route::post('/update/{institute}', [InstituteController::class, 'update']);
        Route::delete('/destroy/{institute}', [InstituteController::class, 'destroy']);
    });
    // --- إدارة الأقسام (للموظفين فقط بناءً على الصلاحية في Seeder) ---
    Route::middleware(['permission:department.manage'])->prefix('departments')->group(function () {
        Route::post('/store', [DepartmentController::class, 'store']);
        Route::post('/update/{department}', [DepartmentController::class, 'update']);
        Route::delete('/destroy/{department}', [DepartmentController::class, 'destroy']);
    });

    // إدارة الحجوزات والمالية (للسكرتير)
    Route::middleware(['permission:course.manage'])->prefix('booking')->group(function () {
        // عرض الحجوزات المعلقة للسكرتير
        Route::get('/pending', [BookingController::class, 'pending']);
        // إنهاء الحجز (تأكيد الدفع وتغيير دور الطالب)
        Route::post('/complete', [BookingController::class, 'complete']);
    });

});
