<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name_ar,
            'name_english' => $this->name_en,
            'description'  => $this->description,
            'is_active'    => (bool) $this->is_active,
            'institute'    => $this->whenLoaded('institute', function() {
                return ['id' => $this->institute->id, 'name' => $this->institute->name_ar];
            }),
        ];
    }
}
