<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-100 bg-slate-950">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-slate-950 px-4">
            <div class="mb-6 flex flex-col items-center">
                <div class="flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 shadow-lg shadow-blue-600/30 mb-3">
                    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-100">SIM<span class="text-blue-500">LAB</span></h1>
                <p class="text-sm text-slate-500 mt-1">Sistem Pengelolaan Laboratorium Komputer</p>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-slate-900/70 backdrop-blur border border-white/8 shadow-xl sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>