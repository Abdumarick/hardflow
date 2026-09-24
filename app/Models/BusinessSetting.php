<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessSetting extends Model
{
    protected $fillable = ['business_id', 'key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
