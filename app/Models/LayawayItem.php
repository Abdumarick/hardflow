<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LayawayItem extends Model { protected $fillable=['business_id','layaway_plan_id','product_id','product_unit_id','quantity','target_unit_price']; protected function casts():array{return ['quantity'=>'decimal:4','target_unit_price'=>'decimal:2'];} public function product():BelongsTo{return $this->belongsTo(Product::class);} public function unit():BelongsTo{return $this->belongsTo(ProductUnit::class,'product_unit_id');} }
