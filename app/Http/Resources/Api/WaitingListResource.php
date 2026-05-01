<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaitingListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
public function toArray($request): array
{
    return [
        'id' => $this->id,
        'user' => [
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
        ],
        'priority_order' => $this->priority_order,
        'status' => $this->status,
        'created_at' => $this->created_at->format('Y-m-d H:i'),
        'updated_at' => $this->updated_at->format('Y-m-d H:i'),
        'bookable_id' => $this->bookable_id,
        'bookable_type' => $this->bookable_type,
        'bookable' => $this->whenLoaded('bookable', function () {
            return [
                'id' => $this->bookable->id,
                'title' => $this->bookable->title_ar ?? null,
            ];
        }),

    ];
}
}
