<!-- Top Bar -->
<div class="flex items-center justify-between w-full gap-4">
    <!-- Left: Toggle sidebar + Page title -->
    <div class="flex items-center gap-4 min-w-0">
        <button @click="sidebarOpen = !sidebarOpen" class="text-slate-400 hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-800 shrink-0">
            <i class="fa-solid fa-bars text-lg"></i>
        </button>
        <div class="min-w-0">
            <h2 id="pageTitle" class="text-base font-bold text-white flex items-center gap-2 truncate">
                @yield('title', 'SIMLAB')
            </h2>
            <p id="pageSubtitle" class="text-xs text-slate-400 truncate">
                @yield('subtitle', '')
            </p>
        </div>
    </div>

    <!-- Right: Quick actions -->
    <div class="flex items-center gap-3 shrink-0">
        <!-- Theme Toggle Button -->
        <button x-data="{ dark: document.documentElement.classList.contains('dark') }"
                @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('simlab-theme', dark ? 'dark' : 'light')"
                class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400 hover:text-amber-300 border border-slate-700 transition-all flex items-center justify-center shadow-sm"
                title="Ubah Mode Terang / Gelap">
            <i :class="dark ? 'fa-solid fa-sun text-sm' : 'fa-solid fa-moon text-sm'" class="fa-solid fa-sun text-sm"></i>
        </button>

        <!-- Live Clock -->
        <div class="hidden md:flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-xs font-mono text-slate-300"
                  x-data="{ now: new Date().toLocaleTimeString('id-ID') + ' WIB' }"
                  x-init="setInterval(() => now = new Date().toLocaleTimeString('id-ID') + ' WIB', 1000)"
                  x-text="now">00:00:00 WIB</span>
        </div>

        <!-- Lapor Kendala -->
        <button class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-semibold px-3 py-2 rounded-lg flex items-center gap-1.5 transition-all cursor-not-allowed opacity-80" title="Coming di Fase 4">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span class="hidden sm:inline">Lapor Kendala</span>
        </button>

        @if (in_array(auth()->user()->role, ['super_admin', 'teknisi']))
            <a href="{{ route('assets.create') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-3 py-2 rounded-lg flex items-center gap-1.5 transition-all shadow-lg shadow-blue-600/20">
                <i class="fa-solid fa-plus"></i>
                <span class="hidden sm:inline">Tambah Aset</span>
            </a>
        @endif
    </div>
</div>