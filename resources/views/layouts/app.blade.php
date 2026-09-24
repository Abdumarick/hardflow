<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>if(localStorage.getItem('hardflow-theme')==='dark'||(!localStorage.getItem('hardflow-theme')&&matchMedia('(prefers-color-scheme: dark)').matches)){document.documentElement.classList.add('dark')}</script>

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
        <div x-data="{ mobileSidebarOpen: false, sidebarCollapsed: localStorage.getItem('hardflow-sidebar-collapsed') === 'true', dark: document.documentElement.classList.contains('dark') }" @theme-toggle.window="dark=!dark; document.documentElement.classList.toggle('dark',dark); localStorage.setItem('hardflow-theme',dark?'dark':'light')" class="min-h-screen bg-slate-50">
            <livewire:layout.navigation />
            <div :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'" class="min-h-screen pt-16 transition-all duration-200">
                @if (isset($header))<header class="border-b border-slate-200 bg-white"><div class="mx-auto max-w-screen-2xl px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div></header>@endif
                <main class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">{{ $slot }}</main>
            </div>
        </div>
    </body>
</html>
