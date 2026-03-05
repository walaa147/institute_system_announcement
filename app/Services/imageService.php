<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    /**
     * رفع صورة جديدة مع التحقق من سلامتها وحذف القديمة
     */
    public function updateImage($newImage, ?string $oldPath, string $folder): ?string
    {
        // 1. التحقق: هل المدخل ملف فعلاً وهل هو سليم؟ (isValid)
        if (!$newImage || !($newImage instanceof UploadedFile) || !$newImage->isValid()) {
            return $oldPath; // إذا لم تكن صورة سليمة، نرجع المسار القديم كما هو
        }

        // 2. حذف الصورة القديمة من السيرفر إذا وُجدت لتوفير المساحة
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // 3. رفع الصورة الجديدة وتخزينها في المجلد المحدد
        // يتم استخدام store لضمان توليد اسم فريد وتجنب تداخل الأسماء
        return $newImage->store($folder, 'public');
    }

    /**
     * حذف صورة نهائياً
     */
    public function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
