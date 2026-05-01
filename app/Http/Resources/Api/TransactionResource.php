<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'transaction_type_id' => $this->transaction_type_id,
            'transaction_type' => new TransactionTypeResource($this->whenLoaded('transactionType')),
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'destination_branch_id' => $this->destination_branch_id,
            'destination_branch' => new BranchResource($this->whenLoaded('destinationBranch')),
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'completed_by' => $this->completed_by,
            'completed_by_user' => new UserResource($this->whenLoaded('completedBy')),
            'served_by_branch_id' => $this->served_by_branch_id,
            'served_by_branch' => new BranchResource($this->whenLoaded('servedByBranch')),
            'customer_id' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'dest_customer_id' => $this->dest_customer_id,
            'dest_customer' => new CustomerResource($this->whenLoaded('destCustomer')),
            'wallet_id' => $this->wallet_id,
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'dest_wallet_id' => $this->dest_wallet_id,
            'dest_wallet' => new WalletResource($this->whenLoaded('destWallet')),
            'customer_phone' => $this->customer_phone,
            'gross_amount' => $this->gross_amount,
            'fee_amount' => $this->fee_amount,
            'net_amount' => $this->net_amount,
            'currency_code' => $this->currency_code,
            'currency_id' => $this->currency_id,
            'currency' => new CurrencyResource($this->whenLoaded('currency')),
            'fee_rule_id' => $this->fee_rule_id,
            'fee_rule' => new FeeRuleResource($this->whenLoaded('feeRule')),
            'fee_mode_applied' => $this->fee_mode_applied->value,
            'fee_mode_applied_label' => $this->fee_mode_applied->label(),
            'fee_snapshot' => $this->fee_snapshot,
            'parent_transaction_id' => $this->parent_transaction_id,
            'parent_transaction' => new TransactionResource($this->whenLoaded('parentTransaction')),
            'child_transactions' => TransactionResource::collection($this->whenLoaded('childTransactions')),
            'withdrawal_code' => $this->withdrawal_code,
            'expires_at' => $this->expires_at,
            'completed_at' => $this->completed_at,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'can_be_modified' => $this->canBeModified(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
