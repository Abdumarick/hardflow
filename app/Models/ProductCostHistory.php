<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCostHistory extends Model
{
    protected $table = 'product_cost_history';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['supplied_unit_cost' => 'decimal:2', 'conversion_factor' => 'decimal:6', 'base_unit_cost' => 'decimal:6', 'previous_average_cost' => 'decimal:2', 'new_average_cost' => 'decimal:2', 'recorded_at' => 'datetime'];
    }
}
