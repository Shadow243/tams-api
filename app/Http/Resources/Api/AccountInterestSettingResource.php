<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountInterestSettingResource extends JsonResource
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
            'customer_account_id' => $this->customer_account_id,
            'customer_account' => new CustomerAccountResource($this->whenLoaded('customerAccount')),
            'interest_type' => $this->interest_type->value,
            'interest_type_label' => $this->interest_type->label(),
            'interest_rate' => $this->interest_rate ? (float) $this->interest_rate : null,
            'fixed_amount' => $this->fixed_amount ? (float) $this->fixed_amount : null,
            'application_period' => $this->application_period->value,
            'application_period_label' => $this->application_period->label(),
            'apply_on_negative_balance' => $this->apply_on_negative_balance,
            'apply_on_positive_balance' => $this->apply_on_positive_balance,
            'last_applied_at' => $this->last_applied_at,
            'next_application_date' => $this->next_application_date,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
