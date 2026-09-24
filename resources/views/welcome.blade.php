<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <meta name="description" content="HardFlow brings sales, inventory, purchasing, expenses, and team management together for growing hardware businesses.">
    <title>HardFlow — Hardware business management, simplified</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#f7f9fd] font-sans text-slate-950 antialiased">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_82%_16%,rgba(57,101,220,.18),transparent_28%),radial-gradient(circle_at_18%_88%,rgba(245,172,30,.12),transparent_24%)]"></div>

        <header class="relative z-10 mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-10">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="HardFlow home">
                <img src="{{ asset('images/hardflow-logo.png') }}" alt="HardFlow" class="size-11 rounded-xl shadow-lg shadow-blue-700/20">
                <span class="text-2xl font-extrabold tracking-tight">HardFlow</span>
            </a>
            <a href="{{ route('login') }}" wire:navigate class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-700/20 transition hover:-translate-y-0.5 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                Sign in <span aria-hidden="true">→</span>
            </a>
        </header>

        <main class="relative z-10 mx-auto grid max-w-7xl items-center gap-14 px-6 pb-16 pt-12 lg:min-h-[calc(100vh-104px)] lg:grid-cols-[1.05fr_.95fr] lg:px-10 lg:pb-24 lg:pt-8">
            <section>
                <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-blue-200 bg-white/80 px-4 py-2 text-xs font-bold uppercase tracking-[.18em] text-blue-700 shadow-sm backdrop-blur">
                    <span class="size-2 rounded-full bg-amber-400"></span>
                    Hardware business management
                </div>
                <h1 class="max-w-3xl text-5xl font-extrabold leading-[1.03] tracking-[-.045em] sm:text-6xl lg:text-7xl">
                    Run every part of your business <span class="text-blue-700">with clarity.</span>
                </h1>
                <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-600 sm:text-xl">
                    Keep sales, stock, purchases, expenses, and your team in one dependable workspace—built for modern hardware businesses.
                </p>
                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center justify-center gap-3 rounded-2xl bg-blue-700 px-7 py-4 font-bold text-white shadow-xl shadow-blue-700/25 transition hover:-translate-y-0.5 hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                        Open your workspace
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="#platform" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-7 py-4 font-bold text-slate-700 shadow-sm transition hover:border-blue-200 hover:text-blue-700">Explore the platform</a>
                </div>
                <div class="mt-10 flex flex-wrap gap-x-7 gap-y-3 text-sm font-medium text-slate-600">
                    <span class="flex items-center gap-2"><span class="text-blue-700">✓</span> Multi-branch ready</span>
                    <span class="flex items-center gap-2"><span class="text-blue-700">✓</span> Role-based access</span>
                    <span class="flex items-center gap-2"><span class="text-blue-700">✓</span> Built for local operations</span>
                </div>
            </section>

            <section id="platform" class="relative lg:pl-8" aria-label="HardFlow platform highlights">
                <div class="absolute -inset-8 -z-10 rounded-full bg-blue-300/20 blur-3xl"></div>
                <div class="overflow-hidden rounded-[2rem] border border-white/80 bg-white/90 p-4 shadow-2xl shadow-blue-950/15 backdrop-blur sm:p-6">
                    <div class="rounded-[1.4rem] bg-gradient-to-br from-blue-800 via-blue-700 to-blue-500 p-6 text-white sm:p-8">
                        <div class="flex items-center justify-between">
                            <div><p class="text-xs font-bold uppercase tracking-[.18em] text-blue-200">Business overview</p><p class="mt-2 text-2xl font-bold">Everything is in flow.</p></div>
                            <span class="rounded-full bg-emerald-400/20 px-3 py-1.5 text-xs font-semibold text-emerald-100">● Live</span>
                        </div>
                        <div class="mt-8 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4"><p class="text-sm text-blue-100">Sales</p><p class="mt-2 text-2xl font-bold">Fast</p><p class="mt-1 text-xs text-blue-200">From quote to receipt</p></div>
                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4"><p class="text-sm text-blue-100">Inventory</p><p class="mt-2 text-2xl font-bold">Accurate</p><p class="mt-1 text-xs text-blue-200">Across every branch</p></div>
                        </div>
                    </div>
                    <div class="grid gap-3 pt-4 sm:grid-cols-3">
                        @foreach ([['12+', 'Connected modules'], ['6', 'Purpose-built roles'], ['TZS', 'Local currency']] as [$value, $label])
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4"><p class="text-xl font-extrabold text-slate-950">{{ $value }}</p><p class="mt-1 text-xs font-medium text-slate-500">{{ $label }}</p></div>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 border-t border-slate-200/80 px-6 py-6 text-center text-sm text-slate-500">© {{ date('Y') }} HardFlow. Smarter operations for growing hardware businesses.</footer>
    </div>
</body>
</html>
