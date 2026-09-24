<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
class NeighbourStockBorrow extends Model {
    protected $fillable = ['business_id','branch_id','sale_id','sale_item_id','product_id','quantity','returned_quantity','conversion_factor','neighbour_name','neighbour_phone','return_due_date','returned_at','notes','return_notes','status','created_by'];
    protected function casts(): array { return ['quantity'=>'decimal:4','returned_quantity'=>'decimal:4','conversion_factor'=>'decimal:4','return_due_date'=>'date','returned_at'=>'date']; }
    protected static function booted(): void { static::creating(fn(self $model) => $model->public_id ??= (string) Str::uuid()); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
}
