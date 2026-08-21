<!-- Sidebar Navigation -->
<div class="flex flex-col h-full">
    <!-- App Brand Header -->
    <div class="p-5 flex items-center gap-3 border-b border-slate-800/80">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/20 shrink-0">
            <i class="fa-solid fa-desktop text-lg"></i>
        </div>
        <div class="min-w-0">
            <h1 class="font-bold text-lg text-white tracking-wide flex items-center gap-1.5">
                SIMLAB <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30">v2.0</span>
            </h1>
            <p class="text-xs text-slate-400">Lab Management System</p>
        </div>
    </div>

    <!-- Lab Selection Dropdown -->
    <div class="px-4 py-3 border-b border-slate-800/60">
        <label class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block mb-1.5">Pilih Ruang Lab</label>
        <div class="relative">
            <select onchange="window.location.href='{{ route('dashboard') }}?lab_id=' + (this.value || '')" class="w-full bg-slate-800 text-slate-200 text-sm font-medium rounded-lg border border-slate-700 px-3 py-2 appearance-none focus:outline-none focus:border-blue-500 transition-colors">
                <option value="" {{ request('lab_id') ? '' : 'selected' }}>💻 Semua Lab ({{ $labs->sum('assets_count') }} PC)</option>
                @foreach ($labs as $lab)
                    <option value="{{ $lab->id }}" {{ (string) request('lab_id') === (string) $lab->id ? 'selected' : '' }}>
                        💻 {{ str_replace('Laboratorium Komputer', 'Lab', $lab->name) }} ({{ $lab->assets_count }} PC)
                    </option>
                @endforeach
            </select>
            <i class="fa-solid fa-chevron-down absolute right-3 top-3 text-slate-400 text-xs pointer-events-none"></i>
        </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto custom-scrollbar">
        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
            <i class="fa-solid fa-chart-pie w-5 text-center text-base"></i>
            <span>Dashboard Utama</span>
        </x-nav-link>

        <x-nav-link :href="route('assets.index')" :active="request()->routeIs('assets.*')">
            <i class="fa-solid fa-hard-drive w-5 text-center text-base"></i>
            <span>Inventaris Aset & PC</span>
        </x-nav-link>

        <x-nav-link :href="route('seatmap.index')" :active="request()->routeIs('seatmap.*')">
            <i class="fa-solid fa-border-all w-5 text-center text-base"></i>
            <span>Seat Mapping & User</span>
        </x-nav-link>

        <x-nav-link :href="route('schedules.index')" :active="request()->routeIs('schedules.*')">
            <i class="fa-solid fa-calendar-days w-5 text-center text-base"></i>
            <span>Jadwal & Booking Lab</span>
        </x-nav-link>

        <x-nav-link :href="route('tickets.index')" :active="request()->routeIs('tickets.*')">
            <i class="fa-solid fa-ticket-simple w-5 text-center text-base"></i>
            <span>Helpdesk & Servis</span>
        </x-nav-link>

        <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 transition-all cursor-not-allowed opacity-60" title="Coming di Fase 6">
            <i class="fa-solid fa-file-invoice w-5 text-center text-base"></i>
            <span class="flex-1">Laporan & Audit</span>
            <span class="text-[8px] px-1 py-0.5 rounded bg-slate-800 text-slate-500">F6</span>
        </a>
    </nav>

    <!-- User Profile Card in Sidebar -->
    <div class="p-3 m-3 rounded-xl bg-slate-800/50 border border-slate-800 flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold text-sm border border-emerald-500/30 shrink-0">
            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-200 truncate">{{ Auth::user()->name }}</p>
            <p class="text-xs text-slate-400 truncate">
                @switch(Auth::user()->role)
                    @case('super_admin') Super Admin @break
                    @case('teknisi') Laboran Utama @break
                    @case('instruktur') Instruktur @break
                    @default Siswa
                @endswitch
            </p>
        </div>
        <form method="POST" action="{{ route('logout') }}" title="Logout">
            @csrf
            <button type="submit" class="text-slate-400 hover:text-slate-200 p-1">
                <i class="fa-solid fa-right-from-bracket text-sm"></i>
            </button>
        </form>
    </div>
</div>
