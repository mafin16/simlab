<x-app-layout>
    @section('title', 'Laporan & Audit')
    @section('subtitle', 'Pusat rekap, ekspor Excel, dan cetak PDF untuk seluruh aktivitas lab')

    <div class="space-y-4">

        <!-- Flash Messages -->
        @if (session('error'))
            <div class="p-3.5 rounded-xl border border-red-500/30 bg-red-900/30 text-red-300 text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-red-400"></i>
                {{ session('error') }}
            </div>
        @endif

        <!-- Ringkasan Bulan Ini -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @php
                $stats = [
                    ['label' => 'Tiket Aktif', 'value' => $activeTickets, 'icon' => 'fa-ticket-simple', 'class' => 'text-amber-400'],
                    ['label' => 'Selesai Bulan Ini', 'value' => $resolvedThisMonth, 'icon' => 'fa-circle-check', 'class' => 'text-emerald-400'],
                    ['label' => 'Sesi Presensi Bulan Ini', 'value' => $sessionsThisMonth, 'icon' => 'fa-fingerprint', 'class' => 'text-blue-400'],
                    ['label' => 'Total Jam Praktikum', 'value' => $presenceHoursThisMonth.' jam', 'icon' => 'fa-clock', 'class' => 'text-purple-400'],
                ];
            @endphp
            @foreach ($stats as $stat)
                <div class="glass-panel p-4 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] uppercase tracking-wider text-slate-500 font-bold">{{ $stat['label'] }}</p>
                            <p class="text-xl font-bold text-white mt-1">{{ $stat['value'] }}</p>
                        </div>
                        <i class="fa-solid {{ $stat['icon'] }} text-lg {{ $stat['class'] }}"></i>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Kartu Laporan -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- 1. Inventaris Aset -->
            <div class="glass-panel p-5 rounded-xl flex flex-col">
                <h3 class="text-sm font-bold text-white flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-hard-drive text-blue-400"></i> Inventaris Aset & PC
                </h3>
                <p class="text-xs text-slate-400 mb-4">Rekap lengkap seluruh aset (kode, spesifikasi, status) dalam format Excel. Mengikuti filter aktif di halaman aset.</p>
                <div class="mt-auto flex flex-wrap gap-2">
                    <a href="{{ route('assets.export') }}" class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-3.5 py-2 rounded-lg flex items-center gap-1.5 transition-all">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </a>
                    <a href="{{ route('assets.index') }}" class="border border-slate-700 hover:border-slate-500 text-slate-300 font-semibold text-xs px-3.5 py-2 rounded-lg flex items-center gap-1.5 transition-all">
                        <i class="fa-solid fa-table-list"></i> Buka Halaman Aset
                    </a>
                </div>
            </div>

            <!-- 2. Riwayat Tiket -->
            <div class="glass-panel p-5 rounded-xl flex flex-col">
                <h3 class="text-sm font-bold text-white flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-clock-rotate-left text-amber-400"></i> Riwayat Tiket Lengkap
                </h3>
                <p class="text-xs text-slate-400 mb-4">Semua tiket termasuk yang lama — tabel dengan pagination, pencarian kode/pelapor, dan filter status, prioritas, lab, serta tanggal.</p>
                <div class="mt-auto flex flex-wrap gap-2">
                    <a href="{{ route('tickets.history') }}" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-3.5 py-2 rounded-lg flex items-center gap-1.5 transition-all">
                        <i class="fa-solid fa-table-list"></i> Buka Riwayat Tiket
                    </a>
                    <a href="{{ route('tickets.history.excel') }}" class="border border-slate-700 hover:border-slate-500 text-slate-300 font-semibold text-xs px-3.5 py-2 rounded-lg flex items-center gap-1.5 transition-all">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>

            <!-- 3. Riwayat Pemeliharaan (PDF) -->
            <div class="glass-panel p-5 rounded-xl relative z-30 flex flex-col">
                <h3 class="text-sm font-bold text-white flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-screwdriver-wrench text-red-400"></i> Laporan Pemeliharaan (PDF)
                </h3>
                <p class="text-xs text-slate-400 mb-4">Cetak riwayat perbaikan tiket Resolved: komponen rusak, solusi, teknisi, dan kepatuhan SLA.</p>
                <form method="GET" action="{{ route('reports.maintenance.pdf') }}" class="mt-auto space-y-2.5">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Dari Tanggal</label>
                            <input type="date" name="date_from" value="{{ now()->startOfMonth()->format('Y-m-d') }}"
                                   class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Sampai</label>
                            <input type="date" name="date_to" value="{{ now()->format('Y-m-d') }}"
                                   class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <x-select name="lab_id" :options="$labs->mapWithKeys(fn ($lab) => [$lab->id => str_replace('Laboratorium Komputer', 'Lab', $lab->name)])->all()" selected="" placeholder="Semua Lab" />
                    <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-500 text-white font-bold text-xs py-2 rounded-lg flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-file-pdf"></i> Unduh PDF Pemeliharaan
                    </button>
                </form>
            </div>

            <!-- 4. Log Presensi -->
            <div class="glass-panel p-5 rounded-xl relative z-30 flex flex-col">
                <h3 class="text-sm font-bold text-white flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-fingerprint text-purple-400"></i> Log Presensi & Utilisasi
                </h3>
                <p class="text-xs text-slate-400 mb-4">Rekap sesi praktikum siswa: PC yang dipakai, jam masuk-keluar, dan durasi per periode.</p>
                <form method="GET" action="{{ route('reports.presence.pdf') }}" class="mt-auto space-y-2.5">
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Dari Tanggal</label>
                            <input type="date" name="date_from" value="{{ now()->startOfMonth()->format('Y-m-d') }}"
                                   class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Sampai</label>
                            <input type="date" name="date_to" value="{{ now()->format('Y-m-d') }}"
                                   class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    <x-select name="lab_id" :options="$labs->mapWithKeys(fn ($lab) => [$lab->id => str_replace('Laboratorium Komputer', 'Lab', $lab->name)])->all()" selected="" placeholder="Semua Lab" />
                    <div class="grid grid-cols-2 gap-2.5">
                        <button type="submit"
                                class="bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs py-2 rounded-lg flex items-center justify-center gap-1.5 transition-all">
                            <i class="fa-solid fa-file-pdf"></i> Unduh PDF
                        </button>
                        <button type="submit" formaction="{{ route('reports.presence.excel') }}"
                                class="border border-emerald-500/40 hover:bg-emerald-500/10 text-emerald-400 font-bold text-xs py-2 rounded-lg flex items-center justify-center gap-1.5 transition-all">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
