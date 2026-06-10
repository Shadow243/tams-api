<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'country_id' => $this->country_id,
            'country' => new CountryResource($this->whenLoaded('country')),
            'address' => $this->address,
            'cash_balance' => $this->cash_balance, // Legacy field (deprecated, use balances instead)
            'balances' => $this->whenLoaded('balances', function () use ($request) {
                $user = $request->user();
                // Agents only see cash balances for their own branch
                if ($user && $user->hasRole('agent') && $user->branch_id !== $this->id) {
                    return [];
                }
                return $this->balances->map(function ($balance) {
                    return [
                        'currency_code' => $balance->currency_code,
                        'currency' => $balance->relationLoaded('currency') ? [
                            'code' => $balance->currency->code,
                            'name' => $balance->currency->name,
                            'symbol' => $balance->currency->symbol,
                        ] : null,
                        'cash_balance' => (float) $balance->cash_balance,
                        'formatted_balance' => number_format((float) $balance->cash_balance, 2),
                    ];
                });
            }),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
