<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SIMLAB') }}</title>

        <!-- Theme init: terapkan preferensi tersimpan sebelum render (hindari FOUC) -->
        <script>
            (function () {
                var theme = localStorage.getItem('simlab-theme') || 'dark';
                document.documentElement.classList.toggle('dark', theme === 'dark');
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-slate-950 text-slate-100 flex overflow-hidden custom-scrollbar" x-data="{ sidebarOpen: true }">
        <!-- Sidebar -->
        <aside x-show="sidebarOpen" x-cloak
               id="sidebar"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col justify-between shrink-0 transition-all duration-300 z-30">
            @include('layouts.navigation')
        </aside>

        <!-- Main Content Container -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-slate-950 min-w-0">
            <!-- Top Navbar -->
            <header class="h-16 border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-md px-6 flex items-center justify-between shrink-0 z-20">
                @include('layouts.topbar')
            </header>

            <!-- View Container -->
            <div id="viewContainer" class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
                {{ $slot }}
            </div>
        </main>

        @stack('scripts')
    </body>
</html>