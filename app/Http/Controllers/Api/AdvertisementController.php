<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Services\AdvertisementService;
use App\Http\Requests\Api\Secretary\StoreAdvertisementRequest;
use App\Http\Requests\Api\Secretary\UpdateAdvertisementRequest;
use App\Http\Resources\Api\AdvertisementResource as ApiAdvertisementResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Resources\Api\AdvertisementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdvertisementController extends Controller
{
    use ApiResponse;
    protected array $relations = ['advertisable', 'institute', 'department', 'creator'];

    public function __construct(protected AdvertisementService $service) {}

    /**
     * عرض قائمة الإعلانات (تخضع للـ Global Scope تلقائياً)
     */
    public function index(): JsonResponse
    {
        $ads = Advertisement::with($this->relations)->latest()->paginate(15);
        return $this->successResponse(ApiAdvertisementResource::collection($ads),__('validation.custom.advertisement.fetched_success'));
    }

    /**
     * تخزين إعلان جديد
     */
    public function store(StoreAdvertisementRequest $request): JsonResponse
    {
        Gate::authorize('create', Advertisement::class);

        try {
            $ad = $this->service->store($request->validated());
            $ad->load($this->relations);
            return $this->successResponse(new ApiAdvertisementResource($ad), __('validation.custom.advertisement.created_success'), 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * عرض تفاصيل إعلان محدد
     */
    public function show($id): JsonResponse
{
    // جلب الإعلان بدون القيود الزمنية أو قيود الحالة
    $ad = Advertisement::withoutGlobalScopes()->with($this->relations)->find($id);

    if (!$ad) {
        return $this->errorResponse(__('validation.custom.advertisement.not_found'), 404);
    }

    // يدويًا: إذا لم يكن هناك مستخدم (زائر) والإعلان غير مفعل -> ارفض
   /** @var \App\Models\User|null $user */
    $user = auth('sanctum')->user();
$isSuperAdmin = $user && $user->hasRole('super_admin');
    $isOwner = $user && $user->institute_id === $ad->institute_id;

    // --- المنطق الجديد لفحص حالة المعهد ---

    // إذا كان المعهد معطلاً
    if (!$ad->institute?->status) {
        // لا يراه إلا السوبر آدمن أو سكرتير نفس المعهد
        if (!$isSuperAdmin && !$isOwner) {
            return $this->errorResponse(__('validation.custom.institute.institute_disabled'), 403);
        }
    }

    if (!$ad->is_active) {
        if (!$isSuperAdmin && !$isOwner) {
             return $this->errorResponse('Unauthorized', 403);
        }

        // إذا كان موجود، نختبر الصلاحية عبر الـ Policy
        if (Gate::forUser($user)->denies('view', $ad)) {
             return $this->errorResponse('Unauthorized', 403);
        }
    }

    return $this->successResponse(new ApiAdvertisementResource($ad), __('validation.custom.advertisement.fetched_success'));
}
    /**
     * تحديث إعلان
     */
    public function update(UpdateAdvertisementRequest $request, $id): JsonResponse
    {
        $ad = Advertisement::find($id);
        if (!$ad) return $this->errorResponse(__('validation.custom.advertisement.not_found'), 404);

        Gate::authorize('update', $ad);

        try {
            $updated = $this->service->update($ad, $request->validated());
            $updated->load($this->relations);
            return $this->successResponse(new ApiAdvertisementResource($updated), __('validation.custom.advertisement.updated_success'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * حذف إعلان
     */
    public function destroy($id): JsonResponse
    {
        $ad = Advertisement::find($id);
        if (!$ad) return $this->errorResponse(__('validation.custom.advertisement.not_found'), 404);

        Gate::authorize('delete', $ad);

        try {
            $this->service->delete($ad);
            return $this->successResponse(null, __('validation.custom.advertisement.deleted_success'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
    public function toggleStatus($id): JsonResponse
    {
        $ad = Advertisement::find($id);
        if (!$ad) return $this->errorResponse(__('validation.custom.advertisement.not_found'), 404);

        Gate::authorize('update', $ad);

        try {
            $updated = $this->service->toggleStatus($ad);
            $message = $updated->is_active
            ? __('validation.custom.advertisement.enabled_advertisement')
            : __('validation.custom.advertisement.disabled_advertisement');
            return $this->successResponse($updated, $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

}
