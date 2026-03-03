<?php

namespace App\Services;

use App\Models\Institute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
// use App\Models\AuditLog; // سنحتاج هذا الموديل لتسجيل الأحداث

class InstituteService
{
    public function store(array $data): Institute
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['logo']) && $data['logo']->isValid()) {
                $data['logo'] = $data['logo']->store('institutes', 'public');
            }
           // unset($data['logo']);
// 3. توثيق الحدث في سجل التدقيق المركزي
            // AuditLog::create([
            //     'action' => 'create_institute',
            //     'description' => "تم إنشاء معهد جديد باسم: {$institute->name}",
            //     'user_id' => auth()->id(),
            // ]);
            return Institute::create($data);
        });
    }

    public function update(Institute $institute, array $data): Institute
    {
        return DB::transaction(function () use ($institute, $data) {
            if (isset($data['logo']) && $data['logo']->isValid()) {
                // حذف الصورة القديمة
                if ($institute->logo) {
                    Storage::disk('public')->delete($institute->logo);
                }
                $data['logo'] = $data['logo']->store('institutes', 'public');
            }
          //  unset($data['logo']);

            $institute->update($data);
            return $institute;
        });
    }

    public function delete(Institute $institute): bool
    {
        return DB::transaction(function () use ($institute) {
            if ($institute->logo) {
                Storage::disk('public')->delete($institute->logo);
            }
            return $institute->delete();
        });
    }
}
