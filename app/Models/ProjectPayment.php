<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectPayment extends Model { protected $fillable=['business_id','project_contract_id','project_payment_request_id','payment_id','amount']; protected function casts():array{return ['amount'=>'decimal:2'];} public function payment():BelongsTo{return $this->belongsTo(Payment::class);} }
