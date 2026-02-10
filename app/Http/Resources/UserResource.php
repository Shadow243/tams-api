<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class UserResource extends JsonResource
{
    public function toArray($request): array
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
