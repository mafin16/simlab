<x-app-layout>
    @section('title', 'Riwayat Tiket')
    @section('subtitle', 'Arsip lengkap seluruh tiket perbaikan lab dengan filter dan ekspor')

    @php
        $priorityMeta = [
            'High' => ['class' => 'bg-red-500/15 text-red-400 border border-red-500/25'],
            'Medium' => ['class' => 'bg-amber-500/15 text-amber-400 border border-amber-500/25'],
            'Low' => ['class' => 'bg-slate-700/40 text-slate-300 border border-slate-600'],
        ];

        $statusMeta = [
            'Open' => 'bg-amber-500/20 text-amber-400',
            'In Progress' => 'bg-blue-500/20 text-blue-400',
            'Resolved' => 'bg-emerald-500/20 text-emerald-400',
        ];
    @endphp

    <div class="space-y-4">

        @if ($errors->any())
            <div class="p-3.5 rounded-xl border border-red-500/30 bg-red-900/30 text-red-300 text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-red-400"></i>
                {{ $errors->first() }}
            </div>
        @endif

        <!-- Panel Filter -->
        <div class="glass-panel p-4 rounded-xl relative z-30">
            <form method="GET" action="{{ route('tickets.history') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-3 items-end">
                <div class="xl:col-span-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Cari Kode / Pelapor</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="TKT-20260821-XXXX atau nama..."
                           class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Lab</label>
                    <x-select name="lab_id"
                              :options="$labs->mapWithKeys(fn ($lab) => [$lab->id => str_replace('Laboratorium Komputer', 'Lab', $lab->name)])->all()"
                              selected="{{ $filters['lab_id'] ?? '' }}"
                              placeholder="Semua Lab" />
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                    <x-select name="status"
                              :options="['Open' => 'Open', 'In Progress' => 'In Progress', 'Resolved' => 'Resolved']"
                              selected="{{ $filters['status'] ?? '' }}"
                              placeholder="Semua Status" />
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Prioritas</label>
                    <x-select name="priority"
                              :options="['High' => 'High', 'Medium' => 'Medium', 'Low' => 'Low']"
                              selected="{{ $filters['priority'] ?? '' }}"
                              placeholder="Semua Prioritas" />
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2 px-3 rounded-lg flex items-center justify-center gap-1.5 transition-all">
                        <i class="fa-solid fa-filter"></i> Terapkan
                    </button>
                    <a href="{{ route('tickets.history') }}" title="Reset filter"
                       class="border border-slate-700 hover:border-slate-500 text-slate-300 font-semibold text-xs py-2 px-3 rounded-lg flex items-center transition-all">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>

                <div class="md:col-span-2 xl:col-span-6 grid grid-cols-2 gap-3 max-w-md">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Dilaporkan Dari</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                               class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wide mb-1">Sampai</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                               class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-xs focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Riwayat -->
        <div class="glass-panel rounded-xl overflow-hidden">
            <div class="p-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-800">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-amber-400"></i> Arsip Tiket
                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-800 text-slate-400">{{ $tickets->total() }} tiket</span>
                </h3>
                <a href="{{ route('tickets.history.excel', request()->only(['lab_id', 'status', 'priority', 'search', 'date_from', 'date_to'])) }}"
                   class="border border-emerald-500/40 hover:bg-emerald-500/10 text-emerald-400 font-bold text-xs px-3.5 py-2 rounded-lg flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>
            </div>

            @if ($tickets->isEmpty())
                <div class="py-16 text-center">
                    <i class="fa-solid fa-inbox text-4xl text-slate-700 mb-3"></i>
                    <p class="text-sm text-slate-400 font-medium">Tidak ada tiket yang cocok dengan filter</p>
                    <p class="text-xs text-slate-500 mt-1">Coba ubah kata kunci atau rentang tanggal</p>
                </div>
            @else
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-left text-[10px] uppercase tracking-wider text-slate-500 border-b border-slate-800">
                                <th class="px-4 py-3 font-bold">Kode Tiket</th>
                                <th class="px-4 py-3 font-bold">Aset / Lab</th>
                                <th class="px-4 py-3 font-bold">Komponen</th>
                                <th class="px-4 py-3 font-bold">Prioritas</th>
                                <th class="px-4 py-3 font-bold">Status</th>
                                <th class="px-4 py-3 font-bold">Pelapor</th>
                                <th class="px-4 py-3 font-bold">Teknisi</th>
                                <th class="px-4 py-3 font-bold">Dilaporkan</th>
                                <th class="px-4 py-3 font-bold">Selesai</th>
                                <th class="px-4 py-3 font-bold">Durasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                <tr class="border-b border-slate-800/60 hover:bg-slate-800/30 transition-colors">
                                    <td class="px-4 py-3 font-mono text-[11px] text-blue-400 whitespace-nowrap">{{ $ticket->ticket_code }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <span class="font-semibold text-slate-200">{{ $ticket->asset->asset_code }}</span>
                                        <span class="block text-[10px] text-slate-500">{{ str_replace('Laboratorium Komputer', 'Lab', $ticket->asset->lab->name) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-300 max-w-[180px] truncate" title="{{ $ticket->component_issue }}">{{ $ticket->component_issue }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ $priorityMeta[$ticket->priority]['class'] }}">{{ $ticket->priority }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $statusMeta[$ticket->status] }}">{{ $ticket->status }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-300 whitespace-nowrap">{{ $ticket->reporter_name }}</td>
                                    <td class="px-4 py-3 text-slate-300 whitespace-nowrap">{{ $ticket->technician_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ $ticket->reported_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-slate-400 whitespace-nowrap">{{ $ticket->resolved_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($ticket->resolved_at)
                                            @php
                                                $durasiJam = ceil(($ticket->resolved_at->getTimestamp() - $ticket->reported_at->getTimestamp()) / 3600);
                                                $tepat = $durasiJam <= \App\Models\Ticket::SLA_HOURS[$ticket->priority];
                                            @endphp
                                            <span class="{{ $tepat ? 'text-emerald-400' : 'text-red-400' }} font-semibold" title="SLA {{ \App\Models\Ticket::SLA_HOURS[$ticket->priority] }} jam">
                                                {{ $durasiJam }} jam {{ $tepat ? '' : '(lewat SLA)' }}
                                            </span>
                                        @else
                                            <span class="text-slate-500">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-800">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
