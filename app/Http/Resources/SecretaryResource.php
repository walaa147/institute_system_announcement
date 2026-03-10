<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SecretaryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'institute' => [
                'id' => $this->institute_id,
                'name_ar' => $this->institute?->name_ar,
                'name_en' => $this->institute?->name_en,
                'is_active'=> $this->institute?->is_active??$this->institute->status??null,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
