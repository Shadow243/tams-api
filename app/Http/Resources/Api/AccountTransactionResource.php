<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Api\BranchResource;

class AccountTransactionResource extends JsonResource
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
            'reference' => $this->reference,
            'customer_account_id' => $this->customer_account_id,
            'customer_account' => new CustomerAccountResource($this->whenLoaded('customerAccount')),
            'transaction_id' => $this->transaction_id,
            'transaction' => new TransactionResource($this->whenLoaded('transaction')),
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_sign' => $this->type->sign(),
            'type_color' => $this->type->color(),
            'amount' => (float) $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'balance_before' => (float) $this->balance_before,
            'balance_after' => (float) $this->balance_after,
            'description' => $this->description,
            'is_credit' => $this->isCredit(),
            'is_debit' => $this->isDebit(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
