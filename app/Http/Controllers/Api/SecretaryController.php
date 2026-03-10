<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SecretaryService;
use App\Http\Requests\Api\Admin\StoreSecretaryRequest;
use App\Http\Requests\Api\Admin\UpdateSecretaryRequest;
use App\Http\Resources\SecretaryResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SecretaryController extends Controller
{
    use ApiResponse;

    public function __construct(protected SecretaryService $service) {}

    public function index(): AnonymousResourceCollection
    {
        // جلب المستخدمين الذين لديهم دور سكرتير فقط مع المعهد التابعين له
        $secretaries = User::role('secretary')
            ->with('institute')
            ->latest()
            ->paginate(15);

        return SecretaryResource::collection($secretaries)
            ->additional(['message' => __('validation.custom.secretary.fetched_success')]);
    }

    public function store(StoreSecretaryRequest $request): JsonResponse
    {
        try {
            $secretary = $this->service->store($request->validated());
            return $this->successResponse(
                new SecretaryResource($secretary),__('validation.custom.secretary.created_success')
            ,
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), null, 400);
        }
    }

    public function show($id): SecretaryResource|JsonResponse
    {
        $secretary = User::role('secretary')->with('institute')->find($id);
        if (!$secretary) {
            return $this->errorResponse(__('validation.custom.secretary.not_found'), null, 404);
        }
        return new SecretaryResource($secretary);
    }

    public function update(UpdateSecretaryRequest $request, $id): JsonResponse
    {
        $secretary = User::role('secretary')->find($id);
        if (!$secretary) {
            return $this->errorResponse(__('validation.custom.secretary.not_found'), null, 404);
        }

        try {
            $updated = $this->service->update($secretary, $request->validated());
            return $this->successResponse(new SecretaryResource($updated), __('validation.custom.secretary.updated_success'));
        } catch (\Exception $e) {
            return $this->errorResponse(__('validation.custom.secretary.update_failed'), null, 500);
        }
    }
    function toggleStatus($id): JsonResponse
    {
        $secretary = User::role('secretary')->find($id);
        if (!$secretary) {
            return $this->errorResponse(__('validation.custom.secretary.not_found'), null, 404);
        }

        try {
            $updated = $this->service->toggleStatus($secretary);
            $message = $updated->is_active
                ? __('validation.custom.secretary.activated_success')
                : __('validation.custom.secretary.deactivated_success');
            return $this->successResponse(new SecretaryResource($updated), $message);
        } catch (\Exception $e) {
            return $this->errorResponse(__('validation.custom.secretary.status_toggle_failed'), null, 500);
        }
    }


    public function destroy($id): JsonResponse
    {
        $secretary = User::role('secretary')->find($id);
        if (!$secretary) {
            return $this->errorResponse(__('validation.custom.secretary.not_found'), null, 404);
        }

        try {
            $this->service->delete($secretary);
            return $this->successResponse(null, __('validation.custom.secretary.deleted_success'));
        } catch (\Exception $e) {
            return $this->errorResponse(__('validation.custom.secretary.delete_failed'), null, 500);
        }
    }
}
