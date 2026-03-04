<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'operator_id' => $this->operator_id,
            'operator' => new OperatorResource($this->whenLoaded('operator')),
            'wallet_number' => $this->wallet_number,
            'balance' => $this->balance,
            'currency' => $this->currency,
            'formatted_balance' => $this->formatted_balance,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_active' => $this->status->isActive(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
