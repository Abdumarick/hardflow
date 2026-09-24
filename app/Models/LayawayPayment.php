<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class LayawayPayment extends Model { protected $fillable=['business_id','layaway_plan_id','payment_id','amount']; protected function casts():array{return ['amount'=>'decimal:2'];} public function plan():BelongsTo{return $this->belongsTo(LayawayPlan::class,'layaway_plan_id');} public function payment():BelongsTo{return $this->belongsTo(Payment::class);} }
