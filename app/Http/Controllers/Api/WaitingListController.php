<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\JoinWaitlistRequest;
use App\Services\WaitingListService;
use App\Models\WaitingList;
use App\Http\Resources\Api\WaitingListResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class WaitingListController extends Controller
{
    public function __construct(protected WaitingListService $service) {}

    // 1. انضمام الطالب
    public function join(JoinWaitlistRequest $request)
    {
        try {
            $entry = $this->service->addToWaitlist($request->validated());
            return response()->json([
                'status' => true,
                'message' => "تم الانضمام للانتظار، ترتيبك: {$entry->priority_order}"
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // 2. عرض القائمة لإعلان معين (للإدارة: سوبر أدمن وسكرتير)
    public function index(Request $request)
{
    $request->validate([
        'bookable_id'   => 'required|integer',
        'bookable_type' => 'required|string',
    ]);

    // جلب الإعلان أولاً
    $ad = \App\Models\Advertisement::findOrFail($request->bookable_id);
$user = request()->user();
    // التحقق من الصلاحية:
    // 1. إذا كان سوبر أدمن -> مسموح
    // 2. إذا كان سكرتير -> يجب أن يكون institute_id الخاص به مطابقاً لمعهد الإعلان
    if ($user->hasRole('secretary')) {
        if ($user->institute_id !== $ad->institute_id) {
            return response()->json(['status' => false, 'message' => 'غير مصرح لك بعرض بيانات معهد آخر.'], 403);
        }
    } else {
        // التحقق من صلاحية السوبر أدمن
        Gate::authorize('viewAny', WaitingList::class);
    }

    $list = WaitingList::where('bookable_id', $ad->id)
        ->where('bookable_type', $request->bookable_type)
        ->orderBy('priority_order', 'asc')
        ->with('user')
        ->get();

    return WaitingListResource::collection($list);
}
    // 3. عرض قائمة الطالب الشخصية
    public function myWaitlist()
    {
        // هنا لا نحتاج Gate معقد لأننا نحضر فقط بيانات المستخدم المسجل
        $list = WaitingList::where('user_id', Auth::id())
            ->with('bookable')
            ->latest()
            ->get();

        return WaitingListResource::collection($list);
    }

    // 4. عرض سجل واحد بالتفصيل (إذا احتجته)
    public function show(WaitingList $waitingList)
    {
        Gate::authorize('view', $waitingList);
        return new WaitingListResource($waitingList->load(['user', 'bookable']));
    }
}
