<?php

namespace App\Services;

use App\Models\Institute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InstituteService
{
    public function store(array $data): Institute
    {
        return DB::transaction(function () use ($data) {
            if (isset($data['image']) && $data['image']->isValid()) {
                $data['photo_path'] = $data['image']->store('institutes', 'public');
            }
            unset($data['image']);

            return Institute::create($data);
        });
    }

    public function update(Institute $institute, array $data): Institute
    {
        return DB::transaction(function () use ($institute, $data) {
            if (isset($data['image']) && $data['image']->isValid()) {
                // حذف الصورة القديمة
                if ($institute->photo_path) {
                    Storage::disk('public')->delete($institute->photo_path);
                }
                $data['photo_path'] = $data['image']->store('institutes', 'public');
            }
            unset($data['image']);

            $institute->update($data);
            return $institute;
        });
    }

    public function delete(Institute $institute): bool
    {
        return DB::transaction(function () use ($institute) {
            if ($institute->photo_path) {
                Storage::disk('public')->delete($institute->photo_path);
            }
            return $institute->delete();
        });
    }
}
