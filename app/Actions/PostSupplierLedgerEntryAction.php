<?php

namespace App\Actions;

use App\Enums\SupplierLedgerType;
use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostSupplierLedgerEntryAction
{
    public function execute(User $actor, Supplier $supplier, SupplierLedgerType $type, string $debit, string $credit, ?int $branchId = null, ?Model $source = null, ?string $notes = null): SupplierLedgerEntry
    {
        if (! is_numeric($debit) || ! is_numeric($credit) || bccomp($debit, '0', 2) < 0 || bccomp($credit, '0', 2) < 0 || (bccomp($debit, '0', 2) === 0) === (bccomp($credit, '0', 2) === 0)) {
            throw ValidationException::withMessages(['amount' => 'A ledger entry must have exactly one positive debit or credit.']);
        }

        return DB::transaction(function () use ($actor, $supplier, $type, $debit, $credit, $branchId, $source, $notes) {
            Supplier::query()->lockForUpdate()->findOrFail($supplier->id);
            if ($source && SupplierLedgerEntry::query()->where('entry_type', $type->value)->where('source_type', $source->getMorphClass())->where('source_id', $source->getKey())->exists()) {
                throw ValidationException::withMessages(['ledger' => 'This source has already been posted to the supplier ledger.']);
            }

            return SupplierLedgerEntry::query()->create([
                'business_id' => $supplier->business_id, 'branch_id' => $branchId, 'supplier_id' => $supplier->id,
                'entry_type' => $type, 'debit' => $debit, 'credit' => $credit,
                'source_type' => $source?->getMorphClass(), 'source_id' => $source?->getKey(),
                'entered_by' => $actor->id, 'occurred_at' => now(), 'notes' => $notes,
            ]);
        });
    }
}
