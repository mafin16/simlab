<x-guest-layout>
    <div class="space-y-4">
        <!-- Identitas PC -->
        <div class="text-center space-y-1">
            <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold">Presensi Praktikum</p>
            <h2 class="font-mono text-xl font-bold text-white">{{ $asset->asset_code }}</h2>
            <p class="text-xs text-slate-400">
                {{ str_replace('Laboratorium Komputer', 'Lab', $asset->lab->name) }} &middot; {{ $asset->seat_label }}
            </p>
        </div>

        @if (session('checkout_success'))
            <!-- State: berhasil check-out -->
            <div class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-900/30 text-center space-y-2">
                <i class="fa-solid fa-circle-check text-3xl text-emerald-400"></i>
                <p class="text-sm font-bold text-emerald-300">Check-out berhasil!</p>
                <p class="text-xs text-slate-300">Sampai jumpa, <span class="font-semibold">{{ session('checkout_success.fullname') }}</span>.</p>
                <p class="text-[11px] text-slate-500">Waktu keluar: {{ session('checkout_success.time') }} WIB</p>
            </div>
            <a href="{{ route('checkin.show', $asset->asset_code) }}"
               class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-all">
                <i class="fa-solid fa-rotate-right"></i> Check-in Lagi
            </a>
        @elseif (session('presence_success'))
            @php
                $success = session('presence_success');
            @endphp

            <!-- State: berhasil check-in -->
            <div class="p-4 rounded-xl border border-emerald-500/30 bg-emerald-900/30 text-center space-y-2">
                <i class="fa-solid fa-chair text-3xl text-emerald-400"></i>
                <p class="text-sm font-bold text-emerald-300">Selamat datang, {{ $success['fullname'] }}!</p>
                <p class="text-xs text-slate-300">
                    Kamu tercatat di <span class="font-mono font-semibold">{{ $asset->asset_code }}</span> sejak {{ $success['time'] }} WIB.
                </p>
                @if ($success['moved'])
                    <p class="text-[11px] text-amber-300/90"><i class="fa-solid fa-right-left"></i> Sesi sebelumnya di PC lain otomatis ditutup.</p>
                @endif
            </div>

            @if ($asset->status === 'Degraded')
                <div class="p-3 rounded-xl border border-amber-500/30 bg-amber-900/30 text-amber-300 text-xs flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <span>PC ini sedang bermasalah ringan (Degraded). Jika mengganggu praktikum, mohon laporkan ke teknisi.</span>
                </div>
            @endif

            <form method="POST" action="{{ route('checkin.checkout', $asset->asset_code) }}" class="space-y-3">
                @csrf
                <input type="hidden" name="user_identifier" value="{{ $success['identifier'] }}">
                <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-500 text-white font-bold text-xs py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-lg shadow-red-600/20">
                    <i class="fa-solid fa-right-from-bracket"></i> Check-out Sekarang
                </button>
            </form>
        @elseif ($blocked)
            <!-- State: PC tidak bisa dipresensi -->
            <div class="p-4 rounded-xl border border-red-500/30 bg-red-900/30 text-center space-y-2">
                <i class="fa-solid fa-ban text-3xl text-red-400"></i>
                <p class="text-sm font-bold text-red-300">PC tidak dapat digunakan</p>
                <p class="text-xs text-slate-300">
                    {{ $asset->asset_code }} sedang dalam status <span class="font-semibold">{{ $asset->status }}</span> dan sedang diperbaiki teknisi.
                </p>
                <p class="text-[11px] text-slate-500">Silakan gunakan PC lain yang tersedia.</p>
            </div>
        @else
            <!-- State: form check-in -->
            @if (session('error'))
                <div class="p-3.5 rounded-xl border border-red-500/30 bg-red-900/30 text-red-300 text-xs font-medium flex items-start gap-2">
                    <i class="fa-solid fa-circle-xmark mt-0.5"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if ($asset->status === 'Degraded')
                <div class="p-3 rounded-xl border border-amber-500/30 bg-amber-900/30 text-amber-300 text-xs flex items-start gap-2">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                    <span>PC ini berstatus Degraded (rusak ringan) — masih bisa dipakai, tapi mohon maklum jika ada kendala.</span>
                </div>
            @endif

            <form method="POST" action="{{ route('checkin.store', $asset->asset_code) }}" class="space-y-3">
                @csrf
                <div>
                    <label for="user_identifier" class="block text-xs font-semibold text-slate-300 mb-1.5">NISN / NIM / Identitas</label>
                    <input id="user_identifier" name="user_identifier" type="text" required minlength="3" maxlength="100"
                           value="{{ old('user_identifier') }}" placeholder="Contoh: 20260012"
                           class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500 placeholder:text-slate-600">
                    @error('user_identifier')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="user_fullname" class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap</label>
                    <input id="user_fullname" name="user_fullname" type="text" required minlength="3" maxlength="100"
                           value="{{ old('user_fullname') }}" placeholder="Contoh: Rina Amelia Putri"
                           class="w-full rounded-lg border-slate-700 bg-slate-950/60 text-slate-100 text-sm focus:border-blue-500 focus:ring-blue-500 placeholder:text-slate-600">
                    @error('user_fullname')
                        <p class="mt-1 text-[11px] text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs py-2.5 rounded-lg flex items-center justify-center gap-1.5 transition-all shadow-lg shadow-blue-600/20">
                    <i class="fa-solid fa-fingerprint"></i> Check-in Sekarang
                </button>
            </form>
        @endif

        <p class="text-center text-[10px] text-slate-600 pt-1">
            Satu siswa hanya boleh aktif di satu PC. Sesi otomatis ditutup saat jam praktikum berakhir.
        </p>
    </div>
</x-guest-layout>
