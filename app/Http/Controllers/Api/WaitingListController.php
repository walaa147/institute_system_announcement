<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Users\JoinWaitlistRequest;
use App\Services\WaitingListService;

class WaitingListController extends Controller
{
    public function __construct(protected WaitingListService $service) {}

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
}
