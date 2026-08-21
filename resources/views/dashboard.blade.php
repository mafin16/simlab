<x-app-layout>
    @section('title', 'Dashboard Utama')
    @section('subtitle', 'Ringkasan status operasional dan statistik laboratorium komputer')

    <div class="space-y-6">
        <!-- Summary Metrics Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total PC -->
            <div class="glass-panel p-4 rounded-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-400">Total Unit PC</p>
                    <h3 class="text-2xl font-bold text-white mt-1">{{ $totalPc }} <span class="text-xs text-slate-400 font-normal">unit</span></h3>
                    <p class="text-[11px] text-emerald-400 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-check"></i> {{ $selectedLab ? $selectedLab->name : 'Tersebar di 2 Ruang Lab' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-desktop"></i>
                </div>
            </div>

            <!-- PC Ready -->
            <div class="glass-panel p-4 rounded-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-400">Kondisi Ready / Normal</p>
                    <h3 class="text-2xl font-bold text-emerald-400 mt-1">{{ $ready }} <span class="text-xs text-slate-400 font-normal">unit ({{ $totalPc > 0 ? round(($ready / $totalPc) * 100) : 0 }}%)</span></h3>
                    <p class="text-[11px] text-slate-400 mt-1">Siap pakai praktikum</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </div>

            <!-- PC Rusak / Maintenance -->
            <div class="glass-panel p-4 rounded-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-400">Kendala / Servis</p>
                    <h3 class="text-2xl font-bold text-slate-200 mt-1">{{ $degraded + $maintenance }} <span class="text-xs text-slate-400 font-normal">unit ({{ $totalPc > 0 ? round((($degraded + $maintenance) / $totalPc) * 100) : 0 }}%)</span></h3>
                    <p class="text-[11px] text-emerald-400 mt-1 flex items-center gap-1">
                        <i class="fa-solid fa-circle-check"></i> Semua Perangkat Normal
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-slate-800 border border-slate-700 text-slate-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
            </div>

            <!-- Sesi Hari Ini -->
            <div class="glass-panel p-4 rounded-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-slate-400">Jadwal Sesi Hari Ini</p>
                    <h3 class="text-2xl font-bold text-indigo-400 mt-1">{{ $todayScheduleCount }} <span class="text-xs text-slate-400 font-normal">Sesi Praktikum</span></h3>
                    <p class="text-[11px] text-indigo-300 mt-1">{{ $selectedLab ? str_replace('Laboratorium Komputer', 'Lab', $selectedLab->name).' aktif' : 'Lab 1 & Lab 2 aktif' }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-clock font-normal"></i>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Chart 1: Condition distribution -->
            <div class="glass-panel p-5 rounded-xl lg:col-span-1 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-chart-pie text-blue-400"></i> Status Kelayakan Unit
                    </h3>
                    <span class="text-xs text-slate-400">{{ $selectedLab ? $selectedLab->name : 'Semua Lab' }}</span>
                </div>
                <div class="relative w-full h-56 flex items-center justify-center">
                    <canvas id="conditionChart"></canvas>
                </div>
                <div class="grid grid-cols-3 gap-2 pt-3 border-t border-slate-800 text-center text-xs">
                    <div><span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 mr-1"></span>Ready: {{ $ready }}</div>
                    <div><span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 mr-1"></span>Degraded: {{ $degraded }}</div>
                    <div><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500 mr-1"></span>Servis: {{ $maintenance }}</div>
                </div>
            </div>

            <!-- Chart 2: Lab occupancy (kosong, belum ada data) -->
            <div class="glass-panel p-5 rounded-xl lg:col-span-2 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-chart-column text-blue-400"></i> Kepadatan Penggunaan Lab Minggu Ini
                    </h3>
                    <span class="text-xs text-slate-400">Jam Operasional (07:00 - 16:00)</span>
                </div>
                <div class="relative w-full h-56 flex items-center justify-center">
                    <canvas id="occupancyChart"></canvas>
                </div>
                <div class="text-xs text-slate-400 pt-3 border-t border-slate-800 flex items-center justify-between">
                    <span>{{ $selectedLab ? str_replace('Laboratorium Komputer', 'Lab', $selectedLab->name) : 'Semua Lab' }}</span>
                    <a href="{{ route('schedules.index') }}" class="text-blue-400 hover:underline">Lihat Kalender Lengkap &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Recent Tickets & Active Schedule Widget -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Maintenance Tickets -->
            <div class="glass-panel p-5 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-screwdriver-wrench text-amber-400"></i> Tiket Servis Perlu Penanganan
                    </h3>
                    <div class="flex items-center gap-3">
                        @if (in_array(auth()->user()->role, ['super_admin', 'teknisi', 'instruktur']))
                            <a href="{{ route('tickets.index') }}" class="text-xs text-blue-400 hover:underline">Lihat Semua</a>
                        @endif
                        <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-400 font-medium">{{ $activeTickets }} Tiket Aktif</span>
                    </div>
                </div>
                <div class="space-y-3">
                    @forelse ($recentTickets as $ticket)
                        <div class="p-3 bg-slate-900/80 rounded-lg border border-slate-800 flex items-center justify-between text-xs">
                            <div class="min-w-0">
                                <span class="font-mono font-bold text-amber-400">{{ $ticket->asset->asset_code ?? 'TKT' }}</span>
                                <p class="font-semibold text-slate-200 mt-0.5 truncate">{{ $ticket->description }}</p>
                            </div>
                            <span class="px-2 py-1 bg-amber-500/10 text-amber-400 border border-amber-500/20 rounded text-[10px] font-bold shrink-0">{{ $ticket->status }}</span>
                        </div>
                    @empty
                        <div class="p-4 bg-slate-900/50 rounded-lg border border-slate-800 text-center text-xs text-slate-400">
                            <i class="fa-solid fa-circle-check text-emerald-400 text-lg mb-1 block"></i>
                            Tidak ada tiket kerusakan aktif. Semua perangkat dalam kondisi Ready!
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Active Schedule Today -->
            <div class="glass-panel p-5 rounded-xl">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-calendar-check text-indigo-400"></i> Jadwal Praktikum Hari Ini
                    </h3>
                    <span class="text-xs px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-medium">{{ $selectedLab ? $selectedLab->name : 'Semua Lab' }}</span>
                </div>
                <div class="space-y-3">
                    @forelse ($todaySchedules as $sched)
                        <div class="p-3 bg-slate-900/80 rounded-lg border border-slate-800 flex items-center justify-between text-xs">
                            <div class="min-w-0">
                                <span class="text-xs font-mono text-indigo-400 font-bold">{{ $sched->start_time->format('H:i') }} – {{ $sched->end_time->format('H:i') }} WIB</span>
                                <p class="font-bold text-slate-200 mt-0.5 truncate">{{ $sched->subject_name }} ({{ $sched->class_group }})</p>
                                <p class="text-[11px] text-slate-400 truncate">Guru: {{ $sched->instructor_name }} • {{ str_replace('Laboratorium Komputer', 'Lab', $sched->lab->name) }}</p>
                            </div>
                            <span class="px-2 py-1 bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded text-[10px] font-bold shrink-0">Jadwal</span>
                        </div>
                    @empty
                        <div class="p-4 bg-slate-900/50 rounded-lg border border-slate-800 text-center text-xs text-slate-400">
                            <i class="fa-solid fa-calendar-days text-indigo-400 text-lg mb-1 block"></i>
                            Belum ada jadwal praktikum hari ini
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Chart.js didefinisikan oleh app.js (module script / deferred), jadi
        // inisialisasi harus menunggu DOMContentLoaded agar window.Chart tersedia.
        document.addEventListener('DOMContentLoaded', () => {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#475569';
            const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.08)';

            // Chart 1: Condition Doughnut
            const ctx1 = document.getElementById('conditionChart');
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: @json($doughnutData['labels']),
                    datasets: [{
                        data: @json($doughnutData['values']),
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                        borderWidth: 0,
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f1f5f9',
                            bodyColor: '#94a3b8',
                            borderColor: 'rgba(255,255,255,0.08)',
                            borderWidth: 1
                        }
                    },
                    cutout: '75%'
                }
            });

            // Chart 2: Occupancy Bar
            const ctx2 = document.getElementById('occupancyChart');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: @json($occupancyData['labels']),
                    datasets: [{
                        label: 'Jumlah Sesi',
                        data: @json($occupancyData['values']),
                        backgroundColor: '#6366f1',
                        hoverBackgroundColor: '#818cf8',
                        borderRadius: 6,
                        maxBarThickness: 28
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#f1f5f9',
                            bodyColor: '#94a3b8',
                            borderColor: 'rgba(255,255,255,0.08)',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: { color: textColor, stepSize: 1 },
                            grid: { color: gridColor }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>