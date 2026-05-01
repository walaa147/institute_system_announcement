<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentService;
use App\Http\Requests\Api\Secretary\StoreDepartmentRequest;
use App\Http\Requests\Api\Secretary\UpdateDepartmentRequest;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Gate;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    use ApiResponse;

    public function __construct(protected DepartmentService $service) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $isStaff = ($user instanceof \App\Models\User) && $user->isStatusAdmin();
$query = $isStaff ? Department::withoutGlobalScope('active_access') : Department::query();
        $departments = $query->withCount(['courses', 'diplomas'])
            ->latest()
            ->paginate(15);

        return $this->successResponse(
            DepartmentResource::collection($departments)->response()->getData(true),
            __('validation.custom.department.fetched_success')
        );
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
       Gate::authorize('create', Department::class);
        try {
            $department = $this->service->store($request->validated());
            return $this->successResponse(
                new DepartmentResource($department),
                __('validation.custom.department.created_success'),
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function show($id): JsonResponse
    {
        $department = Department::withoutGlobalScope('active_access')->withCount(['courses', 'diplomas', 'advertisements'])->find($id);

        if (!$department) {
            return $this->errorResponse(__('validation.custom.department.not_found'), 404);
        }


        $response = Gate::inspect('view', $department);

    if ($response->denied()) {
        return $this->errorResponse($response->message(), 403);
    }

        return $this->successResponse(new DepartmentResource($department));
    }

    public function update(UpdateDepartmentRequest $request, $id): JsonResponse
    {
        $department = Department::withoutGlobalScope('active_access')->find($id);
        if (!$department) return $this->errorResponse(__('validation.custom.department.not_found'), 404);

        Gate::authorize('update', $department);


        try {
            $updated = $this->service->update($department, $request->validated());
            return $this->successResponse(new DepartmentResource($updated), __('validation.custom.department.updated_success'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function toggleStatus($id): JsonResponse
    {

        $department = Department::withoutGlobalScope('active_access')->find($id);
        if (!$department) return $this->errorResponse(__('validation.custom.department.not_found'), 404);
            Gate::authorize('update', $department);
        try {
            $updated = $this->service->toggleStatus($department);
            $message = $updated->is_active
                ? __('validation.custom.department.enabled_department')
                : __('validation.custom.department.disabled_department');

            return $this->successResponse(new DepartmentResource($updated), $message);
        } catch (\Exception $e) {
            return $this->errorResponse(__('validation.custom.department.status_update_failed'), 500);
        }
    }

    public function destroy($id): JsonResponse
    {

        $department = Department::withoutGlobalScope('active_access')->find($id);
        if (!$department) return $this->errorResponse(__('validation.custom.department.not_found'), 404);

        Gate::authorize('delete', $department);


        try {
            $this->service->delete($department);
            return $this->successResponse(null, __('validation.custom.department.deleted_success'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
