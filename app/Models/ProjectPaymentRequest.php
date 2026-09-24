<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ProjectPaymentRequest extends Model { protected $fillable=['business_id','project_contract_id','request_number','title','amount','paid_amount','requested_date','status','notes']; protected function casts():array{return ['amount'=>'decimal:2','paid_amount'=>'decimal:2','requested_date'=>'date'];} public function project():BelongsTo{return $this->belongsTo(ProjectContract::class,'project_contract_id');} }
