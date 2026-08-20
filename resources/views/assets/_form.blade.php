@php
    $asset = $asset ?? null;
    $old = fn ($field) => old($field, $asset?->$field);
    $input = 'w-full h-10 bg-slate-900 text-slate-200 px-3 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500 text-xs';
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-x-5 gap-y-4">

    <!-- ======================== Informasi Identitas ======================== -->
    <div class="md:col-span-2 xl:col-span-3">
        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-400 flex items-center gap-2">
            <i class="fa-solid fa-tag"></i> Informasi Identitas
        </p>
        <div class="border-t border-slate-800 mt-2"></div>
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Kode Aset (*)</label>
        <input type="text" name="asset_code" value="{{ $old('asset_code') }}" placeholder="misal: LAB1-PC-21" required class="{{ $input }}">
        @error('asset_code')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Nama Unit (*)</label>
        <input type="text" name="name" value="{{ $old('name') }}" placeholder="misal: PC Client 21" required class="{{ $input }}">
        @error('name')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Ruang Lab (*)</label>
        <select name="lab_id" required class="{{ $input }}">
            @foreach ($labs as $lab)
                <option value="{{ $lab->id }}" @selected((int) $old('lab_id') === $lab->id)>{{ str_replace('Laboratorium Komputer', 'Lab', $lab->name) }}</option>
            @endforeach
        </select>
        @error('lab_id')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Baris / Meja (*)</label>
        <input type="text" name="seat_label" value="{{ $old('seat_label') }}" placeholder="misal: Meja A-21" required class="{{ $input }}">
        @error('seat_label')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Kategori</label>
        <select name="category" class="{{ $input }}">
            @foreach (['PC Desktop', 'Server', 'Workstation'] as $cat)
                <option value="{{ $cat }}" @selected($old('category') === $cat)>{{ $cat }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Kondisi Status</label>
        <select name="status" class="{{ $input }}">
            @foreach (['Ready', 'Degraded', 'Maintenance', 'Scrapped'] as $st)
                <option value="{{ $st }}" @selected($old('status') === $st)>{{ $st }}</option>
            @endforeach
        </select>
    </div>

    <!-- ======================== Spesifikasi Hardware ======================== -->
    <div class="md:col-span-2 xl:col-span-3 mt-2">
        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-400 flex items-center gap-2">
            <i class="fa-solid fa-microchip"></i> Spesifikasi Hardware
        </p>
        <div class="border-t border-slate-800 mt-2"></div>
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Processor (CPU) (*)</label>
        <input type="text" name="cpu_spec" value="{{ $old('cpu_spec') }}" placeholder="misal: Intel i5-12400" required class="{{ $input }}">
        @error('cpu_spec')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">GPU / VGA</label>
        <input type="text" name="gpu_spec" value="{{ $old('gpu_spec') }}" placeholder="misal: Intel UHD Graphics 730" class="{{ $input }}">
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">RAM (GB) (*)</label>
        <input type="number" name="ram_gb" value="{{ $old('ram_gb') }}" placeholder="16" required min="1" class="{{ $input }}">
        @error('ram_gb')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Tipe RAM</label>
        <input type="text" name="ram_type" value="{{ $old('ram_type') }}" placeholder="misal: DDR4" class="{{ $input }}">
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Storage Utama (*)</label>
        <input type="text" name="storage_primary" value="{{ $old('storage_primary') }}" placeholder="512GB NVMe SSD" required class="{{ $input }}">
        @error('storage_primary')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Storage Sekunder</label>
        <input type="text" name="storage_secondary" value="{{ $old('storage_secondary') }}" placeholder="HDD 500GB (opsional)" class="{{ $input }}">
    </div>

    <!-- ======================== Jaringan & Inventaris ======================== -->
    <div class="md:col-span-2 xl:col-span-3 mt-2">
        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-400 flex items-center gap-2">
            <i class="fa-solid fa-network-wired"></i> Jaringan & Inventaris
        </p>
        <div class="border-t border-slate-800 mt-2"></div>
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">IP Address</label>
        <input type="text" name="ip_address" value="{{ $old('ip_address') }}" placeholder="192.168.10.31" class="{{ $input }}">
        @error('ip_address')<p class="text-red-400 text-[11px] mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">MAC Address</label>
        <input type="text" name="mac_address" value="{{ $old('mac_address') }}" placeholder="00:1A:2B:3C:01:21" class="{{ $input }}">
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Serial Number</label>
        <input type="text" name="serial_number" value="{{ $old('serial_number') }}" placeholder="SN-LAB1-PC-21" class="{{ $input }}">
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Sumber Pengadaan</label>
        <input type="text" name="procurement_source" value="{{ $old('procurement_source') }}" placeholder="Dana BOS / APBD" class="{{ $input }}">
    </div>

    <!-- ======================== Masa Berlaku ======================== -->
    <div class="md:col-span-2 xl:col-span-3 mt-2">
        <p class="text-[11px] font-bold uppercase tracking-wider text-blue-400 flex items-center gap-2">
            <i class="fa-solid fa-calendar-check"></i> Masa Berlaku
        </p>
        <div class="border-t border-slate-800 mt-2"></div>
    </div>

    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Tanggal Pembelian</label>
        <input type="date" name="purchase_date" value="{{ $old('purchase_date') ? \Illuminate\Support\Carbon::parse($old('purchase_date'))->format('Y-m-d') : '' }}" class="{{ $input }}">
    </div>
    <div>
        <label class="block text-slate-400 mb-1.5 font-semibold">Masa Garansi Hingga</label>
        <input type="date" name="warranty_expiry" value="{{ $old('warranty_expiry') ? \Illuminate\Support\Carbon::parse($old('warranty_expiry'))->format('Y-m-d') : '' }}" class="{{ $input }}">
    </div>
</div>