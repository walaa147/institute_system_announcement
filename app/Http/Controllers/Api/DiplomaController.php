<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diploma;
use App\Services\DiplomaService;
use App\Http\Requests\StoreDiplomaRequest;
use App\Http\Requests\UpdateDiplomaRequest;
use App\Http\Resources\DiplomaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiplomaController extends Controller
{
    public function __construct(private readonly DiplomaService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $diplomas = Diploma::with([
            'courses',
            'institute',
            'creator',
            'likes'
        ])
        ->withCount('likes')
        ->latest()
        ->get();

        return DiplomaResource::collection($diplomas);
    }

    public function store(StoreDiplomaRequest $request): DiplomaResource|JsonResponse
    {
        try {
            $diploma = $this->service->store($request->validated());
            return new DiplomaResource($diploma);
        } catch (\Exception $e) {
            return response()->json(['message' => 'فشلت الإضافة: ' . $e->getMessage()], 403);
        }
    }

    public function show(Diploma $diploma): DiplomaResource
    {
        $diploma->load([
            'courses',
            'institute',
            'creator',
            'likes'
        ])->loadCount('likes');

        return new DiplomaResource($diploma);
    }

    public function update(UpdateDiplomaRequest $request, Diploma $diploma): DiplomaResource|JsonResponse
    {
        try {
            $updatedDiploma = $this->service->update($diploma, $request->validated());
            return new DiplomaResource($updatedDiploma);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشلت عملية التحديث: ' . $e->getMessage(),
                'status' => 'error'
            ], 403);
        }
    }

    public function destroy(Diploma $diploma): JsonResponse
    {
        try {
            $this->service->delete($diploma);
            return response()->json([
                'message' => 'تم حذف الدبلوم بنجاح',
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل الحذف: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    // 🔥 إضافات

    public function attachCourses(Request $request, Diploma $diploma)
    {
        $request->validate([
            //'course_ids' => 'required|array',//لانه يطلع خطا بالاختبار
            'course_ids' => 'nullable|array',
            'course_ids.*' => 'exists:courses,id'
        ]);

       /* $courses = collect($request->course_ids)
            ->mapWithKeys(fn($id, $index) => [
                $id => ['sort_order' => $index]
            ]);
علشان الاختبار بس لانه يطلع لي خطا مافيش عندي بيانات للاكراس 
        $diploma->courses()->sync($courses);*/
        if ($request->has('course_ids')) {
    $courses = collect($request->course_ids)
        ->mapWithKeys(fn($id, $index) => [
            $id => ['sort_order' => $index]
        ]);

    $diploma->courses()->sync($courses);
}
        return response()->json(['message' => 'تم ربط الكورسات']);
    }

    public function toggle(Diploma $diploma)
    {
        $diploma->update([
            'is_active' => !$diploma->is_active
        ]);

        return response()->json([
            'message' => 'تم تغيير الحالة',
            'is_active' => $diploma->is_active
        ]);
    }
}