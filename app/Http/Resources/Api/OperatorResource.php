<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OperatorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id ?? null,
            'name' => $this->name ?? null,
            'country_id' => $this->country_id ?? null,
            'country' => $this->relationLoaded('country') && $this->country ? [
                'id' => $this->country->id ?? null,
                'name' => $this->country->name ?? null,
                'code' => $this->country->code ?? null,
            ] : null,
            'logo' => $this->logo ?? null,
            'logo_url' => $this->logo ? $this->mediaUrl('logo') : null,
            'created_at' => $this->created_at ?? null,
            'updated_at' => $this->updated_at ?? null,
        ];
    }
}
