<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * استجابة النجاح القياسية
     * تستخدم عند جلب البيانات بنجاح أو إتمام عملية (مثل الحجز، الإعجاب)
     */
    public function successResponse($data = [], $message = 'تمت العملية بنجاح', $statusCode = 200)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $data,
            'errors'  => null,
        ], $statusCode);
    }

    /**
     * استجابة الخطأ القياسية
     * تستخدم عند فشل عملية (مثل خطأ في الصلاحيات، أو خطأ في عملية الدفع)
     * يمكن تمرير مصفوفة الأخطاء (Validation Errors) بداخلها
     */
    public function errorResponse($message = 'حدث خطأ ما', $errors = null, $statusCode = 400)
    {
        return response()->json([
            'status'  => false,
            'message' => $message,
            'data'    => null,
            'errors'  => $errors,
        ], $statusCode);
    }

    /**
     * استجابة الصفحات القياسية (Pagination)
     * مخصصة لتسهيل عمل مبرمج الـ Flutter عند طلب قوائم طويلة (مثل الإعلانات)
     * لعمل (Infinite Scroll) بسهولة
     */
    public function paginatedResponse($resource, $message = 'تم جلب البيانات بنجاح', $statusCode = 200)
    {
        return response()->json([
            'status'  => true,
            'message' => $message,
            'data'    => $resource->items(),
            'meta'    => [
                'current_page' => $resource->currentPage(),
                'last_page'    => $resource->lastPage(),
                'per_page'     => $resource->perPage(),
                'total'        => $resource->total(),
            ],
            'errors'  => null,
        ], $statusCode);
    }
}
