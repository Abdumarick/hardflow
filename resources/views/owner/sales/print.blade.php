<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->sale_number }}</title>
    <style>body{font-family:Arial,sans-serif;color:#0f172a;max-width:850px;margin:40px auto;padding:24px}header{display:flex;justify-content:space-between;border-bottom:3px solid #1d4ed8;padding-bottom:20px}table{width:100%;border-collapse:collapse;margin-top:28px}th,td{padding:12px;border-bottom:1px solid #e2e8f0;text-align:left}.right{text-align:right}@media print{button{display:none}body{margin:0}}</style>
</head>
<body>
    @php($documentBusiness = $document->branch->business)
    <header><div><h1>HardFlow</h1><p>{{ $documentBusiness->name }}</p></div><div class="right"><h2>{{ $kind }}</h2><strong>{{ $document->sale_number }}</strong><p>{{ $document->sale_date->format('d M Y') }}</p></div></header>
    <section><h3>{{ __('sales.customer') }}</h3><p>{{ $document->customer?->name ?? $document->walk_in_name ?? __('sales.walk_in_customer_lower') }}<br>{{ $document->customer?->phone ?? $document->walk_in_phone }}</p></section>
    <table>
        <thead><tr><th>{{ __('sales.item') }}</th><th>{{ __('sales.qty') }}</th><th>{{ __('sales.unit_price') }}</th><th class="right">{{ __('sales.total') }}</th></tr></thead>
        <tbody>@foreach($document->items as $item)<tr><td>{{ $item->product->name }}</td><td>{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }} {{ $item->productUnit->unit->symbol }}</td><td>{{ number_format((float) $item->applied_unit_price, 2) }}</td><td class="right">{{ number_format((float) $item->line_total, 2) }}</td></tr>@endforeach</tbody>
    </table>
    <h2 class="right">{{ __('sales.total') }}: {{ $documentBusiness->currency }} {{ number_format((float) $document->total_amount, 2) }}</h2>
    <button onclick="window.print()">{{ __('sales.print') }}</button>
</body>
</html>
