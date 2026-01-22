<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diploma;
use App\Services\DiplomaService;
use App\Http\Requests\StoreDiplomaRequest;
use App\Http\Requests\UpdateDiplomaRequest; // تأكد من استدعاء طلب التحديث
use App\Http\Resources\DiplomaResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiplomaController extends Controller
{
    /**
     * حقن خدمة الدبلومات
     */
    public function __construct(private readonly DiplomaService $service) {}

    /**
     * عرض قائمة الدبلومات
     */
    public function index(): AnonymousResourceCollection
{
    $diplomas = Diploma::with([
        'courses',
        'institute',
        'creator',
        'likes' // <=== إضافة العلاقة الكاملة لـ is_liked
    ])
    ->withCount('likes') // <=== إضافة العداد لـ likes_count
    ->latest()
    ->get();

    return DiplomaResource::collection($diplomas);
}

    /**
     * تخزين دبلوم جديد مع ربط الكورسات
     */
    public function store(StoreDiplomaRequest $request): DiplomaResource|JsonResponse
    {
        try {
            $diploma = $this->service->store($request->validated());
            return new DiplomaResource($diploma);
        } catch (\Exception $e) {
            return response()->json(['message' => 'فشلت الإضافة: ' . $e->getMessage()], 403);
        }
    }

    /**
     * عرض تفاصيل دبلوم محدد
     */
    public function show(Diploma $diploma): DiplomaResource
{
    // دمج التحميل لضمان التحميل المسبق للعداد والعلاقة الكاملة
    $diploma->load([
        'courses',
        'institute',
        'creator',
        'likes' // <=== العلاقة الكاملة لـ is_favorited
    ])
    ->loadCount('likes'); // <=== العداد لـ favorite_count

    return new DiplomaResource($diploma);
}

    /**
     * تحديث بيانات الدبلوم والكورسات المرتبطة به
     */
    public function update(UpdateDiplomaRequest $request, Diploma $diploma): DiplomaResource|JsonResponse
    {
        try {
            // استدعاء الخدمة لتحديث البيانات ومزامنة الكورسات (sync)
            $updatedDiploma = $this->service->update($diploma, $request->validated());
            return new DiplomaResource($updatedDiploma);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشلت عملية التحديث: ' . $e->getMessage(),
                'status' => 'error'
            ], 403);
        }
    }

    /**
     * حذف الدبلوم نهائياً
     */
    public function destroy(Diploma $diploma): JsonResponse
    {
        try {
            $this->service->delete($diploma);
            return response()->json([
                'message' => 'تم حذف الدبلوم بنجاح مع كافة الارتباطات',
                'status' => 'success'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'فشل الحذف: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}
