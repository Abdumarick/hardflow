<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <meta name="description" content="Sign in securely to your HardFlow workspace.">
    <title>{{ config('app.name', 'HardFlow') }} — Sign in</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>[x-cloak]{display:none!important}</style>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-950 antialiased">
    <main class="grid min-h-screen lg:grid-cols-[1.08fr_.92fr]">
        <aside class="relative hidden overflow-hidden bg-gradient-to-br from-blue-800 via-blue-700 to-blue-400 p-12 text-white lg:flex lg:flex-col lg:justify-between" aria-label="About HardFlow">
            <div class="absolute inset-0 opacity-20 [background-image:radial-gradient(rgba(255,255,255,.55)_1px,transparent_1px)] [background-size:38px_38px]"></div>
            <div class="absolute -bottom-36 -right-28 size-[34rem] rounded-full bg-white/20 blur-3xl"></div>
            <a href="{{ url('/') }}" class="relative flex items-center gap-3">
                <img src="{{ asset('images/hardflow-logo.png') }}" alt="HardFlow" class="size-12 rounded-xl shadow-xl">
                <span class="text-3xl font-extrabold tracking-tight">HardFlow</span>
            </a>
            <div class="relative max-w-2xl">
                <p class="text-sm font-bold uppercase tracking-[.2em] text-blue-100">Hardware business management</p>
                <h1 class="mt-5 text-5xl font-extrabold leading-[1.08] tracking-[-.035em] xl:text-6xl">Run your hardware business smarter.</h1>
                <p class="mt-6 max-w-xl text-xl leading-9 text-blue-100">Sales, inventory, purchases, expenses, and team management—all together in one secure workspace.</p>
                <div class="mt-10 grid grid-cols-3 gap-4">
                    @foreach ([['12+', 'Modules'], ['6', 'User roles'], ['100%', 'Local currency']] as [$value, $label])
                        <div class="rounded-2xl border border-white/25 bg-white/10 p-5 backdrop-blur-sm"><p class="text-3xl font-extrabold">{{ $value }}</p><p class="mt-1 text-sm text-blue-100">{{ $label }}</p></div>
                    @endforeach
                </div>
            </div>
            <p class="relative text-sm text-blue-100">© {{ date('Y') }} HardFlow. All rights reserved.</p>
        </aside>

        <section class="relative flex min-h-screen items-center justify-center bg-[#f8fafe] px-6 py-12 sm:px-10">
            <a href="{{ url('/') }}" class="absolute left-6 top-6 flex items-center gap-2 lg:hidden" aria-label="HardFlow home">
                <img src="{{ asset('images/hardflow-logo.png') }}" alt="HardFlow" class="size-9 rounded-lg shadow-sm">
                <span class="text-xl font-extrabold">HardFlow</span>
            </a>
            <div class="w-full max-w-[480px]">{{ $slot }}</div>
        </section>
    </main>
    @livewireScripts
</body>
</html>
