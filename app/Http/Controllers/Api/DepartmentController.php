<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest; // أضف هذا السطر
use App\Http\Resources\DepartmentResource;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    /**
     * عرض جميع الأقسام مع بيانات المعهد المرتبط
     */
    public function index(): JsonResponse
    {
        $departments = Department::with('institute')->get();
        return response()->json([
            'status' => 'success',
            'data'   => DepartmentResource::collection($departments)
        ]);
    }

    /**
     * إنشاء قسم جديد
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        // التحقق من القيد الفريد (Unique) قبل الإنشاء
        if (Department::where('name_ar', $request->name_ar)
            ->where('institute_id', $request->institute_id)
            ->exists()) {
            return response()->json(['message' => 'القسم موجود مسبقاً في هذا المعهد'], 422);
        }

        $department = Department::create($request->validated());

        return response()->json([
            'message' => 'تم إنشاء القسم بنجاح',
            'data'    => new DepartmentResource($department)
        ], 201);
    }

    /**
     * عرض تفاصيل قسم معين
     */
    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data'   => new DepartmentResource($department->load('institute'))
        ]);
    }

    /**
     * تحديث بيانات قسم موجود
     */
    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        // التحقق من القيد الفريد مع استثناء القسم الحالي (Unique except current ID)
        if ($request->has('name_ar') || $request->has('institute_id')) {
            $name = $request->name_ar ?? $department->name_ar;
            $instId = $request->institute_id ?? $department->institute_id;

            $exists = Department::where('name_ar', $name)
                ->where('institute_id', $instId)
                ->where('id', '!=', $department->id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'الاسم الجديد موجود مسبقاً في هذا المعهد.'], 422);
            }
        }

        $department->update($request->validated());

        return response()->json([
            'message' => 'تم تحديث القسم بنجاح',
            'data'    => new DepartmentResource($department->load('institute'))
        ]);
    }

    /**
     * حذف قسم
     */
    public function destroy(Department $department): JsonResponse
    {
        $department->delete();
        return response()->json([
            'message' => 'تم حذف القسم بنجاح'
        ]);
    }
}
