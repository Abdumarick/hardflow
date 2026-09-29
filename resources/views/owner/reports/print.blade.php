<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('reports.document_title', ['section' => __('reports.'.$section), 'business' => $business->name]) }}</title>
    <style>
        @page { size: A4; margin: 16mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #0f172a; font-family: Arial, sans-serif; font-size: 12px; }
        header { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 18px; border-bottom: 2px solid #1d4ed8; }
        h1 { margin: 0; font-size: 26px; }
        h2 { margin: 6px 0 10px; font-size: 15px; }
        p { margin: 3px 0; color: #475569; }
        .platform { color: #64748b; font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .meta { text-align: right; }
        .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 18px; }
        .card { padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; }
        .card small { color: #64748b; text-transform: uppercase; }
        .card strong { display: block; margin-top: 7px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 9px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background: #eff6ff; color: #1e40af; font-size: 10px; text-transform: uppercase; }
        td:last-child, th:last-child { text-align: right; }
        footer { margin-top: 28px; padding-top: 10px; border-top: 1px solid #cbd5e1; color: #64748b; }
        .actions { margin: 0 auto 16px; max-width: 210mm; padding: 10px; background: #eff6ff; text-align: center; }
        button { border: 0; border-radius: 7px; background: #1d4ed8; color: white; padding: 9px 18px; font-weight: bold; cursor: pointer; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    @unless($pdf ?? false)<div class="actions"><button onclick="window.print()">{{ __('reports.print_save_pdf') }}</button></div>@endunless
    <header>
        <div><h1>{{ $business->name }}</h1><p class="platform">HardFlow</p><h2>{{ $report['title'] }}</h2><p>{{ $branch->name }}</p></div>
        <div class="meta"><strong>{{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}</strong><p>{{ __('reports.generated') }} {{ now()->format('d M Y, H:i') }}</p><p>{{ __('reports.currency') }}: {{ $business->currency }}</p></div>
    </header>
    <section class="cards">
        @foreach($report['cards'] as $card)
            <div class="card"><small>{{ $card[0] }}</small><strong>{{ ($card[2] ?? true) ? $business->currency.' ' : '' }}{{ number_format((float)$card[1], 2) }}{{ $card[3] ?? '' }}</strong></div>
        @endforeach
    </section>
    <h2>{{ __('reports.details') }}</h2>
    <table>
        <thead><tr><th>{{ __('reports.group') }}</th><th>{{ __('reports.value') }}</th></tr></thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr><td>{{ ucfirst($row->label) }}</td><td>{{ ($report['row_money'] ?? true) ? $business->currency.' ' : '' }}{{ number_format((float)$row->amount, 2) }}{{ $report['row_suffix'] ?? '' }}</td></tr>
            @empty
                <tr><td colspan="2">{{ __('reports.print_empty') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    <footer>{{ __('reports.print_evidence') }}</footer>
</body>
</html>
