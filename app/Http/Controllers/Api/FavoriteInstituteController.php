<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Services\FavoriteInstituteService;
use App\Http\Requests\Student\ToggleFavoriteRequest;
use App\Http\Resources\InstituteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FavoriteInstituteController extends Controller
{

    public function __construct(private readonly FavoriteInstituteService $service) {}

    //عرض قائمة المعاهد المفضلة للطالب الحالي

   public function index(\Illuminate\Http\Request $request): AnonymousResourceCollection
    {

        $user = $request->user();

        // جلب المعاهد التي يمتلك الطالب سجل مفضلة لها
        $institutes = Institute::whereHas('favorites', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->latest()
        ->get();

        return InstituteResource::collection($institutes);
    }

    // إضافة أو إزالة معهد من المفضلة

    public function toggle(ToggleFavoriteRequest $request, Institute $institute): JsonResponse
    {
        try {

            $result = $this->service->toggleFavorite($request->user(), $institute->id);

            return response()->json([
                'status'       => 'success',
                'message'      => $result['message'],
                'is_favorited' => $result['is_favorited'],
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'message' => 'فشلت عملية تعديل المفضلة',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
