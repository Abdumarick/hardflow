<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <title>Access denied | {{ config('app.name', 'HardFlow') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-950 antialiased">
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-6 py-12">
        <section class="w-full rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-xl shadow-slate-200/50 sm:p-10">
            <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-amber-100 text-2xl font-black text-amber-700">!</span>
            <p class="mt-6 text-xs font-bold uppercase tracking-[.2em] text-amber-700">Access restricted</p>
            <h1 class="mt-3 text-3xl font-black tracking-tight">You do not have access to this page.</h1>
            <p class="mt-4 text-sm leading-6 text-slate-600">{{ $message }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-800">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl bg-blue-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-blue-800">Go to Login</a>
                @endauth
                <button type="button" onclick="history.back()" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Go Back</button>
            </div>
        </section>
    </main>
</body>
</html>
