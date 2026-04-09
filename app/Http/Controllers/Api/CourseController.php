<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseService;
use App\Http\Requests\Api\Secretary\StoreCourseRequest;
use App\Http\Requests\Api\Secretary\UpdateCourseRequest;
use App\Http\Requests\Api\Users\FilterCourseRequest;
use App\Http\Resources\Api\CourseResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    use ApiResponse;

    public function __construct(protected CourseService $service) {}

    /**
     * عرض قائمة الكورسات النشطة مع علاقاتها
     */
    public function index(): JsonResponse
    {

        $courses = Course::with(['department.institute', 'creator', 'likes'])
            ->withCount('likes')
           // ->where('is_active', true) // جلب الكورسات النشطة فقط
            ->latest()
            ->paginate(15);

        return $this->successResponse(
            CourseResource::collection($courses),
            __('validation.custom.course.fetched_success')
        );
    }

    /**
     * تخزين كورس جديد
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
       // Gate::authorize('create', Course::class);

        try {
            $course = $this->service->store($request->validated());

            // تحميل العلاقات للكورس الجديد لكي لا يفشل الـ Resource في قراءتها
            $course->load(['department.institute', 'creator']);

            return $this->successResponse(
                new CourseResource($course),
                __('validation.custom.course.created_success'),
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * عرض تفاصيل كورس محدد
     */
    public function show($id): JsonResponse
    {
        // جلب الكورس مع علاقاته وإحصائياته
        $course = Course::with(['department.institute', 'creator', 'likes'])
            ->withCount('likes')
            ->find($id);

        if (!$course) return $this->errorResponse(__('validation.custom.course.not_found'), 404);

       // Gate::authorize('view', $course);

        return $this->successResponse(new CourseResource($course));
    }

    /**
     * تحديث كورس
     */
    public function update(UpdateCourseRequest $request, $id): JsonResponse
    {
        $course = Course::find($id);

        if (!$course) return $this->errorResponse(__('validation.custom.course.not_found'), 404);

        //Gate::authorize('update', $course);

        try {
            $updatedCourse = $this->service->update($course, $request->validated());

            // تحميل العلاقات لضمان عرضها بشكل صحيح في الـ Resource بعد التحديث
            $updatedCourse->load(['department.institute', 'creator']);

            return $this->successResponse(
                new CourseResource($updatedCourse),
                __('validation.custom.course.updated_success')
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * حذف كورس نهائياً (أو Soft Delete حسب إعدادات المايجريشن)
     */
    public function destroy($id): JsonResponse
    {
        $course = Course::find($id);

        if (!$course) return $this->errorResponse(__('validation.custom.course.not_found'), 404);

        //Gate::authorize('delete', $course);

        try {
            $this->service->delete($course);
            return $this->successResponse(null, __('validation.custom.course.deleted_success'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * تفعيل / إيقاف الكورس
     */
    public function toggleStatus($id): JsonResponse
    {
        $course = Course::find($id);

        if (!$course) return $this->errorResponse(__('validation.custom.course.not_found'), 404);

       // Gate::authorize('update', $course);

        try {
            $updated = $this->service->toggleStatus($course);
            $message = $updated->is_active
                ? __('validation.custom.course.enabled_course')
                : __('validation.custom.course.disabled_course');

            return $this->successResponse(new CourseResource($updated), $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }



    /**
 * عرض قائمة الكورسات للزوار مع دعم البحث والفلترة
 */
public function publicIndex(FilterCourseRequest $request): JsonResponse
{
    // استدعاء الخدمة مع البيانات المفلترة
    $courses = $this->service->getPublicCourses($request->validated());

    return $this->successResponse(
        CourseResource::collection($courses),
        __('validation.custom.course.fetched_success')
    );
}

/**
 * إبداء الإعجاب أو إلغاؤه (Toggle Like)
 */
public function toggleLike($id): JsonResponse
{
    // البحث عن الكورس يدوياً لتوحيد رسائل الخطأ
    $course = Course::find($id);
    if (!$course) {
        return $this->errorResponse(__('validation.custom.course.not_found'), 404);
    }

    try {
        // تمرير الكورس والمستخدم الحالي للخدمة
        $result = $this->service->toggleLike($course, Auth::user());

        $message = $result['is_liked']
            ? __('validation.custom.course.liked_success')
            : __('validation.custom.course.unliked_success');

        return $this->successResponse($result, $message);
    } catch (\Exception $e) {
        return $this->errorResponse($e->getMessage(), 500);
    }
}
}
