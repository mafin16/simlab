<x-app-layout>
    @section('title', 'Detail Aset — '.$asset->asset_code)
    @section('subtitle', $asset->name.' • '.str_replace('Laboratorium Komputer', 'Lab', $asset->lab->name ?? '-'))

    <div class="space-y-4">
        @if (session('success'))
            <div class="p-3.5 rounded-xl border border-emerald-500/30 bg-emerald-900/30 text-emerald-300 text-xs font-medium flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-emerald-400"></i>
                {{ session('success') }}
            </div>
        @endif

        <!-- Header -->
        <div class="glass-panel p-4 rounded-xl flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-desktop"></i>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-white font-mono">{{ $asset->asset_code }} <span class="text-slate-400 font-sans font-semibold">- {{ $asset->name }}</span></h3>
                    <p class="text-xs text-slate-400">{{ $asset->lab->name ?? '-' }} | {{ $asset->seat_label }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
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
                @if (in_array(auth()->user()->role, ['super_admin', 'teknisi']))
                    <a href="{{ route('assets.edit', $asset) }}" class="bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 border border-amber-500/30 text-xs font-semibold px-3 py-2 rounded-lg flex items-center gap-1.5 transition-all">
                        <i class="fa-solid fa-pen"></i> Edit Aset
                    </a>
                @endif
                <a href="{{ route('assets.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold px-3 py-2 rounded-lg border border-slate-700 flex items-center gap-1.5 transition-all">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Hardware Specs -->
            <div class="glass-panel p-5 rounded-xl lg:col-span-2">
                <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-microchip text-blue-400"></i> Spesifikasi Hardware
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Processor</span>
                        <p class="font-semibold text-slate-200">{{ $asset->cpu_spec }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">RAM</span>
                        <p class="font-semibold text-slate-200">{{ $asset->ram_gb }} GB {{ $asset->ram_type }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Storage Utama</span>
                        <p class="font-semibold text-slate-200">{{ $asset->storage_primary }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Storage Sekunder</span>
                        <p class="font-semibold text-slate-200">{{ $asset->storage_secondary ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">GPU / VGA</span>
                        <p class="font-semibold text-slate-200">{{ $asset->gpu_spec ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Kategori</span>
                        <p class="font-semibold text-slate-200">{{ $asset->category }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">IP Address</span>
                        <p class="font-semibold text-slate-200 font-mono">{{ $asset->ip_address ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">MAC Address</span>
                        <p class="font-semibold text-slate-200 font-mono">{{ $asset->mac_address ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Serial Number</span>
                        <p class="font-semibold text-slate-200 font-mono">{{ $asset->serial_number ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Sumber Pengadaan</span>
                        <p class="font-semibold text-slate-200">{{ $asset->procurement_source ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Tanggal Pembelian</span>
                        <p class="font-semibold text-slate-200">{{ $asset->purchase_date?->format('d-m-Y') ?? '-' }}</p>
                    </div>
                    <div class="bg-slate-900/60 p-3 rounded-lg border border-slate-800">
                        <span class="text-slate-400 block mb-0.5">Masa Garansi</span>
                        <p class="font-semibold text-slate-200">{{ $asset->warranty_expiry?->format('d-m-Y') ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <!-- QR Code -->
            <div class="glass-panel p-5 rounded-xl">
                <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-qrcode text-emerald-400"></i> QR Code Aset
                </h3>
                <div class="bg-white p-4 rounded-xl inline-block shadow-md mx-auto block w-fit">
                    @php
                        $checkinUrl = route('checkin.show', ['asset_code' => $asset->asset_code]);
                    @endphp
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($checkinUrl) }}" alt="QR {{ $asset->asset_code }}" class="w-40 h-40">
                </div>
                <p class="text-[11px] text-slate-400 text-center mt-3">Scan QR untuk check-in presensi di unit ini.</p>
                <a href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={{ urlencode($checkinUrl) }}" target="_blank" class="mt-3 w-full bg-blue-600/20 text-blue-400 border border-blue-500/30 rounded-lg text-xs font-semibold py-2 flex items-center justify-center gap-1.5 hover:bg-blue-600/30 transition-all">
                    <i class="fa-solid fa-print"></i> Cetak QR Stiker
                </a>
            </div>
        </div>

        <!-- Peripherals -->
        <div class="glass-panel p-5 rounded-xl">
            <div class="flex flex-wrap items-center justify-between mb-4 gap-2">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-plug text-amber-400"></i> Periferal Terpasang
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-800 text-slate-400 font-medium">{{ $asset->peripherals->count() }} unit</span>
                </h3>
            </div>

            @if ($asset->peripherals->isEmpty())
                <div class="p-4 bg-slate-900/50 rounded-lg border border-slate-800 text-center text-xs text-slate-400">
                    <i class="fa-solid fa-plug-circle-xmark text-slate-500 text-lg mb-1 block"></i>
                    Belum ada periferal terpasang pada unit ini.
                </div>
            @else
                <div class="space-y-1.5">
                    @foreach ($asset->peripherals as $peripheral)
                        <div class="p-2.5 bg-slate-900 rounded-lg border border-slate-800 flex items-center justify-between gap-2 text-xs">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-200 truncate">
                                    <span class="font-mono text-amber-400">{{ $peripheral->peripheral_code }}</span>
                                    &mdash; {{ $peripheral->brand }} {{ $peripheral->model_name }}
                                </p>
                                <p class="text-[11px] text-slate-400">
                                    <span class="text-slate-500">{{ $peripheral->type }}</span>
                                    @if ($peripheral->serial_number) • SN: <span class="font-mono">{{ $peripheral->serial_number }}</span> @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @php
                                    $cond = match ($peripheral->condition) {
                                        'Baik / Normal' => 'emerald',
                                        'Perlu Penggantian' => 'amber',
                                        default => 'red',
                                    };
                                @endphp
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-{{ $cond }}-500/20 text-{{ $cond }}-400 border border-{{ $cond }}-500/30">{{ $peripheral->condition }}</span>
                                @if (in_array(auth()->user()->role, ['super_admin', 'teknisi']))
                                    <form method="POST" action="{{ route('assets.peripherals.destroy', $peripheral) }}" onsubmit="return confirm('Hapus periferal ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-300 p-1" title="Hapus periferal">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Add Peripheral -->
            @if (in_array(auth()->user()->role, ['super_admin', 'teknisi']))
                <form method="POST" action="{{ route('assets.peripherals.store', $asset) }}" class="mt-4 p-3 bg-slate-900/60 rounded-lg border border-slate-800 space-y-3" x-data="{ open: false }">
                    @csrf
                    <button type="button" @click="open = !open" class="text-xs text-blue-400 hover:text-blue-300 font-semibold flex items-center gap-1.5">
                        <i class="fa-solid" :class="open ? 'fa-minus' : 'fa-plus'"></i>
                        <span x-text="open ? 'Tutup Form' : 'Tambah Periferal'"></span>
                    </button>

                    <div x-show="open" x-cloak class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 pt-2 border-t border-slate-800">
                        <div>
                            <label class="block text-slate-400 mb-1 font-semibold text-[11px]">Jenis Periferal (*)</label>
                            <select name="type" required class="w-full bg-slate-900 text-slate-200 p-2 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500 text-xs">
                                @foreach (['Monitor', 'Keyboard', 'Mouse', 'Headset', 'Speaker', 'Webcam', 'UPS', 'Printer', 'Lainnya'] as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1 font-semibold text-[11px]">Merk (*)</label>
                            <input type="text" name="brand" placeholder="misal: LG / Logitech" required class="w-full bg-slate-900 text-slate-200 p-2 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500 text-xs">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1 font-semibold text-[11px]">Model</label>
                            <input type="text" name="model_name" placeholder="misal: 24MP400" class="w-full bg-slate-900 text-slate-200 p-2 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500 text-xs">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1 font-semibold text-[11px]">Serial Number</label>
                            <input type="text" name="serial_number" placeholder="SN-..." class="w-full bg-slate-900 text-slate-200 p-2 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500 text-xs">
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1 font-semibold text-[11px]">Kondisi</label>
                            <select name="condition" class="w-full bg-slate-900 text-slate-200 p-2 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500 text-xs">
                                <option value="Baik / Normal">Baik / Normal</option>
                                <option value="Perlu Penggantian">Perlu Penggantian</option>
                                <option value="Rusak">Rusak</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1 font-semibold text-[11px]">Catatan Lokasi</label>
                            <input type="text" name="location_note" placeholder="misal: Port HDMI depan" class="w-full bg-slate-900 text-slate-200 p-2 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500 text-xs">
                        </div>
                        <div class="sm:col-span-2 md:col-span-3 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-500 shadow-lg shadow-emerald-600/20 text-xs">
                                <i class="fa-solid fa-plug mr-1"></i> Simpan Periferal
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>