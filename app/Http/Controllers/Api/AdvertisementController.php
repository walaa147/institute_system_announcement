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
    /** @var \App\Models\User|null $user */
    $user = auth('sanctum')->user();

    $query = Advertisement::with([
        'advertisable', 'institute', 'creator',
        'department' => function ($q) {
            $q->withoutGlobalScopes();
        }
    ]);

    // إذا كان المستخدم ليس سوبر أدمن
    if (!$user || !$user->hasRole('super_admin')) {
        $query->where(function ($q) use ($user) {
            // أولاً: اعرض له كل إعلانات معهده (سواء القسم نشط أو لا)
            if ($user && $user->institute_id) {
                $q->where('institute_id', $user->institute_id);
            }

            // ثانياً: اعرض له إعلانات المعاهد الأخرى بشرط أن يكون القسم نشطاً
            $q->orWhere(function ($sub) use ($user) {
                $sub->where('is_active', true)
                    ->whereHas('institute', fn($inst) => $inst->where('status', true))
                    ->where(function ($depQ) {
                        $depQ->whereNull('department_id')
                             ->orWhereHas('department', fn($d) => $d->where('is_active', true));
                    });

                // إذا كان سكرتير، لا نريد تكرار إعلانات معهده هنا
                if ($user && $user->institute_id) {
                    $sub->where('institute_id', '!=', $user->institute_id);
                }
            });
        });
    }

    $ads = $query->latest()->paginate(15);
    return $this->successResponse(ApiAdvertisementResource::collection($ads), __('validation.custom.advertisement.fetched_success'));
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
    $ad = Advertisement::withoutGlobalScopes()->with([
        'advertisable', 'institute', 'creator',
        'department' => function ($q) {
            $q->withoutGlobalScopes();
        }
    ])->find($id);

    if (!$ad) {
        return $this->errorResponse(__('validation.custom.advertisement.not_found'), 404);
    }

    /** @var \App\Models\User|null $user */
    $user = auth('sanctum')->user();
    $isSuperAdmin = $user && $user->hasRole('super_admin');
    $isOwner = $user && $user->institute_id === $ad->institute_id; // هل هو سكرتير نفس المعهد؟

    // 1. التحقق من حالة المعهد
    if (!$ad->institute?->status) {
        if (!$isSuperAdmin && !$isOwner) {
            return $this->errorResponse(__('validation.custom.institute.institute_disabled'), 403);
        }
    }

    // 2. التحقق من حالة القسم (التعديل الجوهري هنا)
    if ($ad->department_id && !$ad->department?->is_active) {
        // إذا لم يكن سوبر أدمن ولم يكن صاحب المعهد (حتى لو كان سكرتير معهد آخر) -> امنعه
        if (!$isSuperAdmin && !$isOwner) {
           return $this->errorResponse(__('validation.custom.department.department_disabled'), 403);
        }
    }

    // 3. التحقق من حالة نشاط الإعلان نفسه
    if (!$ad->is_active) {
        // سكرتير معهد آخر لا يرى الإعلانات غير النشطة لمعاهد غيره
        if (!$isSuperAdmin && !$isOwner) {
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
