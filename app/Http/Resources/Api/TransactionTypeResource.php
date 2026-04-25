<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionTypeResource extends JsonResource
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
            'description' => $this->description,
            'branch_effect' => $this->branch_effect ?? 'none',
            'branch_amount' => $this->branch_amount ?? 'gross',
            'wallet_effect' => $this->wallet_effect ?? 'none',
            'wallet_amount' => $this->wallet_amount ?? 'gross',
            'dest_branch_effect'  => $this->dest_branch_effect ?? 'none',
            'dest_branch_amount'  => $this->dest_branch_amount ?? 'gross',
            'dest_wallet_effect'  => $this->dest_wallet_effect ?? 'none',
            'dest_wallet_amount'  => $this->dest_wallet_amount ?? 'gross',
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
