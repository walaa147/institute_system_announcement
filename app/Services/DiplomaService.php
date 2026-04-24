<?php

namespace App\Services;

use App\Models\Diploma;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str; // ✅ مهم

class DiplomaService
{
   /* public function store(array $data): Diploma
    {
        return DB::transaction(function () use ($data) {

            if (!Auth::check()) {
                throw new \Exception('Unauthorized');
            }

            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_available'] = $data['is_available'] ?? true;

            // ✅ توليد slug تلقائي
            $title = $data['title_ar'] ?? null;
            //$data['name_ar'] = $data['title_ar'];

if (!$title) {
    throw new \Exception('title_ar is missing - cannot generate slug');
}

$data['slug'] = Str::slug($title) . '-' . time();

            // 🖼️ الصورة
            if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['photo_path'] = $data['image']->store('diplomas', 'public');
            }

            // 🔗 الكورسات (اختياري)
            $courses = [];
            if (!empty($data['course_ids'])) {
                $courses = collect($data['course_ids'])
                    ->mapWithKeys(fn($id, $index) => [
                        $id => ['sort_order' => $index]
                    ]);
            }

            unset($data['course_ids'], $data['image']);

            $diploma = Diploma::create($data);

            // ربط الكورسات فقط لو موجودة
            if (!empty($courses)) {
                $diploma->courses()->sync($courses);
            }

            return $diploma->load(['courses:id,name_ar']);
        });
    }*/
        public function store(array $data): Diploma
{
    return DB::transaction(function () use ($data) {

        // 🔐 التحقق من تسجيل الدخول
        if (!Auth::check()) {
            throw new \Exception('Unauthorized');
        }

        // 👤 بيانات المستخدم
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        // 🔘 القيم الافتراضية
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_available'] = $data['is_available'] ?? true;

        // ⚠️ حل مشكلة name_ar (إجباري في قاعدة البيانات)
        $data['name_ar'] = $data['name_ar'] ?? $data['title_ar'];

        // 🔥 التحقق من title_ar
        $title = $data['title_ar'] ?? null;

        if (!$title) {
            throw new \Exception('title_ar is missing - cannot generate slug');
        }

        // 🔗 توليد slug
        $data['slug'] = Str::slug($title) . '-' . time();

        // 🖼️ رفع الصورة
        if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['photo_path'] = $data['image']->store('diplomas', 'public');
        }

        // 📚 تجهيز الكورسات
        $courses = [];
        if (!empty($data['course_ids'])) {
            $courses = collect($data['course_ids'])
                ->mapWithKeys(fn($id, $index) => [
                    $id => ['sort_order' => $index]
                ]);
        }

        // 🧹 تنظيف البيانات غير اللازمة
        unset($data['course_ids'], $data['image']);

        // 💾 حفظ الدبلوم
        $diploma = Diploma::create($data);

        // 🔗 ربط الكورسات (إذا موجودة)
        if (!empty($courses)) {
            $diploma->courses()->sync($courses);
        }

        // 📦 إرجاع النتيجة مع العلاقات
        return $diploma->load(['courses:id,name_ar']);
    });
}

    public function update(Diploma $diploma, array $data): Diploma
    {
        return DB::transaction(function () use ($diploma, $data) {

            if (!Auth::check()) {
                throw new \Exception('Unauthorized');
            }

            $data['updated_by'] = Auth::id();

            // 🖼️ الصورة
            if (!empty($data['image']) && $data['image'] instanceof UploadedFile) {

                if ($diploma->photo_path && Storage::disk('public')->exists($diploma->photo_path)) {
                    Storage::disk('public')->delete($diploma->photo_path);
                }

                $data['photo_path'] = $data['image']->store('diplomas', 'public');
            }

            // 🔗 الكورسات (اختياري)
            if (isset($data['course_ids'])) {
                $courses = collect($data['course_ids'])
                    ->mapWithKeys(fn($id, $index) => [
                        $id => ['sort_order' => $index]
                    ]);

                $diploma->courses()->sync($courses);
            }

            // ❗ لو عدلت العنوان، حدث slug
            if (isset($data['title_ar'])) {
                $data['slug'] = Str::slug($data['title_ar']) . '-' . time();
            }

            unset($data['course_ids'], $data['image']);

            $diploma->update($data);

            return $diploma->refresh()->load(['courses:id,name_ar']);
        });
    }

    public function delete(Diploma $diploma): bool
    {
        return DB::transaction(function () use ($diploma) {

            if ($diploma->photo_path && Storage::disk('public')->exists($diploma->photo_path)) {
                Storage::disk('public')->delete($diploma->photo_path);
            }

            $diploma->courses()->detach();

            return $diploma->delete();
        });
    }
}