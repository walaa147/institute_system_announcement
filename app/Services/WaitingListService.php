<?php

namespace App\Services;

use App\Models\WaitingList;
use Illuminate\Support\Facades\Auth;

class WaitingListService
{
    public function addToWaitlist(array $data): WaitingList
    {
        $exists = WaitingList::where('user_id', Auth::id())
            ->where('bookable_id', $data['bookable_id'])
            ->where('bookable_type', $data['bookable_type'])
            ->where('status', 'waiting')
            ->exists();

        if ($exists) {
            throw new \Exception("أنت مسجل بالفعل في قائمة الانتظار.");
        }

        $lastOrder = WaitingList::where('bookable_id', $data['bookable_id'])
            ->where('bookable_type', $data['bookable_type'])
            ->max('priority_order') ?? 0;

        return WaitingList::create([
            'user_id'        => Auth::id(),
            'bookable_id'    => $data['bookable_id'],
            'bookable_type'  => $data['bookable_type'],
            'priority_order' => $lastOrder + 1,
            'status'         => 'waiting',
        ]);
    }
}
