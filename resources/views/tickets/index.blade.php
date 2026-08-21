<x-app-layout>
    @section('title', 'Helpdesk & Tiket Perbaikan')
    @section('subtitle', 'Pusat komplain kerusakan perangkat dan log pekerjaan teknisi')

    @php
        $canManage = in_array(auth()->user()->role, ['super_admin', 'teknisi']);
        $canCreate = in_array(auth()->user()->role, ['super_admin', 'teknisi', 'instruktur']);

        $labOptions = $labs->mapWithKeys(fn ($lab) => [$lab->id => str_replace('Laboratorium Komputer', 'Lab', $lab->name)])->all();

        $priorityMeta = [
            'High' => ['class' => 'bg-red-500/15 text-red-400 border border-red-500/25', 'sla' => \App\Models\Ticket::SLA_HOURS['High']],
            'Medium' => ['class' => 'bg-amber-500/15 text-amber-400 border border-amber-500/25', 'sla' => \App\Models\Ticket::SLA_HOURS['Medium']],
            'Low' => ['class' => 'bg-slate-700/40 text-slate-300 border border-slate-600', 'sla' => \App\Models\Ticket::SLA_HOURS['Low']],
        ];

        $columns = [
            'Open' => [
                'label' => 'Open (Belum Ditangani)',
                'icon' => 'fa-circle-dot',
                'border' => 'border-t-amber-500',
                'text' => 'text-amber-400',
                'badge' => 'bg-amber-500/20 text-amber-400',
                'items' => $openTickets,
            ],
            'In Progress' => [
                'label' => 'In Progress (Prosedur Servis)',
                'icon' => 'fa-spinner',
                'border' => 'border-t-blue-500',
                'text' => 'text-blue-400',
                'badge' => 'bg-blue-500/20 text-blue-400',
                'items' => $progressTickets,
            ],
            'Resolved' => [
                'label' => 'Resolved (Selesai)',
                'icon' => 'fa-circle-check',
                'border' => 'border-t-emerald-500',
                'text' => 'text-emerald-400',
                'badge' => 'bg-emerald-500/20 text-emerald-400',
                'items' => $resolvedTickets,
                'count' => $resolvedTotal,
            ],
        ];

        $assetsByLab = $assets->groupBy(fn ($asset) => str_replace('Laboratorium Komputer', 'Lab', $asset->lab->name));
    @endphp

    <div class="space-y-4" x-data='{
        resolveTicket: { id: 0, label: "" },
        deleteTicket: { id: 0, label: "" },
    }'>

        <!-- Flash Messages -->
        @if (session('success'))
            <div class="p-3.5 rounded-xl border border-emerald-500/30 bg-emerald-900/30 text-emerald-300 text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-400"></i>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-3.5 rounded-xl border border-red-500/30 bg-red-900/30 text-red-300 text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-red-400"></i>
                {{ session('error') }}
            </div>
        @endif

        <!-- Header Panel -->
        <div class="glass-panel p-4 rounded-xl flex flex-wrap items-center justify-between gap-4 relative z-30">
            <div>
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-ticket-simple text-amber-400"></i> Helpdesk & Tiket Perbaikan Lab
                    @if ($selectedLab)
                        <span class="text-amber-400">{{ str_replace('Laboratorium Komputer', 'Lab', $selectedLab->name) }}</span>
                    @endif
                </h3>
                <p class="text-xs text-slate-400">Kelola dan update penanganan kerusakan komponen fisik/software</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('tickets.index') }}" class="flex items-center gap-2">
                    <x-select name="lab_id" :options="$labOptions" selected="{{ $selectedLab?->id ?? '' }}" placeholder="Semua Lab" submit />
                </form>

                <a href="{{ route('tickets.history') }}" title="Lihat arsip semua tiket"
                   class="border border-slate-700 hover:border-slate-500 text-slate-300 font-semibold text-xs px-3.5 py-2 rounded-lg flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Lengkap
                </a>

                @if ($canCreate)
                    <button @click="$dispatch('open-modal', 'modalNewTicket')" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-3.5 py-2 rounded-lg flex items-center gap-1.5 transition-all shadow-lg shadow-amber-500/20">
                        <i class="fa-solid fa-plus"></i> Buat Tiket Baru
                    </button>
                @endif
            </div>
        </div>

        <!-- Kanban Board -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach ($columns as $status => $col)
                <div class="glass-panel p-4 rounded-xl border-t-2 {{ $col['border'] }} flex flex-col">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-slate-800">
                        <span class="text-xs font-bold {{ $col['text'] }} uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid {{ $col['icon'] }}"></i> {{ $col['label'] }}
                        </span>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $col['badge'] }}">{{ $col['count'] ?? $col['items']->count() }}</span>
                    </div>

                    <div class="space-y-3 flex-1 min-h-[300px]">
                        @forelse ($col['items'] as $ticket)
                            @php
                                $due = $ticket->slaDueAt();
                                $minutesLeft = (int) floor(($due->getTimestamp() - now()->getTimestamp()) / 60);
                                $durationHours = $ticket->resolved_at
                                    ? (int) ceil(($ticket->resolved_at->getTimestamp() - $ticket->reported_at->getTimestamp()) / 3600)
                                    : null;
                            @endphp
                            <div class="p-3 bg-slate-900/90 rounded-xl border border-slate-800 space-y-2 text-xs hover:border-slate-700 transition-all shadow-md">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-amber-400">{{ $ticket->ticket_code }}</span>
                                    <span class="text-[10px] text-slate-500">{{ $ticket->reported_at->format('d M Y H:i') }}</span>
                                </div>

                                <div class="font-bold text-slate-200 leading-snug">
                                    {{ $ticket->asset->asset_code }}
                                    <span class="text-slate-500">•</span>
                                    {{ $ticket->component_issue }}
                                </div>

                                <p class="text-slate-400 text-[11px] line-clamp-2">{{ $ticket->description }}</p>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ $priorityMeta[$ticket->priority]['class'] }}">
                                        {{ $ticket->priority }} • SLA {{ $priorityMeta[$ticket->priority]['sla'] }}j
                                    </span>
                                    @if ($ticket->status !== 'Resolved')
                                        @if ($ticket->isOverdue())
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-500/15 text-red-400 border border-red-500/25 animate-pulse">
                                                Lewat SLA {{ abs($minutesLeft) < 60 ? abs($minutesLeft).' mnt' : abs($hoursLeft ?? (int) floor($minutesLeft / 60)).' jam' }}
                                            </span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-800 text-slate-400 border border-slate-700">
                                                Sisa {{ $minutesLeft < 60 ? $minutesLeft.' mnt' : (int) floor($minutesLeft / 60).' jam' }}
                                            </span>
                                        @endif
                                    @endif
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-semibold bg-slate-800/60 text-slate-500">
                                        {{ str_replace('Laboratorium Komputer', 'Lab', $ticket->asset->lab->name) }}
                                    </span>
                                </div>

                                @if ($ticket->status === 'Resolved')
                                    <div class="p-2 rounded-lg bg-emerald-500/5 border border-emerald-500/15 text-[11px] text-slate-400 leading-relaxed">
                                        <p><i class="fa-solid fa-user-gear mr-1 text-emerald-400"></i> {{ $ticket->technician_name ?? '-' }} • {{ $durationHours }} jam pengerjaan</p>
                                        <p class="mt-1"><i class="fa-solid fa-note-sticky mr-1 text-emerald-400"></i> {{ $ticket->resolution_notes }}</p>
                                    </div>
                                @endif

                                <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px]">
                                    <span class="text-slate-500 truncate">Pelapor: {{ $ticket->reporter_name }}</span>
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        @if ($canManage)
                                            @if ($ticket->status === 'Open')
                                                <form method="POST" action="{{ route('tickets.start', $ticket) }}">
                                                    @csrf
                                                    <button class="px-2 py-0.5 bg-blue-600/20 text-blue-400 border border-blue-500/30 rounded text-[10px] font-semibold hover:bg-blue-600/30 transition-all">
                                                        Proses Tiket &rarr;
                                                    </button>
                                                </form>
                                            @elseif ($ticket->status === 'In Progress')
                                                <button @click="resolveTicket = { id: {{ $ticket->id }}, label: '{{ addslashes($ticket->ticket_code) }}' }; $dispatch('open-modal', 'modalResolveTicket')" class="px-2 py-0.5 bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 rounded text-[10px] font-semibold hover:bg-emerald-600/30 transition-all">
                                                    Selesaikan Tiket
                                                </button>
                                            @else
                                                <span class="text-emerald-400 font-semibold"><i class="fa-solid fa-check mr-1"></i> Selesai</span>
                                            @endif
                                            <button @click="deleteTicket = { id: {{ $ticket->id }}, label: '{{ addslashes($ticket->ticket_code.' - '.$ticket->asset->asset_code) }}' }; $dispatch('open-modal', 'confirmDeleteTicket')" class="w-5 h-5 bg-slate-800/80 rounded text-red-400 text-[9px] hover:text-red-300 transition-colors" title="Hapus Tiket">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="min-h-[280px] flex flex-col items-center justify-center text-center p-4">
                                <i class="fa-solid fa-inbox text-2xl text-slate-700 mb-2"></i>
                                <p class="text-[11px] text-slate-500">Tidak ada tiket di kolom ini.</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($status === 'Resolved' && $resolvedTotal > 5)
                        <p class="mt-3 pt-2 border-t border-slate-800 text-[10px] text-slate-500 text-center">
                            Menampilkan 5 terbaru dari {{ $resolvedTotal }} tiket selesai
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Modal: Buat Tiket Baru -->
        @if ($canCreate)
            <x-modal name="modalNewTicket" maxWidth="lg" :show="false" focusable>
                <form method="POST" action="{{ route('tickets.store') }}" class="p-5 space-y-4 text-xs">
                    @csrf
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-amber-400"></i> Lapor Kerusakan / Kendala
                        </h3>
                        <button type="button" @click="$dispatch('close-modal', 'modalNewTicket')" class="text-slate-400 hover:text-white p-1">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>

                    <div>
                        <x-input-label value="Pilih Kode PC Kendala (*)" />
                        <select name="asset_id" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                            @forelse ($assetsByLab as $labName => $labAssets)
                                <optgroup label="{{ $labName }}">
                                    @foreach ($labAssets as $asset)
                                        <option value="{{ $asset->id }}" @selected(old('asset_id') == $asset->id)>{{ $asset->asset_code }} &bull; {{ $asset->name }}</option>
                                    @endforeach
                                </optgroup>
                            @empty
                                <option value="">Tidak ada PC pada lab ini</option>
                            @endforelse
                        </select>
                        <x-input-error :messages="$errors->get('asset_id')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Komponen Kendala (*)" />
                            <select name="component_issue" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                @foreach (\App\Models\Ticket::COMPONENTS as $component)
                                    <option value="{{ $component }}" @selected(old('component_issue') === $component)>{{ $component }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('component_issue')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Prioritas / SLA (*)" />
                            <select name="priority" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                @foreach (\App\Models\Ticket::PRIORITIES as $priority)
                                    <option value="{{ $priority }}" @selected(old('priority', 'Medium') === $priority)>
                                        {{ $priority }} (max {{ $priorityMeta[$priority]['sla'] }} jam)
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('priority')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Deskripsi Kendala (*)" />
                        <textarea name="description" rows="3" required minlength="5" placeholder="Jelaskan detail kendala (misal: klik kiri mouse macet, kabel HDMI kendor)..." class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Nama Pelapor (*)" />
                        <input type="text" name="reporter_name" value="{{ old('reporter_name', auth()->user()->name) }}" required maxlength="100" class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                        <x-input-error :messages="$errors->get('reporter_name')" class="mt-1" />
                    </div>

                    <p class="text-[10px] text-slate-500 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-amber-400 mr-1"></i>
                        Status PC akan naik otomatis: kendala minor menjadi Degraded, kerusakan berat (monitor/booting) menjadi Maintenance. Saat tiket diselesaikan, PC kembali Ready.
                    </p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="$dispatch('close-modal', 'modalNewTicket')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-amber-500 text-slate-950 font-bold rounded-lg hover:bg-amber-400 shadow-lg shadow-amber-500/20">Kirim Tiket</button>
                    </div>
                </form>
            </x-modal>
        @endif

        <!-- Modal: Selesaikan Tiket -->
        @if ($canManage)
            <x-modal name="modalResolveTicket" maxWidth="lg" :show="false" focusable>
                <form method="POST" :action="'/tickets/' + resolveTicket.id + '/resolve'" class="p-5 space-y-4 text-xs">
                    @csrf
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <i class="fa-solid fa-circle-check text-emerald-400"></i>
                            Selesaikan Tiket <span class="text-emerald-400 font-mono" x-text="resolveTicket.label"></span>
                        </h3>
                        <button type="button" @click="$dispatch('close-modal', 'modalResolveTicket')" class="text-slate-400 hover:text-white p-1">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>

                    <div>
                        <x-input-label value="Catatan Solusi (*)" />
                        <textarea name="resolution_notes" rows="4" required minlength="5" placeholder="Wajib diisi. Misal: ganti mouse unit cadangan, install ulang driver..." class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-emerald-500"></textarea>
                        <x-input-error :messages="$errors->get('resolution_notes')" class="mt-1" />
                    </div>

                    <p class="text-[10px] text-slate-500 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-emerald-400 mr-1"></i>
                        Setelah diselesaikan, status PC induk otomatis kembali Ready (kecuali unit sudah Scrapped).
                    </p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="$dispatch('close-modal', 'modalResolveTicket')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-500 shadow-lg shadow-emerald-600/20">Selesaikan & Kembalikan PC ke Ready</button>
                    </div>
                </form>
            </x-modal>

            <!-- Modal: Konfirmasi Hapus Tiket -->
            <x-modal name="confirmDeleteTicket" maxWidth="sm" :show="false">
                <div class="p-5 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center text-xl mb-3">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white">Hapus Tiket Ini?</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        <span class="text-slate-200 font-bold" x-text="deleteTicket.label"></span> akan dihapus permanen.
                    </p>
                    <form method="POST" :action="'/tickets/' + deleteTicket.id" class="mt-4 flex justify-center gap-2">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="$dispatch('close-modal', 'confirmDeleteTicket')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-500 shadow-lg shadow-red-600/20">Ya, Hapus</button>
                    </form>
                </div>
            </x-modal>
        @endif
    </div>
</x-app-layout>
