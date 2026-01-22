<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Diploma;
use App\Models\Like; // تم تغيير الموديل من Favorite إلى Like
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller // تم تغيير اسم الكلاس
{
    /**
     * تبديل حالة الإعجاب (Like/Unlike) لكورس أو دبلوم
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:course,diploma',
            'id' => 'required|integer',
        ]);

        // تحديد موديل الكائن (Course::class أو Diploma::class)
        $modelClass = $request->type === 'course' ? Course::class : Diploma::class;

        // 1. العثور على الكائن المراد الإعجاب به
        $item = $modelClass::findOrFail($request->id);

        // 2. التحقق من المنطق (الأمان)
        if ($item->is_open) {
            return response()->json([
                'message' => 'لا يمكن إضافة العناصر المفتوحة للتسجيل إلى قائمة الإعجابات. يجب الحجز مباشرة.',
                'status' => 'error'
            ], 403);
        }

        // 3. البحث عن سجل الإعجاب الحالي باستخدام الحقول الجديدة
        $like = Like::where('user_id', Auth::id())
            ->where('likeable_type', $modelClass)
            ->where('likeable_id', $item->id)
            ->first();

        // 4. منطق التبديل (Toggle Logic)
        if ($like) {
            // إذا كان موجوداً، قم بحذفه (Unlike)
            $like->delete();
            $message = 'تمت الإزالة من الإعجابات.';
            $action = 'unliked';
        } else {
            // إذا لم يكن موجوداً، قم بإنشاء سجل جديد (Like)
            Like::create([
                'user_id' => Auth::id(),
                'likeable_type' => $modelClass,
                'likeable_id' => $item->id,
            ]);
            $message = 'تمت الإضافة إلى الإعجابات.';
            $action = 'liked';
        }

        // إرجاع عدد الإعجابات الجديد باستخدام اسم العلاقة الجديد
        $newCount = $item->likes()->count();

        return response()->json([
            'message' => $message,
            'action' => $action,
            'likes_count' => $newCount
        ]);
    }
}
