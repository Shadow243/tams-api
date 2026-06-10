<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canSeeBalance = !$user || !$user->hasAnyRole(['agent', 'caissier']);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'account_number' => $this->account_number,
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'currency_id' => $this->currency_id,
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'balance' => $canSeeBalance ? (float) $this->balance : null,
            'credit_limit' => $canSeeBalance ? (float) $this->credit_limit : null,
            'available_balance' => $canSeeBalance ? $this->available_balance : null,
            'is_in_debt' => $canSeeBalance ? $this->isInDebt() : null,
            'debt_amount' => $canSeeBalance ? $this->debt_amount : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_vip' => $this->is_vip,
            'notes' => $this->notes,
            'interest_settings' => new AccountInterestSettingResource($this->whenLoaded('interestSettings')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
