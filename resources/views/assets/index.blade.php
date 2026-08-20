<x-app-layout>
    @section('title', 'Inventaris Aset & Spesifikasi PC')
    @section('subtitle', 'Pencatatan detail jeroan PC, IP address, periferal, dan status kelaikan')

    @php
        $canManage = in_array(auth()->user()->role, ['super_admin', 'teknisi']);
        $allIds = $assets->pluck('id')->all();
        $labOptions = $labs->mapWithKeys(fn ($lab) => [$lab->id => str_replace('Laboratorium Komputer', 'Lab', $lab->name)])->all();
        $statusOptions = ['Ready' => 'Ready / Normal', 'Degraded' => 'Degraded (Kendala Minor)', 'Maintenance' => 'Maintenance / Diservis'];
        $categoryOptions = ['PC Desktop' => 'PC Desktop', 'Server' => 'Server', 'Workstation' => 'Workstation'];
    @endphp

    <div class="space-y-4" x-data="{
        selected: [],
        allIds: @json($allIds),
        isAllSelected: false,
        toggleAll() {
            if (this.selected.length === this.allIds.length && this.allIds.length > 0) {
                this.selected = [];
            } else {
                this.selected = [...this.allIds];
            }
        },
    }" x-init="$watch('selected', val => this.isAllSelected = val.length === this.allIds.length && this.allIds.length > 0)">

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

        @if (session('import_errors'))
            <div class="p-3.5 rounded-xl border border-amber-500/30 bg-amber-900/30 text-xs">
                <p class="font-semibold text-amber-300 mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i> Beberapa baris tidak bisa diimpor:
                </p>
                <ul class="space-y-0.5 text-amber-200/80 max-h-32 overflow-y-auto custom-scrollbar">
                    @foreach (session('import_errors') as $err)
                        <li class="font-mono text-[11px]">{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter Bar -->
        <div class="glass-panel p-4 rounded-xl flex flex-wrap items-center justify-between gap-3 relative z-30">
            <form method="GET" action="{{ route('assets.index') }}" class="flex flex-wrap items-center gap-3 flex-1 min-w-[280px]">
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-sm pointer-events-none"></i>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari kode PC, spesifikasi, atau IP..." class="w-full bg-slate-900 text-xs text-slate-200 pl-9 pr-3 py-2 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                </div>

                <x-select name="lab_id" :options="$labOptions" selected="{{ $filters['lab_id'] ?? '' }}" placeholder="Semua Lab" submit />
                <x-select name="status" :options="['' => 'Semua Status'] + $statusOptions" selected="{{ $filters['status'] ?? '' }}" submit />
                <x-select name="category" :options="['' => 'Semua Kategori'] + $categoryOptions" selected="{{ $filters['category'] ?? '' }}" submit />

                @if ($filters['search'] ?? null)
                    <a href="{{ route('assets.index') }}" class="text-[11px] text-slate-500 hover:text-slate-300 flex items-center gap-1">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                @endif
            </form>

            <div class="flex items-center gap-2 flex-wrap">
                @if ($canManage)
                    <button @click="$dispatch('open-modal', 'modalImport')" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold px-3 py-2 rounded-lg border border-slate-700 flex items-center gap-1.5 transition-all">
                        <i class="fa-solid fa-file-excel text-emerald-400"></i> Import Excel
                    </button>
                    <a href="{{ route('assets.import.template') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold px-3 py-2 rounded-lg border border-slate-700 flex items-center gap-1.5 transition-all" title="Unduh template import">
                        <i class="fa-solid fa-file-arrow-down text-indigo-400"></i> Template
                    </a>
                    <a href="{{ route('assets.export', request()->only(['lab_id', 'status', 'category'])) }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold px-3 py-2 rounded-lg border border-slate-700 flex items-center gap-1.5 transition-all">
                        <i class="fa-solid fa-download text-blue-400"></i> Export Data
                    </a>
                    <a href="{{ route('assets.create') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-3 py-2 rounded-lg flex items-center gap-1.5 transition-all shadow-lg shadow-blue-600/20">
                        <i class="fa-solid fa-plus"></i> Tambah Aset
                    </a>
                @endif
            </div>
        </div>

        <!-- Bulk Action Bar -->
        @if ($canManage)
            <div x-show="selected.length > 0" x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="fixed bottom-5 left-1/2 -translate-x-1/2 z-40 bg-slate-800/95 backdrop-blur-md border border-red-500/30 rounded-xl shadow-xl shadow-black/40 px-4 py-3 flex items-center gap-4">
                <span class="text-xs font-semibold text-slate-200">
                    <span class="text-red-400 font-bold" x-text="selected.length"></span> aset terpilih
                </span>
                <button @click="selected = []" class="text-[11px] text-slate-400 hover:text-slate-200 flex items-center gap-1">
                    <i class="fa-solid fa-xmark"></i> Batal
                </button>
                <button @click="$dispatch('open-modal', 'confirmBulkDelete')" class="bg-red-600 hover:bg-red-500 text-white text-xs font-semibold px-3 py-2 rounded-lg flex items-center gap-1.5 shadow-lg shadow-red-600/20 transition-all">
                    <i class="fa-solid fa-trash"></i> Hapus Terpilih
                </button>
            </div>
        @endif

        <!-- Assets Table -->
        <div class="glass-panel rounded-xl overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900/90 text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-800">
                        <tr>
                            @if ($canManage)
                                <th class="p-3.5 pl-4 w-10">
                                    <input type="checkbox"
                                           class="w-4 h-4 rounded border-slate-600 bg-slate-800 accent-blue-600 cursor-pointer"
                                           :checked="isAllSelected"
                                           @change="toggleAll()">
                                </th>
                            @endif
                            <th class="p-3.5 pl-4">Kode Aset</th>
                            <th class="p-3.5">Nama Perangkat & Meja</th>
                            <th class="p-3.5">Spesifikasi Inti</th>
                            <th class="p-3.5">IP & MAC Address</th>
                            <th class="p-3.5">Kondisi Status</th>
                            <th class="p-3.5 text-center pr-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 font-medium">
                        @forelse ($assets as $asset)
                            <tr class="hover:bg-slate-900/60 transition-colors" :class="{ 'bg-blue-500/5': selected.includes({{ $asset->id }}) }">
                                @if ($canManage)
                                    <td class="p-3.5 pl-4">
                                        <input type="checkbox"
                                               class="w-4 h-4 rounded border-slate-600 bg-slate-800 accent-blue-600 cursor-pointer"
                                               x-model="selected"
                                               :value="{{ $asset->id }}">
                                    </td>
                                @endif
                                <td class="p-3.5 pl-4 font-mono font-bold text-blue-400">{{ $asset->asset_code }}</td>
                                <td class="p-3.5">
                                    <div class="font-bold text-slate-200">{{ $asset->name }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        <i class="fa-solid fa-chair text-slate-500 mr-1"></i>{{ $asset->seat_label }}
                                        <span class="text-slate-600 mx-1">•</span>
                                        {{ str_replace('Laboratorium Komputer', 'Lab', $asset->lab->name ?? '-') }}
                                    </div>
                                </td>
                                <td class="p-3.5">
                                    <div class="font-semibold text-slate-300">{{ $asset->cpu_spec }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $asset->ram_gb }} GB {{ $asset->ram_type }} | {{ $asset->storage_primary }}</div>
                                </td>
                                <td class="p-3.5 font-mono text-[11px] text-slate-400">
                                    <div>{{ $asset->ip_address ?? '-' }}</div>
                                    <div class="text-slate-500">{{ $asset->mac_address ?? '-' }}</div>
                                </td>
                                <td class="p-3.5">
                                    @php
                                        $badge = match ($asset->status) {
                                            'Ready' => ['emerald', 'fa-check'],
                                            'Degraded' => ['amber', 'fa-triangle-exclamation'],
                                            default => ['red', 'fa-wrench'],
                                        };
                                    @endphp
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full bg-{{ $badge[0] }}-500/20 text-{{ $badge[0] }}-400 border border-{{ $badge[0] }}-500/30 inline-flex items-center gap-1">
                                        <i class="fa-solid {{ $badge[1] }}"></i> {{ $asset->status }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-center pr-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('assets.show', $asset) }}" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded border border-slate-700 text-[11px] transition-all" title="Detail">
                                            <i class="fa-solid fa-eye text-blue-400"></i>
                                        </a>
                                        @if ($canManage)
                                            <a href="{{ route('assets.edit', $asset) }}" class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded border border-slate-700 text-[11px] transition-all" title="Edit">
                                                <i class="fa-solid fa-pen text-amber-400"></i>
                                            </a>
                                            <button @click="$dispatch('open-modal', 'confirmDelete-{{ $asset->id }}')" class="px-2.5 py-1 bg-slate-800 hover:bg-red-500/20 text-slate-200 rounded border border-slate-700 text-[11px] transition-all" title="Hapus">
                                                <i class="fa-solid fa-trash text-red-400"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canManage ? 7 : 6 }}" class="p-10 text-center">
                                    <i class="fa-solid fa-hard-drive text-3xl text-slate-600 mb-2 block"></i>
                                    <p class="text-sm font-semibold text-slate-400">Belum ada data aset</p>
                                    <p class="text-[11px] text-slate-500 mt-1">Gunakan tombol Tambah Aset atau Import Excel untuk menambahkan data.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="flex flex-wrap items-center justify-between gap-3 relative z-30">
            <form method="GET" action="{{ route('assets.index') }}" class="flex flex-wrap items-center gap-2 text-[11px] text-slate-400">
                @foreach (['lab_id', 'status', 'category', 'search'] as $f)
                    @if (($filters[$f] ?? '') !== '')
                        <input type="hidden" name="{{ $f }}" value="{{ $filters[$f] }}">
                    @endif
                @endforeach
                <span>Tampilkan</span>
                <x-select name="per_page" direction="up" :options="[10 => '10', 30 => '30', 50 => '50', 100 => '100']" selected="{{ $assets->perPage() }}" placeholder="10" submit />
                <span>per halaman</span>
                <span class="text-slate-600">•</span>
                <span>{{ $assets->firstItem() ?? 0 }}–{{ $assets->lastItem() ?? 0 }} dari <span class="text-slate-200 font-semibold">{{ $assets->total() }}</span> data</span>
            </form>

            <div class="flex justify-end">
                {{ $assets->links() }}
            </div>
        </div>

        <!-- Modal: Import Excel -->
        <x-modal name="modalImport" maxWidth="md" :show="false" focusable>
            <form method="POST" action="{{ route('assets.import') }}" enctype="multipart/form-data" class="p-5 space-y-4 text-xs">
                @csrf
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-sm text-white flex items-center gap-2">
                        <i class="fa-solid fa-file-excel text-emerald-400"></i> Import Data Bulk Excel
                    </h3>
                    <button type="button" @click="$dispatch('close-modal', 'modalImport')" class="text-slate-400 hover:text-white p-1">
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <p class="text-slate-300">Unggah file template <span class="font-mono text-emerald-400">.xlsx</span> yang sudah diisi sesuai format data aset PC & periferal.</p>

                <div class="border-2 border-dashed border-slate-700 hover:border-emerald-500 p-6 rounded-xl text-center cursor-pointer transition-colors" onclick="document.getElementById('assetImportFile').click()">
                    <i class="fa-solid fa-cloud-arrow-up text-3xl text-emerald-400 mb-2"></i>
                    <p class="font-semibold text-slate-200">Klik untuk pilih file Excel</p>
                    <p class="text-[11px] text-slate-400 mt-1">Format didukung: .xlsx, .xls</p>
                    <input type="file" id="assetImportFile" name="file" class="hidden" accept=".xlsx,.xls" required>
                </div>

                @error('file')
                    <p class="text-red-400 text-[11px]">{{ $message }}</p>
                @enderror

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="$dispatch('close-modal', 'modalImport')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-500 shadow-lg shadow-emerald-600/20">Import Data</button>
                </div>
            </form>
        </x-modal>

        <!-- Modal: Bulk Delete Confirm -->
        @if ($canManage)
            <x-modal name="confirmBulkDelete" maxWidth="sm" :show="false">
                <div class="p-5 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center text-xl mb-3">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white">Hapus Aset Terpilih?</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        <span class="text-red-400 font-bold" x-text="selected.length"></span> aset yang dipilih akan dihapus permanen.
                    </p>
                    <form method="POST" action="{{ route('assets.bulk-destroy') }}" class="mt-4 flex justify-center gap-2">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="ids[]" :value="id">
                        </template>
                        <button type="button" @click="$dispatch('close-modal', 'confirmBulkDelete')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-500 shadow-lg shadow-red-600/20">Ya, Hapus Semua</button>
                    </form>
                </div>
            </x-modal>
        @endif

        <!-- Modals: Delete Confirm -->
        @foreach ($assets as $asset)
            <x-modal name="confirmDelete-{{ $asset->id }}" maxWidth="sm" :show="false">
                <div class="p-5 text-center">
                    <div class="mx-auto w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center text-xl mb-3">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <h3 class="font-bold text-sm text-white">Hapus Aset Ini?</h3>
                    <p class="text-xs text-slate-400 mt-1">
                        <span class="font-mono text-slate-200">{{ $asset->asset_code }}</span> — {{ $asset->name }} akan dihapus permanen.
                    </p>
                    <form method="POST" action="{{ route('assets.destroy', $asset) }}" class="mt-4 flex justify-center gap-2">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="$dispatch('close-modal', 'confirmDelete-{{ $asset->id }}')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-500 shadow-lg shadow-red-600/20">Ya, Hapus</button>
                    </form>
                </div>
            </x-modal>
        @endforeach
    </div>
</x-app-layout>