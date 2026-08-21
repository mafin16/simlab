<x-app-layout>
    @section('title', 'Seat Mapping & User')
    @section('subtitle', 'Denah interaktif penggunaan PC dan presensi siswa secara live')

    @php
        $labOptions = $labs->mapWithKeys(fn ($lab) => [$lab->id => str_replace('Laboratorium Komputer', 'Lab', $lab->name)])->all();
    @endphp

    <div class="space-y-4"
         x-data='{
            seats: @json($seats),
            serverTime: @json(now()->format("H:i:s")),
            selected: null,
            endpoint: @json(route("seatmap.status", ["lab_id" => $selectedLab?->id])),
            init() {
                setInterval(() => this.refresh(), 10000);
            },
            async refresh() {
                try {
                    const res = await fetch(this.endpoint, { headers: { "Accept": "application/json" } });
                    const data = await res.json();
                    this.seats = data.seats;
                    this.serverTime = data.server_time;
                    if (this.selected) {
                        this.selected = this.seats.find(s => s.id === this.selected.id) ?? null;
                    }
                } catch (e) {}
            },
            cardClass(seat) {
                if (seat.presence) return "border-blue-500/60 bg-blue-500/10";
                return {
                    "Ready": "border-emerald-500/30 bg-emerald-500/5 hover:border-emerald-500/70",
                    "Degraded": "border-amber-500/40 bg-amber-500/5 hover:border-amber-500/70",
                    "Maintenance": "border-red-500/40 bg-red-500/5 hover:border-red-500/70",
                    "Scrapped": "border-slate-800 bg-slate-900/60 opacity-60",
                }[seat.status] ?? "border-emerald-500/30 bg-emerald-500/5 hover:border-emerald-500/70";
            },
            statusLabel(seat) {
                if (seat.presence) return "Terisi";
                return {
                    "Ready": "Ready", "Degraded": "Degraded", "Maintenance": "Maintenance", "Scrapped": "Scrapped",
                }[seat.status] ?? seat.status;
            },
            statusIcon(seat) {
                if (seat.presence) return "fa-user";
                return {
                    "Ready": "fa-circle-check", "Degraded": "fa-triangle-exclamation",
                    "Maintenance": "fa-screwdriver-wrench", "Scrapped": "fa-ban",
                }[seat.status] ?? "fa-circle-check";
            },
            statusTextClass(seat) {
                if (seat.presence) return "text-blue-400";
                return {
                    "Ready": "text-emerald-400", "Degraded": "text-amber-400",
                    "Maintenance": "text-red-400", "Scrapped": "text-slate-500",
                }[seat.status] ?? "text-emerald-400";
            },
            badgeClass(seat) {
                if (seat.presence) return "bg-blue-500/15 text-blue-400 border-blue-500/30";
                return {
                    "Ready": "bg-emerald-500/15 text-emerald-400 border-emerald-500/30",
                    "Degraded": "bg-amber-500/15 text-amber-400 border-amber-500/30",
                    "Maintenance": "bg-red-500/15 text-red-400 border-red-500/30",
                    "Scrapped": "bg-slate-700/40 text-slate-400 border-slate-600",
                }[seat.status] ?? "bg-emerald-500/15 text-emerald-400 border-emerald-500/30";
            },
            get counts() {
                return {
                    ready: this.seats.filter(s => s.status === "Ready" && !s.presence).length,
                    occupied: this.seats.filter(s => s.presence).length,
                    degraded: this.seats.filter(s => s.status === "Degraded").length,
                    maintenance: this.seats.filter(s => s.status === "Maintenance").length,
                };
            },
         }'>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-3.5 rounded-xl border border-emerald-500/30 bg-emerald-900/30 text-emerald-300 text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-400"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Header Panel -->
        <div class="glass-panel p-4 rounded-xl flex flex-wrap items-center justify-between gap-4 relative z-30">
            <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-border-all text-blue-400"></i> Denah Lab
                    @if ($selectedLab)
                        <span class="text-blue-400">{{ str_replace('Laboratorium Komputer', 'Lab', $selectedLab->name) }}</span>
                    @endif
                </h3>
                <p class="text-xs text-slate-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Live update otomatis &middot; terakhir <span x-text="serverTime"></span>
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('seatmap.index') }}" class="flex items-center gap-2">
                    <x-select name="lab_id" :options="$labOptions" selected="{{ $selectedLab?->id ?? '' }}" placeholder="Pilih Lab" submit />
                </form>
            </div>
        </div>

        <!-- Legend & Counter -->
        <div class="glass-panel p-3 rounded-xl flex flex-wrap items-center justify-between gap-3 text-[11px]">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-slate-400">
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded border border-emerald-500/50 bg-emerald-500/20"></span> Ready / Kosong</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded border border-blue-500/50 bg-blue-500/20 animate-pulse"></span> Terisi / Presensi</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded border border-amber-500/50 bg-amber-500/20"></span> Degraded</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded border border-red-500/50 bg-red-500/20"></span> Maintenance</span>
                <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded border border-slate-700 bg-slate-900"></span> Scrapped</span>
            </div>
            <div class="flex items-center gap-2 font-semibold">
                <span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400">Kosong: <span x-text="counts.ready"></span></span>
                <span class="px-2 py-0.5 rounded-full bg-blue-500/15 text-blue-400">Terisi: <span x-text="counts.occupied"></span></span>
                <span class="px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400">Rusak ringan: <span x-text="counts.degraded"></span></span>
                <span class="px-2 py-0.5 rounded-full bg-red-500/15 text-red-400">Servis: <span x-text="counts.maintenance"></span></span>
            </div>
        </div>

        <!-- Seat Grid -->
        @if ($selectedLab && count($seats) > 0)
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                <template x-for="seat in seats" :key="seat.id">
                    <button type="button"
                            @click="selected = seat"
                            :class="cardClass(seat)"
                            class="p-3 rounded-xl border text-left transition-all hover:scale-[1.03] shadow-md space-y-1.5 group">
                        <div class="flex items-center justify-between">
                            <span class="font-mono text-xs font-bold text-slate-100" x-text="seat.code"></span>
                            <i class="fa-solid text-xs" :class="[statusIcon(seat), statusTextClass(seat)]"></i>
                        </div>
                        <div class="text-[10px] text-slate-500 truncate" x-text="seat.seat_label"></div>
                        <div class="flex items-center gap-1.5 min-h-[16px]">
                            <template x-if="seat.presence">
                                <span class="flex items-center gap-1 text-[11px] font-semibold text-blue-300 truncate animate-pulse">
                                    <i class="fa-solid fa-user text-[9px]"></i>
                                    <span x-text="seat.presence.fullname" class="truncate"></span>
                                </span>
                            </template>
                            <template x-if="!seat.presence">
                                <span class="text-[11px] font-semibold" :class="statusTextClass(seat)" x-text="statusLabel(seat)"></span>
                            </template>
                        </div>
                    </button>
                </template>
            </div>
        @else
            <div class="glass-panel p-8 rounded-xl text-center">
                <i class="fa-solid fa-border-all text-3xl text-slate-600 mb-2"></i>
                <p class="text-xs text-slate-400">Belum ada PC yang terdaftar di lab ini.</p>
            </div>
        @endif

        <!-- Modal Detail PC -->
        <div x-show="selected" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="selected = null"></div>
            <div x-show="selected"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative w-full max-w-md glass-panel rounded-2xl p-5 space-y-4">
                <template x-if="selected">
                    <div class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="text-base font-bold text-white font-mono" x-text="selected.code"></h3>
                                <p class="text-xs text-slate-400" x-text="selected.name + ' • ' + selected.seat_label"></p>
                            </div>
                            <button type="button" @click="selected = null" class="text-slate-500 hover:text-slate-300 transition-colors">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 text-[10px] font-bold rounded-full border"
                                  :class="badgeClass(selected)"
                                  x-text="statusLabel(selected)"></span>
                            <template x-if="selected.presence">
                                <span class="text-[11px] text-blue-300">
                                    Masuk pukul <span x-text="selected.presence.check_in_time"></span> WIB
                                </span>
                            </template>
                        </div>

                        <template x-if="selected.presence">
                            <div class="p-3 rounded-xl border border-blue-500/30 bg-blue-500/10 space-y-1">
                                <p class="text-[10px] uppercase tracking-wider text-blue-400 font-bold">Pengguna Aktif</p>
                                <p class="text-sm font-bold text-white" x-text="selected.presence.fullname"></p>
                                <p class="text-xs text-slate-400 font-mono" x-text="selected.presence.identifier"></p>
                            </div>
                        </template>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="p-2.5 rounded-lg bg-slate-900/80 border border-slate-800">
                                <p class="text-[10px] text-slate-500 uppercase tracking-wide">CPU</p>
                                <p class="text-slate-200 font-semibold" x-text="selected.cpu_spec"></p>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-900/80 border border-slate-800">
                                <p class="text-[10px] text-slate-500 uppercase tracking-wide">RAM</p>
                                <p class="text-slate-200 font-semibold"><span x-text="selected.ram_gb"></span> GB</p>
                            </div>
                            <div class="p-2.5 rounded-lg bg-slate-900/80 border border-slate-800 col-span-2">
                                <p class="text-[10px] text-slate-500 uppercase tracking-wide">IP Address</p>
                                <p class="text-slate-200 font-semibold font-mono" x-text="selected.ip_address"></p>
                            </div>
                        </div>

                        @if (auth()->user()->role !== 'siswa')
                            <a href="{{ route('tickets.index') }}"
                               class="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-3.5 py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-lg shadow-amber-500/20">
                                <i class="fa-solid fa-triangle-exclamation"></i> Lapor Kendala PC Ini
                            </a>
                        @endif
                    </div>
                </template>
            </div>
        </div>
    </div>
</x-app-layout>
