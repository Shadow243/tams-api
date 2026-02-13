<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'username' => $this->username,
            'gender' => $this->gender,
            'country_code' => $this->country_code,
            'phone_number' => $this->phone_number,
            'full_number' => $this->full_number,
            'email' => $this->email,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            'active' => $this->active,
            'avatar' => $this->avatar,
            'avatar_url' => $this->avatar ? $this->mediaUrl('avatar') : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
