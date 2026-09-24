<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $payment->payment_number }}</title>
    <style>body{background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a}.receipt{max-width:390px;margin:28px auto;background:white;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;box-shadow:0 20px 45px #0f172a18}.success{padding:16px;background:#ecfdf5;border-bottom:1px solid #d1fae5}.body{padding:20px}.center{text-align:center}.muted{color:#64748b;font-size:12px}.row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed #cbd5e1;font-size:13px}.total{font-size:17px;font-weight:bold}.actions{display:flex;gap:8px;padding:0 20px 20px}.actions button,.actions a{flex:1;padding:10px;border-radius:8px;border:1px solid #cbd5e1;background:white;text-align:center;text-decoration:none;color:#0f172a;font-size:12px;font-weight:bold}@media print{body{background:white}.receipt{box-shadow:none;margin:0 auto}.actions{display:none}}</style>
</head>
<body>
    <div class="receipt">
        <div class="success"><strong>✓ {{ __('payments.payment_complete') }}</strong><div class="muted">{{ $payment->payment_number }}</div></div>
        <div class="body">
            <div class="center"><h2 style="margin:0">{{ $business->name }}</h2><p class="muted">{{ $branch?->name }}<br>{{ now()->format('d/m/Y H:i') }}</p></div>
            <div class="row"><span>{{ __('payments.customer') }}</span><strong>{{ $payment->customer?->name ?? $payment->allocations->first()?->sale?->walk_in_name ?? __('payments.walk_in_customer') }}</strong></div>
            <div class="row"><span>{{ __('payments.method') }}</span><strong>{{ $payment->account->method->name }}</strong></div>
            <div class="row"><span>{{ __('payments.account') }}</span><strong>{{ $payment->account->name }}</strong></div>
            @foreach($payment->allocations as $allocation)
                <div class="row"><span>{{ __('payments.invoice_number', ['number' => $allocation->sale->sale_number]) }}</span><strong>{{ $business->currency }} {{ number_format((float) $allocation->amount, 2) }}</strong></div>
            @endforeach
            <div class="row total"><span>{{ __('payments.paid') }}</span><span>{{ $business->currency }} {{ number_format((float) $payment->amount, 2) }}</span></div>
            <p class="center muted">{{ $payment->external_reference ?: __('payments.internal_cash_receipt') }}<br><br>{{ __('payments.thank_you') }}<br>{{ __('payments.powered_by') }}</p>
        </div>
        <div class="actions"><button onclick="window.print()">{{ __('payments.print') }}</button><a href="{{ route('owner.payments.index') }}">{{ __('payments.done') }}</a></div>
    </div>
</body>
</html>
