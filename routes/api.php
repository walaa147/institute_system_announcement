<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DiplomaController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\InstituteController;
use App\Http\Controllers\Api\BookingController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/courses', [CourseController::class, 'index']);

// مسارات العرض العام (تحتاج تسجيل دخول فقط لرؤية حالة المفضلة)
Route::prefix('view')->middleware('auth:sanctum')->group(function () {
    Route::get('/courses/{course}', [CourseController::class, 'show']);
    Route::get('/diplomas', [DiplomaController::class, 'index']);
    Route::get('/diplomas/{diploma}', [DiplomaController::class, 'show']);
    Route::get('/institutes', [InstituteController::class, 'index']);
    Route::get('/institutes/{institute}', [InstituteController::class, 'show']);
    Route::get('/departments', [DepartmentController::class, 'index']);
    Route::get('/departments/{department}', [DepartmentController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Shared & Student)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // بيانات المستخدم الشخصية
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', function (Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'تم تسجيل الخروج بنجاح']);
    });

    // العمليات الخاصة بالطلاب والزبائن
    Route::post('/like/toggle', [LikeController::class, 'toggle']);
    Route::post('/booking/bookItem', [BookingController::class, 'bookItem']);

    /*
    |--------------------------------------------------------------------------
    | Secretary & Admin Routes (إدارة النظام)
    |--------------------------------------------------------------------------
    */
    // هنا نطبق الميدلوير المخصص الجديد لحماية كل ما يخص الإدارة
    Route::middleware(['is_secretary'])->group(function () {

        // إدارة الكورسات
        Route::prefix('courses')->group(function () {
            Route::post('/store', [CourseController::class, 'store']);
            Route::post('/update/{course}', [CourseController::class, 'update']);
            Route::delete('/destroy/{course}', [CourseController::class, 'destroy']);
        });

        // إدارة الدبلومات
        Route::prefix('diplomas')->group(function () {
            Route::post('/store', [DiplomaController::class, 'store']);
            Route::post('/update/{diploma}', [DiplomaController::class, 'update']);
            Route::delete('/destroy/{diploma}', [DiplomaController::class, 'destroy']);
        });

        // إدارة المعاهد
        Route::prefix('institutes')->group(function () {
            Route::post('/store', [InstituteController::class, 'store']);
            Route::post('/update/{institute}', [InstituteController::class, 'update']);
            Route::delete('/destroy/{institute}', [InstituteController::class, 'destroy']);
        });

        // إدارة الأقسام
        Route::prefix('departments')->group(function () {
            Route::post('/store', [DepartmentController::class, 'store']);
            Route::post('/update/{department}', [DepartmentController::class, 'update']);
            Route::delete('/destroy/{department}', [DepartmentController::class, 'destroy']);
        });

        // إدارة الحجوزات والمالية
        Route::prefix('booking')->group(function () {
            Route::get('/pending', [BookingController::class, 'pending']);
            Route::post('/complete', [BookingController::class, 'complete']);
        });
    });
});
Route::any('{any}', function () {
   return response()->json([
        'status' => 'false',
        'message' => 'الصفحة التي طلبتها غير موجودة.'], 404);
})->where('any', '.*');
