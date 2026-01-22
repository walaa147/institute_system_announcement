<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Services\InstituteService;
use App\Http\Requests\StoreInstituteRequest;
use App\Http\Requests\UpdateInstituteRequest;
use App\Http\Resources\InstituteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InstituteController extends Controller
{
    public function __construct(private readonly InstituteService $service) {}

    public function index(): AnonymousResourceCollection
    {
        $institutes = Institute::withCount(['departments', 'employees'])
            ->latest()
            ->get();

        return InstituteResource::collection($institutes);
    }

    public function store(StoreInstituteRequest $request): InstituteResource|JsonResponse
    {
        try {
            $institute = $this->service->store($request->validated());
            return new InstituteResource($institute);
        } catch (\Exception $e) {
            return response()->json(['message' => 'فشلت عملية الإضافة: ' . $e->getMessage()], 403);
        }
    }

    public function show(Institute $institute): InstituteResource
    {
        $institute->loadCount(['departments', 'employees']);
        return new InstituteResource($institute);
    }

    public function update(UpdateInstituteRequest $request, Institute $institute): InstituteResource|JsonResponse
    {
        try {
            $updated = $this->service->update($institute, $request->validated());
            return new InstituteResource($updated);
        } catch (\Exception $e) {
            return response()->json(['message' => 'فشلت عملية التحديث: ' . $e->getMessage()], 403);
        }
    }

    public function destroy(Institute $institute): JsonResponse
    {
        try {
            $this->service->delete($institute);
            return response()->json(['message' => 'تم حذف المعهد بنجاح', 'status' => 'success']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'فشلت عملية الحذف', 'error' => $e->getMessage()], 500);
        }
    }
}
