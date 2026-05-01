<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SecretaryController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\AdvertisementController;
use App\Http\Controllers\Api\DiplomaController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\InstituteController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\WaitingListController;
use App\Http\Controllers\Api\FavoriteInstituteController;

use Termwind\Components\Raw;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
//Route::post('/register', [AuthController::class, 'register']);
//Route::post('/login', [AuthController::class, 'login']);
//Route::get('/courses', [CourseController::class, 'index']);

// مسارات العرض العام (تحتاج تسجيل دخول فقط لرؤية حالة المفضلة)
/*::prefix('view')->middleware('auth:sanctum')->group(function () {
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
*/

Route::prefix('v1')->group(function () {

    // عرض بدون تسجيل
    Route::get('diplomas', [DiplomaController::class, 'index']);
    Route::get('diplomas/{diploma}', [DiplomaController::class, 'show']);

    // يحتاج تسجيل
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('diplomas', [DiplomaController::class, 'store']);
        Route::put('diplomas/{diploma}', [DiplomaController::class, 'update']);
        Route::delete('diplomas/{diploma}', [DiplomaController::class, 'destroy']);

        Route::post('diplomas/{diploma}/courses', [DiplomaController::class, 'attachCourses']);
        Route::post('diplomas/{diploma}/toggle', [DiplomaController::class, 'toggle']);

    });

});




// v1 API Routes (للتطوير المستقبلي والتحديثات الكبيرة)

Route::prefix('v1')->group(function () {
// مسارات العرض العام (تحتاج تسجيل دخول فقط )
    // مسارات المصادقة Auth
    Route::prefix('auth')->controller(AuthController::class)->group(function () {

        // المسارات العامة (للزوار والطلاب الجدد)
        Route::post('register', 'register');
        Route::post('login', 'login');});
        // مسار لمحاكاة نجاح الدفع (للتجربة والعرض فقط)

       Route::prefix('view')->group(function () {
//  institute
Route::prefix('view')->middleware('auth_optional')->group(function () {
    Route::get('/institutes', [InstituteController::class, 'index']);
    Route::get('/institutes/{institute}', [InstituteController::class, 'show']);

    // advertisements
     Route::get('/advertisements', [AdvertisementController::class, 'index']);
    Route::get('/advertisements/{advertisement}', [AdvertisementController::class, 'show']);

// department
            Route::get('/departments', [DepartmentController::class, 'index']);
            Route::get('/departments/{department}', [DepartmentController::class, 'show']);

});
 // Courses
            Route::get('/courses', [CourseController::class, 'publicIndex']);
            Route::get('/courses/{course}', [CourseController::class, 'show']);
        });
        // المسارات المحمية (لا يمكن الدخول لها إلا بتوكن صالح)
        Route::middleware(['auth:sanctum', 'check.active'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::post('courses/toggle-like/{id}', [CourseController::class, 'toggleLike']);

           Route::prefix('bookings')->controller(BookingController::class)->group(function () {
            Route::get('show', 'index');          // الطالب يرى حجوزاته / السكرتير يرى حجوزات معهده

            Route::get('show/{booking}', 'show');  // عرض تفاصيل حجز معين
        });
        Route::prefix('waiting-list')->controller(WaitingListController::class)->group(function () {
            Route::get('my-list', 'myWaitlist');
             Route::post('join', 'join');
                Route::get('show/{waitingList}', 'show'); //
        });
              // بيانات المستخدم الشخصية
   Route::get('/user', function (Request $request) {
         return $request->user();
    });

            Route::prefix('bookings')->controller(BookingController::class)->group(function () {
            Route::post('create', 'store');         // إنشاء حجز جديد (للطالب)

            Route::post('{booking}/cancel', 'cancel');
            Route::post('{booking}/simulate-payment',  'simulatePayment');

        });




Route::prefix('profile')->controller(ProfileController::class   )->group(function () {
  Route::get('/show', 'show');
Route::post('/update', 'update');
Route::post('/update-fcm-token', 'updateFcmToken');
Route::delete('/destroy-account', 'destroyAccount')->middleware('role:student');
    });
    // --- مسارات مدير النظام فقط (Super Admin) ---
    Route::middleware(['is_admin'])->group(function () { // سننشئ هذا الميدلوير أو نستخدم الفحص المباشر
        Route::prefix('institutes')->controller(InstituteController::class)->group(function () {
            Route::post('store', 'store');

            Route::delete('destroy/{institute}', 'destroy');
            Route::post('toggle-status/{institute}', 'toggleStatus');
            Route::post('purchase-points/{institute}', 'purchasePoints');
            Route::post('update-commission-rate/{institute}', 'updateCommissionRate');
        });
        Route::prefix('secretaries')->controller(SecretaryController::class)->group(function () {
            Route::get('show', 'index');
            Route::post('store', 'store');
            Route::get('show/{id}', 'show');
            Route::post('update/{id}', 'update');
            Route::delete('destroy/{id}', 'destroy');
            Route::post('toggle-status/{id}', 'toggleStatus');

 });
    });
      Route::middleware(['is_secretary'])->group(function () { // سكرتير أو أدمن
        Route::prefix('departments')->controller(DepartmentController::class)->group(function () {
            Route::post('store', 'store');
            Route::post('update/{department}', 'update');
            Route::delete('destroy/{department}', 'destroy');
             Route::post('toggle-status/{department}', 'toggleStatus');
        });
        Route::prefix('advertisements')->controller(AdvertisementController::class)->group(function () {
            Route::post('store', 'store');
            Route::post('update/{advertisement}', 'update');
            Route::delete('destroy/{advertisement}', 'destroy');
            Route::post('toggle-status/{advertisement}', 'toggleStatus');
        });
        Route::post('update/{institute}', [InstituteController::class, 'update']);
Route::prefix('bookings')->controller(BookingController::class)->group(function () {
                Route::post('{booking}/status', 'updateStatus'); // تأكيد، إلغاء، أو تسجيل حضور
            });

        Route::prefix('courses')->controller(CourseController::class)->group(function () {
            Route::post('store', 'store');
            Route::post('update/{course}', 'update');
            Route::delete('destroy/{course}', 'destroy');
            Route::post('toggle-status/{course}', 'toggleStatus');
             Route::post('index', 'index');
        });
        Route::prefix('waiting-list')->controller(WaitingListController::class)->group(function () {
            Route::get('index', 'index'); // عرض قائمة الانتظار لإعلان معين
        });
      });

        //  مسارات الطلاب فقط Student
        Route::middleware(['is_student'])->group(function () {

            // إدارة المفضلة (المعاهد)
            Route::prefix('student/favorites/institutes')->controller(FavoriteInstituteController::class)->group(function () {
                Route::get('show', 'index');               // عرض المعاهد المفضلة للطالب
                Route::post('toggle/{institute}', 'toggle'); // إضافة/إزالة معهد من المفضلة


            });
        });
        });


       Route::any('{any}', function () {
   return response()->json([
        'status' => 'false',
        'message' => 'الصفحة التي طلبتها غير موجودة.'], 404);
})->where('any', '.*');
    });
