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
            'branch_id' => $this->branch_id,
            'avatar' => $this->avatar ? [
                'full' => $this->mediaUrl('avatar'),
                'thumbnail' => $this->mediaUrl('avatar'), // Using same URL for both for now
            ] : null,
            'settings' => $this->settings ?? [],
            'two_factor_enabled' => (bool) $this->two_factor_enabled,
            'permissions' => $this->getAllPermissions()->pluck('name')->toArray(),
            'roles' => $this->getRoleNames()->toArray(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
