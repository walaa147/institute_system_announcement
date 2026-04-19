<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Services\InstituteService;
use App\Http\Requests\Api\Admin\StoreInstituteRequest;
use App\Http\Requests\Api\Admin\UpdateInstituteRequest;
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
        /** @var \App\Models\User $user */
        $user = auth('sanctum')->user();
        $userLat = $request->lat ?? $request->user_lat;
    $userLng = $request->lng ?? $request->user_lng;
       $isSuperAdmin = $user && $user->hasRole('super_admin');
    $isSecretary = $user && $user->isStatusAdmin();

    $institutes = Institute::query()
        ->when(!$isSuperAdmin, function ($query) use ($user, $isSecretary) {
            return $query->where(function ($q) use ($user, $isSecretary) {
                $q->active(); // المعاهد النشطة للجميع

                // إذا كان سكرتير، أظهر معهده أيضاً حتى لو غير نشط
                if ($isSecretary) {
                    $q->orWhere('id', $user->institute_id);
                }
            });
        }) // استخدام Scope المعاهد النشطة فقط
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

    public function show($id, Request $request): InstituteResource|JsonResponse
    {
        $institute = Institute::with(['departments', 'courses', 'advertisements'])->find($id);
        if (!$institute) {
            return $this->errorResponse(__('validation.custom.institute.not_found'), 404);
        }
        // التحقق من حالة المعهد قبل العرض
        /** @var \App\Models\User $user */
        $user = auth('sanctum')->user();

    // 1. هل هو سوبر أدمن؟
    $isSuperAdmin = $user && $user->hasRole('super_admin');

    // 2. هل هو سكرتير هذا المعهد تحديداً؟
    $isStaffOfThisInstitute = $user && $user->isStatusAdmin() && $user->institute_id == $institute->id;

    // المنطق الجديد: إذا كان المعهد معطلاً، اسمح فقط للسوبر أدمن أو سكرتير المعهد بالدخول
    if (!$institute->status && !$isSuperAdmin && !$isStaffOfThisInstitute) {
        return $this->errorResponse(__('validation.custom.institute.institute_disabled'), 403);
    }
        $institute->loadCount(['departments', 'courses', 'advertisements']);
        return new InstituteResource($institute);
    }


    public function update(UpdateInstituteRequest $request, $id): InstituteResource|JsonResponse
    {
        $institute = Institute::find($id);
        if (!$institute) {
            return $this->errorResponse(__('validation.custom.institute.not_found'), 404);
        }
        try {
            $updated = $this->service->update($institute, $request->validated());
            return $this->successResponse(new InstituteResource($updated), __('validation.custom.institute.updated_success'));
        } catch (\Exception $e) {
            return $this->errorResponse(__('validation.custom.institute.update_failed') , 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $institute = Institute::find($id);
        if (!$institute) {
            return $this->errorResponse(__('validation.custom.institute.not_found'), 404);
        }
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
public function toggleStatus($id): JsonResponse
{
    $institute = Institute::find($id);
    if (!$institute) {
        return $this->errorResponse(__('validation.custom.institute.not_found'), 404);
    }

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
