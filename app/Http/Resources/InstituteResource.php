<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InstituteResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'name_ar'     => $this->name_ar,
            'name_en'     => $this->name_en,
            'description' => $this->description,
            'address'     => $this->address,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'image_url'   => $this->photo_path ? url('storage/' . $this->photo_path) : null,

            // إحصائيات سريعة
            'departments_count' => $this->whenCounted('departments'),
            'employees_count'   => $this->whenCounted('employees'),

            'created_at'  => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
