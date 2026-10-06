<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            (() => {
                const storageKey = 'hardflow-theme';
                const readTheme = () => {
                    try {
                        const savedTheme = localStorage.getItem(storageKey);
                        if (savedTheme === 'dark' || savedTheme === 'light') return savedTheme;
                    } catch (_) {}

                    return matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                };
                const applyTheme = (theme) => {
                    const dark = theme === 'dark';
                    document.documentElement.classList.toggle('dark', dark);
                    document.documentElement.style.colorScheme = theme;
                    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
                        button.setAttribute('aria-pressed', String(dark));
                        button.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
                        button.setAttribute('title', dark ? 'Use light mode' : 'Use dark mode');
                        const icon = button.querySelector('[data-theme-icon]');
                        if (icon) icon.textContent = dark ? '☀' : '◐';
                    });
                };

                window.hardflowToggleTheme = () => {
                    const theme = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
                    try { localStorage.setItem(storageKey, theme); } catch (_) {}
                    applyTheme(theme);
                };
                window.hardflowApplyTheme = () => applyTheme(readTheme());
                applyTheme(readTheme());
                document.addEventListener('DOMContentLoaded', window.hardflowApplyTheme);
                document.addEventListener('livewire:navigated', window.hardflowApplyTheme);
            })();
        </script>
        <script>
            (() => {
                window.hardflowSetMobileNavigation = (open) => {
                    const drawer = document.querySelector('[data-mobile-navigation]');
                    const backdrop = document.querySelector('[data-mobile-navigation-backdrop]');
                    const shouldOpen = Boolean(open) && window.innerWidth < 1024;

                    if (drawer) drawer.style.transform = shouldOpen ? 'translateX(0)' : '';
                    if (backdrop) backdrop.style.display = shouldOpen ? 'block' : 'none';
                    document.body.style.overflow = shouldOpen ? 'hidden' : '';
                };

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') window.hardflowSetMobileNavigation(false);
                });
                document.addEventListener('livewire:navigating', () => window.hardflowSetMobileNavigation(false));
                document.addEventListener('livewire:navigated', () => window.hardflowSetMobileNavigation(false));
                window.addEventListener('resize', () => window.hardflowSetMobileNavigation(false));
            })();
        </script>

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <style>[x-cloak]{display:none!important}</style>
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
        <div
            x-data="{ sidebarCollapsed: localStorage.getItem('hardflow-sidebar-collapsed') === 'true' }"
            class="min-h-screen bg-slate-50"
        >
            <livewire:layout.navigation />
            <div :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-64'" class="min-h-screen pt-16 transition-all duration-200">
                @if (isset($header))<header class="border-b border-slate-200 bg-white"><div class="mx-auto max-w-screen-2xl px-4 py-5 sm:px-6 lg:px-8">{{ $header }}</div></header>@endif
                <main class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">{{ $slot }}</main>
            </div>
        </div>
        @livewireScripts
    </body>
</html>
