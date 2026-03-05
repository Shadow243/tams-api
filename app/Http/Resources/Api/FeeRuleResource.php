<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeeRuleResource extends JsonResource
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
            'transaction_type_id' => $this->transaction_type_id,
            'transaction_type' => new TransactionTypeResource($this->whenLoaded('transactionType')),
            'operator_id' => $this->operator_id,
            'operator' => new OperatorResource($this->whenLoaded('operator')),
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'fee_mode' => $this->fee_mode->value,
            'fee_mode_label' => $this->fee_mode->label(),
            'value' => $this->value,
            'min_fee' => $this->min_fee,
            'max_fee' => $this->max_fee,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
