<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Services\InstituteService;
use App\Http\Requests\StoreInstituteRequest;
use App\Http\Requests\UpdateInstituteRequest;
use App\Traits\ApiResponse;
use App\Http\Resources\InstituteResource;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class InstituteController extends Controller
{
    use ApiResponse;
    public function __construct(protected InstituteService $service) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        // استلام إحداثيات المستخدم (إذا توفرت) لحساب المسافة برمجياً
        $userLat = $request->lat;
        $userLng = $request->lng;

        $institutes = Institute::query()
            ->active() // استخدام Scope المعاهد النشطة فقط
            ->withDistance($userLat, $userLng) // حساب المسافة إذا أرسل المستخدم موقعه
            ->orderBySmartPriority() // الترتيب الحاكم (الأولوية الذكية)
            ->withCount(['departments', 'courses']) // جلب عدد الأقسام والكورسات لسرعة العرض
            ->paginate(15);


        return InstituteResource::collection($institutes)
            ->additional(['message' => __('validation.custom.institute.fetched_success')]);
    }

    public function store(StoreInstituteRequest $request): InstituteResource|JsonResponse
     {
    //     $user = auth()->user();
    //     if(!auth()->user()->can('create_institute')) {
    //         return $this->errorResponse(__('validation.custom.institute.unauthorized'), 403);
    //     }
        try {
            $institute = $this->service->store($request->validated());
            return $this->successResponse(new InstituteResource($institute), __('validation.custom.institute.created_success'), 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
           // return $this->errorResponse(__('validation.custom.institute.create_failed'), 403);
        }
    }

    public function show(Institute $institute): InstituteResource|JsonResponse
    {
        // منع الوصول للمعهد إذا كان معطلاً (status: 0) [cite: 14, 196]
        if (!$institute->status) {
            return $this->errorResponse(__('validation.custom.institute.disabled'), 404);
        }

        $institute->loadCount(['departments', 'courses', 'advertisements']);
        return new InstituteResource($institute);
    }


    public function update(UpdateInstituteRequest $request, Institute $institute): InstituteResource|JsonResponse
    {
        try {
            $updated = $this->service->update($institute, $request->validated());
            return $this->successResponse(new InstituteResource($updated), __('validation.custom.institute.updated_success'));
        } catch (\Exception $e) {
            return $this->errorResponse(__('validation.custom.institute.update_failed') , 500);
        }
    }

    public function destroy(Institute $institute): JsonResponse
    {
        try {
            $this->service->delete($institute);
            return $this->successResponse(null, __('validation.custom.institute.deleted_success'));
        } catch (\Exception $e) {
            return $this->errorResponse(__('validation.custom.institute.delete_failed'), 500);
        }
    }
    /**
 * تفعيل أو تعطيل المعهد
 */
public function toggleStatus(Institute $institute): JsonResponse
{
    try {
        $updated = $this->service->toggleStatus($institute);

        $message = $updated->status
            ? __('validation.custom.institute.enabled_institute')
            : __('validation.custom.institute.disabled_institute');

        return $this->successResponse(new InstituteResource($updated), $message);
    } catch (\Exception $e) {
        return $this->errorResponse(__('validation.custom.institute.status_update_failed'), 500);
    }
}
}
