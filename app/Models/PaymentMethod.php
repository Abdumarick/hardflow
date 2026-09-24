<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = ['business_id', 'name', 'type', 'requires_reference', 'is_active'];

    protected function casts(): array
    {
        return ['type' => AccountType::class, 'requires_reference' => 'boolean', 'is_active' => 'boolean'];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class);
    }
}
