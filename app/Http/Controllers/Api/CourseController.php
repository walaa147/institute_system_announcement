<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\CourseService;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    /**
     * حقن خدمة الكورسات في المتحكم
     */
    public function __construct(private readonly CourseService $service) {}

    /**
     * عرض قائمة الكورسات النشطة
     * * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
{
    $courses = Course::with(['department.institute', 'creator.user', 'likes']) // <=== إضافة favorites هنا
        ->withCount('likes')
        ->where('is_active', true)
        ->latest()
        ->get();

    return CourseResource::collection($courses);
}

    /**
     * تخزين كورس جديد
     * * @param StoreCourseRequest $request
     * @return CourseResource|JsonResponse
     */
    public function store(StoreCourseRequest $request): CourseResource|JsonResponse
    {
        try {
            $course = $this->service->store($request->validated());
            return new CourseResource($course);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشلت عملية الإضافة: ' . $e->getMessage(),
                'status' => 'error'
            ], 403);
        }
    }

    /**
     * عرض تفاصيل كورس معين مع علاقاته
     * * @param Course $course
     * @return CourseResource
     */
    // في app/Http/Controllers/Api/CourseController.php

public function show(Course $course): CourseResource
{
    $course->load([
        'department.institute',
        'creator.user',
        'likes'
    ]);
    $course->loadCount('likes');

    return new CourseResource($course);
}


    /**
     * تحديث بيانات كورس موجود
     * * @param UpdateCourseRequest $request
     * @param Course $course
     * @return CourseResource|JsonResponse
     */
    public function update(UpdateCourseRequest $request, Course $course): CourseResource|JsonResponse
    {
        try {
            $updatedCourse = $this->service->update($course, $request->validated());
            return new CourseResource($updatedCourse);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشلت عملية التحديث: ' . $e->getMessage(),
                'status' => 'error'
            ], 403);
        }
    }

    /**
     * حذف كورس نهائياً مع صورته
     * * @param Course $course
     * @return JsonResponse
     */
    public function destroy(Course $course): JsonResponse
    {
        try {
            $this->service->delete($course);
            return response()->json([
                'message' => 'تم حذف الدورة بنجاح مع الملفات المرتبطة بها',
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشلت عملية الحذف',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
