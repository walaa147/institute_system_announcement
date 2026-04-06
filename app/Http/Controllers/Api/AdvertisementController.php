<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Services\AdvertisementService;
use App\Http\Requests\Api\Secretary\StoreAdvertisementRequest;
use App\Http\Requests\Api\Secretary\UpdateAdvertisementRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AdvertisementController extends Controller
{
    use ApiResponse;

    public function __construct(protected AdvertisementService $service) {}

    /**
     * عرض قائمة الإعلانات (تخضع للـ Global Scope تلقائياً)
     */
    public function index(): JsonResponse
    {
        $ads = Advertisement::with(['advertisable'])->latest()->paginate(15);
        return $this->successResponse($ads,__('validation.custom.advertisement.fetched_success'));
    }

    /**
     * تخزين إعلان جديد
     */
    public function store(StoreAdvertisementRequest $request): JsonResponse
    {
        Gate::authorize('create', Advertisement::class);

        try {
            $ad = $this->service->store($request->validated());
            return $this->successResponse($ad, __('validation.custom.advertisement.created_success'), 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * عرض تفاصيل إعلان محدد
     */
    public function show($id): JsonResponse
    {
        $ad = Advertisement::find($id);
        if (!$ad) return $this->errorResponse(__('validation.custom.advertisement.not_found'), 404);

        Gate::authorize('view', $ad);

        return $this->successResponse($ad);
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
            return $this->successResponse($updated, __('validation.custom.advertisement.updated_success'));
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
