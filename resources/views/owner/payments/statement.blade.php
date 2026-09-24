<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ __('payments.statement_title', ['customer' => $customer->name]) }}</title>
    <style>body{font-family:Arial,sans-serif;color:#0f172a;margin:36px}.head{display:flex;justify-content:space-between;border-bottom:2px solid #1d4ed8;padding-bottom:18px}.muted{color:#64748b;font-size:12px}table{width:100%;border-collapse:collapse;margin-top:24px;font-size:13px}th,td{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left}.num{text-align:right}.balance{font-weight:700}.print{margin-top:24px}@media print{.print{display:none}}</style>
</head>
<body>
    <div class="head"><div><h1 style="margin:0;color:#1d4ed8">HardFlow</h1><p class="muted">{{ $business->name }} · {{ __('payments.customer_statement') }}</p></div><div style="text-align:right"><h2 style="margin:0">{{ $customer->name }}</h2><p class="muted">{{ $customer->phone }}<br>{{ $customer->email }}</p></div></div>
    <p class="muted">{{ __('payments.generated', ['date' => now()->format('d M Y H:i')]) }}</p>
    @php($running = '0')
    <table>
        <thead><tr><th>{{ __('payments.date') }}</th><th>{{ __('payments.reference') }}</th><th>{{ __('payments.description') }}</th><th class="num">{{ __('payments.debit') }}</th><th class="num">{{ __('payments.credit') }}</th><th class="num">{{ __('payments.balance') }}</th></tr></thead>
        <tbody>@forelse($entries as $entry)@php($running = bcsub(bcadd($running, (string) $entry->debit, 2), (string) $entry->credit, 2))<tr><td>{{ $entry->occurred_at->format('d M Y') }}</td><td>{{ $entry->reference }}</td><td>{{ $entry->description }}</td><td class="num">{{ (float) $entry->debit > 0 ? number_format((float) $entry->debit, 2) : '—' }}</td><td class="num">{{ (float) $entry->credit > 0 ? number_format((float) $entry->credit, 2) : '—' }}</td><td class="num balance">{{ number_format((float) $running, 2) }}</td></tr>@empty<tr><td colspan="6" style="text-align:center">{{ __('payments.no_ledger_entries') }}</td></tr>@endforelse</tbody>
        <tfoot><tr><th colspan="5" class="num">{{ __('payments.outstanding_balance') }}</th><th class="num">{{ $business->currency }} {{ number_format((float) $running, 2) }}</th></tr></tfoot>
    </table>
    <button class="print" onclick="window.print()">{{ __('payments.print_statement') }}</button>
</body>
</html>
