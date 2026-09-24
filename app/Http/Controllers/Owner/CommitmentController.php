<?php

namespace App\Http\Controllers\Owner;

use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\LayawayPlan;
use App\Models\ProjectContract;
use App\Models\ProjectPaymentRequest;
use App\Models\ProjectPayment;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\ProductUnit;
use App\Models\StockBalance;
use App\Models\ProjectMaterialIssue;
use App\Models\Sale;
use App\Models\NumberSequence;
use App\Enums\SaleStatus;
use App\Enums\PaymentStatus;
use App\Enums\FulfillmentStatus;
use App\Actions\ApplyStockMovementAction;
use App\Enums\StockStatus;
use App\Enums\StockMovementType;
use App\Actions\PostCustomerLedgerEntryAction;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommitmentController extends Controller
{
    private function business(Request $request, TenantContext $tenant)
    {
        $business = $tenant->businessOrFail();
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::CustomersView, $business), 403);
        return $business;
    }

    public function index(Request $request, TenantContext $tenant): View
    {
        return $this->dashboard($request, $tenant, $request->string('tab')->toString() === 'projects' ? 'projects' : 'layaway');
    }

    public function layaway(Request $request, TenantContext $tenant): View
    {
        return $this->dashboard($request, $tenant, 'layaway');
    }

    public function projects(Request $request, TenantContext $tenant): View
    {
        return $this->dashboard($request, $tenant, 'projects');
    }

    private function dashboard(Request $request, TenantContext $tenant, string $mode): View
    {
        $business = $this->business($request, $tenant);
        $branchId = $tenant->branchId();
        $perPage = (int) $request->input('per_page', 12);
        if (! in_array($perPage, [6, 9, 12, 24], true)) $perPage = 12;
        $base = ['business' => $business, 'mode' => $mode, 'perPage' => $perPage,
            'customers' => Customer::query()->where('business_id', $business->id)->where('is_active', true)->orderBy('name')->get(),
            'accounts' => PaymentAccount::query()->where('business_id', $business->id)->where('is_active', true)->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))->with('method')->orderBy('name')->get(),
            'projectUnits' => $this->productUnits($business->id),
        ];
        if ($mode === 'layaway') {
            $query = LayawayPlan::query()->where('branch_id', $branchId)->with(['customer','items.product','items.unit.unit','payments.payment'])->latest();
            if ($request->filled('search')) $query->where(fn ($q) => $q->where('plan_number', 'like', '%'.$request->string('search').'%')->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->string('search').'%')));
            if ($request->filled('status')) $query->where('status', $request->string('status'));
            if ($request->filled('customer_id')) $query->where('customer_id', $request->integer('customer_id'));
            $all = (clone $query)->get();
            return view('owner.commitments.dashboard', $base + ['plans' => $query->paginate($perPage)->withQueryString(), 'summary' => ['target' => $all->sum('target_amount'), 'paid' => $all->sum('paid_amount'), 'remaining' => $all->sum(fn ($p) => max(0, (float)$p->target_amount-(float)$p->paid_amount)), 'count' => $all->count()]]);
        }
        $query = ProjectContract::query()->where('branch_id', $branchId)->with(['customer','requests','payments.payment','materialIssues.items'])->latest();
        if ($request->filled('search')) $query->where(fn ($q) => $q->where('project_number', 'like', '%'.$request->string('search').'%')->orWhere('name', 'like', '%'.$request->string('search').'%')->orWhereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->string('search').'%')));
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('customer_id')) $query->where('customer_id', $request->integer('customer_id'));
        $all = (clone $query)->get();
        $issued = $all->sum(fn ($p) => $p->materialIssues->sum(fn ($i) => $i->items->sum(fn ($x) => (float)$x->quantity*(float)$x->conversion_factor*(float)$x->unit_cost)));
        return view('owner.commitments.dashboard', $base + ['projects' => $query->paginate($perPage)->withQueryString(), 'summary' => ['contract' => $all->sum('contract_amount'), 'paid' => $all->sum('paid_amount'), 'issued' => $issued, 'remaining' => $all->sum(fn ($p) => (float)$p->contract_amount-(float)$p->paid_amount), 'count' => $all->count()]]);
    }

    private function productUnits(int $businessId)
    {
        return ProductUnit::query()->where('business_id',$businessId)->where('can_sell',true)->where('is_active',true)->with(['product','unit','prices' => fn ($q) => $q->where('is_active', true)->where('current_slot', true)])->get()->map(fn ($unit) => ['id' => $unit->id, 'name' => $unit->product->name, 'unit' => $unit->unit->symbol, 'price' => (float) optional($unit->prices->first())->amount]);
    }

    public function showLayaway(Request $request, LayawayPlan $plan, TenantContext $tenant): View
    {
        $business=$this->business($request,$tenant); abort_unless($plan->business_id===$business->id && $plan->branch_id===$tenant->branchId(),403);
        $plan->load('customer'); $perPage=$this->detailPerPage($request);
        $items=$plan->items()->with(['product','unit.unit'])->when($request->filled('goods_search'),fn($q)=>$q->whereHas('product',fn($p)=>$p->where('name','like','%'.$request->string('goods_search').'%')))->latest()->paginate($perPage,['*'],'goods_page')->withQueryString();
        $payments=$plan->payments()->with('payment.account.method')->latest()->paginate($perPage,['*'],'payments_page')->withQueryString();
        $itemsValue=$plan->items()->get()->sum(fn($item)=>(float)$item->quantity*(float)$item->target_unit_price);
        return view('owner.commitments.detail', ['business'=>$business,'mode'=>'layaway','record'=>$plan,'items'=>$items,'itemsValue'=>$itemsValue,'payments'=>$payments,'requests'=>null,'accounts'=>PaymentAccount::query()->where('business_id',$business->id)->where('is_active',true)->with('method')->get(),'projectUnits'=>$this->productUnits($business->id),'detailPerPage'=>$perPage]);
    }

    public function showProject(Request $request, ProjectContract $project, TenantContext $tenant): View
    {
        $business=$this->business($request,$tenant); abort_unless($project->business_id===$business->id && $project->branch_id===$tenant->branchId(),403);
        $project->load('customer'); $perPage=$this->detailPerPage($request);
        $issues=$project->materialIssues()->with(['items.product','items.unit.unit'])->when($request->filled('goods_search'),fn($q)=>$q->whereHas('items.product',fn($p)=>$p->where('name','like','%'.$request->string('goods_search').'%')))->latest()->paginate($perPage,['*'],'goods_page')->withQueryString();
        $payments=$project->payments()->with('payment.account.method')->latest()->paginate($perPage,['*'],'payments_page')->withQueryString();
        $requests=$project->requests()->latest()->paginate($perPage,['*'],'requests_page')->withQueryString();
        $issuedValue=$project->materialIssues()->with('items')->get()->sum(fn($issue)=>$issue->items->sum(fn($item)=>(float)$item->quantity*(float)$item->conversion_factor*(float)$item->unit_cost));
        return view('owner.commitments.detail', ['business'=>$business,'mode'=>'projects','record'=>$project,'issues'=>$issues,'items'=>null,'payments'=>$payments,'requests'=>$requests,'openRequests'=>$project->requests()->whereIn('status',['requested','partially_paid'])->get(),'issuedValue'=>$issuedValue,'accounts'=>PaymentAccount::query()->where('business_id',$business->id)->where('is_active',true)->with('method')->get(),'projectUnits'=>$this->productUnits($business->id),'detailPerPage'=>$perPage]);
    }

    private function detailPerPage(Request $request): int
    {
        return in_array((int) $request->input('detail_per_page', 6), [6, 12, 24], true) ? (int) $request->input('detail_per_page', 6) : 6;
    }

    public function storeLayaway(Request $request, TenantContext $tenant): RedirectResponse
    {
        $business = $this->business($request, $tenant);
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::SalesCreate, $business), 403);
        $data = $request->validate(['customer_id' => ['required', 'integer'], 'target_amount' => ['required', 'numeric', 'gt:0'], 'collection_due_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $customer = Customer::query()->where('business_id', $business->id)->where('is_active', true)->findOrFail($data['customer_id']);
        $number = 'LAY-'.now()->format('Ymd').'-'.str_pad((string) (LayawayPlan::query()->where('business_id', $business->id)->max('id') + 1), 5, '0', STR_PAD_LEFT);
        $plan = LayawayPlan::query()->create(['business_id' => $business->id, 'branch_id' => $tenant->branchId(), 'customer_id' => $customer->id, 'plan_number' => $number, 'target_amount' => $data['target_amount'], 'collection_due_date' => $data['collection_due_date'] ?? null, 'notes' => $data['notes'] ?? null, 'created_by' => $request->user()->id]);
        AuditLog::query()->create(['user_id' => $request->user()->id, 'business_id' => $business->id, 'branch_id' => $tenant->branchId(), 'action' => 'layaway.created', 'subject_type' => LayawayPlan::class, 'subject_id' => $plan->id, 'new_values' => ['plan_number' => $number, 'target_amount' => $plan->target_amount]]);
        return back()->with('status', 'Layaway plan created. Products are not reserved until collection.');
    }

    public function storeLayawayItem(Request $request, LayawayPlan $plan, TenantContext $tenant): RedirectResponse
    {
        $business=$this->business($request,$tenant); abort_unless($plan->business_id===$business->id && $plan->branch_id===$tenant->branchId() && in_array($plan->status,['active','ready_for_collection'],true) && $request->user()->hasPermissionInBusiness(PermissionName::SalesCreate,$business),403);
        $data=$request->validate(['product_unit_id'=>['nullable','integer'],'quantity'=>['nullable','numeric','gt:0'],'target_unit_price'=>['nullable','numeric','min:0'],'items'=>['nullable','array','min:1'],'items.*.product_unit_id'=>['required_with:items','integer'],'items.*.quantity'=>['required_with:items','numeric','gt:0'],'items.*.target_unit_price'=>['required_with:items','numeric','min:0']]);
        $items = $data['items'] ?? [['product_unit_id'=>$data['product_unit_id'],'quantity'=>$data['quantity'],'target_unit_price'=>$data['target_unit_price']]];
        foreach ($items as $line) { $unit=ProductUnit::query()->with('product')->where('business_id',$business->id)->where('is_active',true)->findOrFail($line['product_unit_id']); $plan->items()->create(['business_id'=>$business->id,'product_id'=>$unit->product_id,'product_unit_id'=>$unit->id,'quantity'=>$line['quantity'],'target_unit_price'=>$line['target_unit_price']]); }
        $productsTotal = $plan->items()->get()->sum(fn ($item) => (float) $item->quantity * (float) $item->target_unit_price);
        if ($productsTotal > (float) $plan->target_amount) {
            $plan->update(['target_amount'=>$productsTotal, 'status'=>(float)$plan->paid_amount >= $productsTotal ? 'ready_for_collection' : 'active', 'completed_at'=>(float)$plan->paid_amount >= $productsTotal ? now() : null]);
            return back()->with('status','Products added and the layaway target amount was increased automatically. Stock remains available until collection.');
        }
        return back()->with('status','Layaway product recorded. Stock remains available until collection.');
    }

    public function storeSelectedLayawayItem(Request $request, TenantContext $tenant): RedirectResponse
    {
        $plan = LayawayPlan::query()->findOrFail($request->integer('layaway_plan_id'));

        return $this->storeLayawayItem($request, $plan, $tenant);
    }

    public function collectLayaway(Request $request, LayawayPlan $plan, TenantContext $tenant, PostCustomerLedgerEntryAction $ledger): RedirectResponse
    {
        $business=$this->business($request,$tenant); abort_unless($plan->business_id===$business->id && $plan->branch_id===$tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesConfirm,$business),403);
        if ($plan->status!=='ready_for_collection') return back()->withErrors(['layaway'=>'Layaway must be fully paid before collection.']);
        $plan->load(['items.unit','payments.payment']); if ($plan->items->isEmpty()) return back()->withErrors(['layaway'=>'Record at least one product before collection.']);
        $total=$plan->items->reduce(fn($sum,$item)=>bcadd($sum,bcmul((string)$item->quantity,(string)$item->target_unit_price,2),2),'0'); if (bccomp($total,(string)$plan->paid_amount,2)!==0) return back()->withErrors(['layaway'=>'Recorded products must total exactly the paid layaway amount.']);
        $sale=\Illuminate\Support\Facades\DB::transaction(function() use($plan,$business,$request,$ledger,$total){$seq=NumberSequence::query()->where('business_id',$business->id)->where('branch_id',$plan->branch_id)->where('type','sale')->lockForUpdate()->firstOrFail();$number=$seq->prefix.str_pad((string)$seq->next_number,$seq->padding,'0',STR_PAD_LEFT);$seq->increment('next_number');$sale=Sale::query()->create(['business_id'=>$business->id,'branch_id'=>$plan->branch_id,'customer_id'=>$plan->customer_id,'sale_number'=>$number,'status'=>SaleStatus::Confirmed,'payment_status'=>PaymentStatus::Paid,'fulfillment_status'=>FulfillmentStatus::OnHold,'sale_date'=>now()->toDateString(),'subtotal'=>$total,'total_amount'=>$total,'created_by'=>$request->user()->id,'confirmed_by'=>$request->user()->id,'confirmed_at'=>now()]);foreach($plan->items as $item){$cost=(string)(StockBalance::query()->where('branch_id',$plan->branch_id)->where('product_id',$item->product_id)->where('stock_status','available')->value('average_cost')??'0');$sale->items()->create(['business_id'=>$business->id,'product_id'=>$item->product_id,'product_unit_id'=>$item->product_unit_id,'quantity'=>$item->quantity,'conversion_factor'=>$item->unit->conversion_factor,'original_unit_price'=>$item->target_unit_price,'applied_unit_price'=>$item->target_unit_price,'cost_snapshot'=>$cost,'line_total'=>bcmul((string)$item->quantity,(string)$item->target_unit_price,2)]);}foreach($plan->payments as $row){$row->payment->allocations()->create(['business_id'=>$business->id,'sale_id'=>$sale->id,'amount'=>$row->amount]);}$ledger->execute($request->user(),$plan->customer,'sale',$total,'0',$plan->branch_id,$sale,$number,'Layaway collection '.$plan->plan_number);$plan->update(['status'=>'collected','completed_at'=>now()]);return $sale;});
        return redirect()->route('owner.sales.show',$sale)->with('status','Layaway collected. Confirm goods release to deduct stock.');
    }

    public function storeProject(Request $request, TenantContext $tenant): RedirectResponse
    {
        $business = $this->business($request, $tenant);
        abort_unless($request->user()->hasPermissionInBusiness(PermissionName::SalesCreate, $business), 403);
        $data = $request->validate(['customer_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:255'], 'location' => ['nullable', 'string', 'max:255'], 'contract_amount' => ['required', 'numeric', 'gt:0'], 'payment_mode' => ['required', 'in:completion,milestone'], 'start_date' => ['nullable', 'date'], 'completion_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $customer = Customer::query()->where('business_id', $business->id)->where('is_active', true)->findOrFail($data['customer_id']);
        $number = 'PRJ-'.now()->format('Ymd').'-'.str_pad((string) (ProjectContract::query()->where('business_id', $business->id)->max('id') + 1), 5, '0', STR_PAD_LEFT);
        ProjectContract::query()->create(['business_id' => $business->id, 'branch_id' => $tenant->branchId(), 'customer_id' => $customer->id, 'project_number' => $number, ...$data, 'created_by' => $request->user()->id]);
        return back()->with('status', 'Project contract created.');
    }

    public function storeRequest(Request $request, ProjectContract $project, TenantContext $tenant): RedirectResponse
    {
        $business = $this->business($request, $tenant);
        abort_unless($project->business_id === $business->id && $project->branch_id === $tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesCreate, $business), 403);
        $data = $request->validate(['title' => ['required', 'string', 'max:255'], 'amount' => ['required', 'numeric', 'gt:0'], 'requested_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $number = $project->project_number.'-REQ-'.str_pad((string) ($project->requests()->count() + 1), 2, '0', STR_PAD_LEFT);
        ProjectPaymentRequest::query()->create(['business_id' => $business->id, 'project_contract_id' => $project->id, 'request_number' => $number, ...$data]);
        return back()->with('status', 'Project payment request created.');
    }

    public function recordLayawayPayment(Request $request, LayawayPlan $plan, TenantContext $tenant, PostCustomerLedgerEntryAction $ledger): RedirectResponse
    {
        $business = $this->business($request, $tenant);
        abort_unless($plan->business_id === $business->id && $plan->branch_id === $tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::PaymentsCreate, $business), 403);
        $data = $request->validate(['payment_account_id'=>['required','integer'],'amount'=>['required','numeric','gt:0'],'external_reference'=>['nullable','string','max:255']]);
        $account = PaymentAccount::query()->with('method')->where('business_id',$business->id)->where('is_active',true)->where(fn($q)=>$q->where('branch_id',$plan->branch_id)->orWhereNull('branch_id'))->findOrFail($data['payment_account_id']);
        if ($account->method->requires_reference && blank($data['external_reference'] ?? null)) return back()->withErrors(['external_reference'=>'This payment method requires a reference.']);
        if (bccomp((string)$data['amount'], bcsub((string)$plan->target_amount,(string)$plan->paid_amount,2),2)>0) return back()->withErrors(['amount'=>'Payment exceeds the remaining layaway balance.']);
        $payment = Payment::query()->create(['business_id'=>$business->id,'branch_id'=>$plan->branch_id,'customer_id'=>$plan->customer_id,'payment_account_id'=>$account->id,'payment_number'=>'PAY-'.now()->format('Ymd').'-'.str_pad((string)(Payment::query()->where('business_id',$business->id)->count()+1),5,'0',STR_PAD_LEFT),'status'=>'confirmed','payment_date'=>now()->toDateString(),'amount'=>$data['amount'],'external_reference'=>$data['external_reference']??null,'received_by'=>$request->user()->id,'notes'=>'Layaway '.$plan->plan_number]);
        $plan->payments()->create(['business_id'=>$business->id,'payment_id'=>$payment->id,'amount'=>$data['amount']]); $paid=bcadd((string)$plan->paid_amount,(string)$data['amount'],2); $plan->update(['paid_amount'=>$paid,'status'=>bccomp($paid,(string)$plan->target_amount,2)>=0?'ready_for_collection':'active','completed_at'=>bccomp($paid,(string)$plan->target_amount,2)>=0?now():null]);
        $ledger->execute($request->user(),$plan->customer,'layaway_payment','0',(string)$data['amount'],$plan->branch_id,$payment,$payment->payment_number,'Layaway payment '.$plan->plan_number);
        return back()->with('status','Layaway payment recorded.');
    }

    public function increaseLayawayTarget(Request $request, LayawayPlan $plan, TenantContext $tenant): RedirectResponse
    {
        $business = $this->business($request, $tenant);
        abort_unless($plan->business_id === $business->id && $plan->branch_id === $tenant->branchId() && in_array($plan->status, ['active', 'ready_for_collection'], true) && $request->user()->hasPermissionInBusiness(PermissionName::SalesCreate, $business), 403);
        $data = $request->validate(['increase_amount' => ['required', 'numeric', 'gt:0']]);
        $oldTarget = $plan->target_amount;
        $newTarget = bcadd((string) $oldTarget, (string) $data['increase_amount'], 2);
        $plan->update(['target_amount' => $newTarget, 'status' => bccomp((string) $plan->paid_amount, $newTarget, 2) >= 0 ? 'ready_for_collection' : 'active', 'completed_at' => bccomp((string) $plan->paid_amount, $newTarget, 2) >= 0 ? now() : null]);
        AuditLog::query()->create(['user_id'=>$request->user()->id,'business_id'=>$business->id,'branch_id'=>$plan->branch_id,'action'=>'layaway.target_increased','subject_type'=>LayawayPlan::class,'subject_id'=>$plan->id,'old_values'=>['target_amount'=>$oldTarget],'new_values'=>['target_amount'=>$newTarget,'increase_amount'=>$data['increase_amount']]]);
        return back()->with('status', 'Layaway target amount increased.');
    }

    public function destroyLayaway(Request $request, LayawayPlan $plan, TenantContext $tenant): RedirectResponse
    {
        $business = $this->business($request, $tenant);
        abort_unless($plan->business_id === $business->id && $plan->branch_id === $tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesCreate, $business), 403);
        if ($plan->payments()->exists()) return back()->withErrors(['layaway' => 'This layaway has payments and cannot be deleted. Keep it for the customer payment history.']);
        $number = $plan->plan_number;
        \Illuminate\Support\Facades\DB::transaction(function () use ($plan, $business, $request, $number) {
            $plan->items()->delete();
            AuditLog::query()->create(['user_id'=>$request->user()->id,'business_id'=>$business->id,'branch_id'=>$plan->branch_id,'action'=>'layaway.deleted','subject_type'=>LayawayPlan::class,'subject_id'=>$plan->id,'old_values'=>['plan_number'=>$number]]);
            $plan->delete();
        });
        return redirect()->route('owner.layaway.index')->with('status', 'Layaway deleted.');
    }

    public function destroyProject(Request $request, ProjectContract $project, TenantContext $tenant): RedirectResponse
    {
        $business = $this->business($request, $tenant);
        abort_unless($project->business_id === $business->id && $project->branch_id === $tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesCreate, $business), 403);
        if ($project->payments()->exists() || $project->materialIssues()->exists()) return back()->withErrors(['project' => 'This project has payments or stock materials issued and cannot be deleted. Keep it for the financial and stock history.']);
        $number = $project->project_number;
        \Illuminate\Support\Facades\DB::transaction(function () use ($project, $business, $request, $number) {
            $project->requests()->delete();
            AuditLog::query()->create(['user_id'=>$request->user()->id,'business_id'=>$business->id,'branch_id'=>$project->branch_id,'action'=>'project.deleted','subject_type'=>ProjectContract::class,'subject_id'=>$project->id,'old_values'=>['project_number'=>$number]]);
            $project->delete();
        });
        return redirect()->route('owner.projects.index')->with('status', 'Project deleted.');
    }

    public function recordProjectPayment(Request $request, ProjectContract $project, TenantContext $tenant, PostCustomerLedgerEntryAction $ledger): RedirectResponse
    {
        $business = $this->business($request, $tenant);
        abort_unless($project->business_id === $business->id && $project->branch_id === $tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::PaymentsCreate, $business), 403);
        $data = $request->validate(['payment_account_id'=>['required','integer'],'project_payment_request_id'=>['nullable','integer'],'amount'=>['required','numeric','gt:0'],'external_reference'=>['nullable','string','max:255']]);
        $account = PaymentAccount::query()->with('method')->where('business_id',$business->id)->where('is_active',true)->where(fn($q)=>$q->where('branch_id',$project->branch_id)->orWhereNull('branch_id'))->findOrFail($data['payment_account_id']);
        if ($account->method->requires_reference && blank($data['external_reference'] ?? null)) return back()->withErrors(['external_reference'=>'This payment method requires a reference.']);
        $requestRecord = !empty($data['project_payment_request_id']) ? $project->requests()->findOrFail($data['project_payment_request_id']) : null;
        $remaining = bcsub((string)$project->contract_amount,(string)$project->paid_amount,2);
        if (bccomp((string)$data['amount'],$remaining,2)>0 || ($requestRecord && bccomp((string)$data['amount'],bcsub((string)$requestRecord->amount,(string)$requestRecord->paid_amount,2),2)>0)) return back()->withErrors(['amount'=>'Payment exceeds the remaining project or selected request balance.']);
        $payment = Payment::query()->create(['business_id'=>$business->id,'branch_id'=>$project->branch_id,'customer_id'=>$project->customer_id,'payment_account_id'=>$account->id,'payment_number'=>'PAY-'.now()->format('Ymd').'-'.str_pad((string)(Payment::query()->where('business_id',$business->id)->count()+1),5,'0',STR_PAD_LEFT),'status'=>'confirmed','payment_date'=>now()->toDateString(),'amount'=>$data['amount'],'external_reference'=>$data['external_reference']??null,'received_by'=>$request->user()->id,'notes'=>'Project '.$project->project_number]);
        ProjectPayment::query()->create(['business_id'=>$business->id,'project_contract_id'=>$project->id,'project_payment_request_id'=>$requestRecord?->id,'payment_id'=>$payment->id,'amount'=>$data['amount']]);
        $paid = bcadd((string)$project->paid_amount,(string)$data['amount'],2); $project->update(['paid_amount'=>$paid,'status'=>bccomp($paid,(string)$project->contract_amount,2)>=0?'paid':'active']);
        if ($requestRecord) { $requestPaid=bcadd((string)$requestRecord->paid_amount,(string)$data['amount'],2); $requestRecord->update(['paid_amount'=>$requestPaid,'status'=>bccomp($requestPaid,(string)$requestRecord->amount,2)>=0?'paid':'partially_paid']); }
        $ledger->execute($request->user(),$project->customer,'project_payment','0',(string)$data['amount'],$project->branch_id,$payment,$payment->payment_number,'Project payment '.$project->project_number);
        return back()->with('status','Project payment recorded.');
    }

    public function issueMaterial(Request $request, ProjectContract $project, TenantContext $tenant, ApplyStockMovementAction $movement): RedirectResponse
    {
        $business=$this->business($request,$tenant); abort_unless($project->business_id===$business->id && $project->branch_id===$tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesRelease,$business),403);
        $data=$request->validate(['product_unit_id'=>['nullable','integer'],'quantity'=>['nullable','numeric','gt:0'],'items'=>['nullable','array','min:1'],'items.*.product_unit_id'=>['required_with:items','integer'],'items.*.quantity'=>['required_with:items','numeric','gt:0'],'notes'=>['nullable','string','max:2000']]);
        $lines = $data['items'] ?? [['product_unit_id'=>$data['product_unit_id'],'quantity'=>$data['quantity']]];
        $number=$project->project_number.'-MAT-'.str_pad((string)($project->materialIssues()->max('id')+1),3,'0',STR_PAD_LEFT);
        $issue=ProjectMaterialIssue::query()->create(['business_id'=>$business->id,'branch_id'=>$project->branch_id,'project_contract_id'=>$project->id,'issue_number'=>$number,'status'=>'confirmed','notes'=>$data['notes']??null,'created_by'=>$request->user()->id,'confirmed_by'=>$request->user()->id,'confirmed_at'=>now()]);
        foreach ($lines as $line) { $unit=ProductUnit::query()->with('product')->where('business_id',$business->id)->where('is_active',true)->findOrFail($line['product_unit_id']); $base=bcmul((string)$line['quantity'],(string)$unit->conversion_factor,4); $cost=(string)(StockBalance::query()->where('branch_id',$project->branch_id)->where('product_id',$unit->product_id)->where('stock_status','available')->value('average_cost')??'0'); $issue->items()->create(['business_id'=>$business->id,'product_id'=>$unit->product_id,'product_unit_id'=>$unit->id,'quantity'=>$line['quantity'],'conversion_factor'=>$unit->conversion_factor,'unit_cost'=>$cost]); $movement->execute($request->user(),$project->branch,$unit->product,StockStatus::Available,StockMovementType::AdjustmentOut,bcmul($base,'-1',4),'Project material issue '.$number,$issue,$cost); }
        return back()->with('status','Project material issued and stock deducted.');
    }

    public function completeProject(Request $request, ProjectContract $project, TenantContext $tenant): RedirectResponse
    {
        $business=$this->business($request,$tenant); abort_unless($project->business_id===$business->id && $project->branch_id===$tenant->branchId() && $request->user()->hasPermissionInBusiness(PermissionName::SalesConfirm,$business),403);
        if ($project->status!=='active') return back()->withErrors(['project'=>'Only active projects can be completed.']);
        $balance=bcsub((string)$project->contract_amount,(string)$project->paid_amount,2);
        if ($project->payment_mode==='completion' && bccomp($balance,'0',2)>0 && !$project->requests()->where('title','Final completion payment')->exists()) { $number=$project->project_number.'-REQ-'.str_pad((string)($project->requests()->max('id')+1),2,'0',STR_PAD_LEFT); $project->requests()->create(['business_id'=>$business->id,'request_number'=>$number,'title'=>'Final completion payment','amount'=>$balance,'requested_date'=>now()->toDateString(),'status'=>'requested']); }
        $project->update(['status'=>bccomp($balance,'0',2)>0?'completed_pending_payment':'completed','completion_date'=>now()->toDateString()]);
        return back()->with('status','Project completed. Final payment status updated.');
    }
}
