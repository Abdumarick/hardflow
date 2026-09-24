<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostCustomerLedgerEntryAction
{
    public function execute(User $actor, Customer $customer, string $type, string $debit, string $credit, int $branchId, Model $source, string $reference, ?string $description = null): CustomerLedgerEntry
    {
        if ((bccomp($debit, '0', 2) > 0) === (bccomp($credit, '0', 2) > 0)) {
            throw ValidationException::withMessages(['amount' => 'A customer ledger entry requires exactly one positive debit or credit.']);
        }

        return DB::transaction(fn () => CustomerLedgerEntry::query()->create(['business_id' => $customer->business_id, 'branch_id' => $branchId, 'customer_id' => $customer->id, 'entry_type' => $type, 'debit' => $debit, 'credit' => $credit, 'source_type' => $source->getMorphClass(), 'source_id' => $source->getKey(), 'reference' => $reference, 'description' => $description, 'occurred_at' => now(), 'created_by' => $actor->id]));
    }
}
