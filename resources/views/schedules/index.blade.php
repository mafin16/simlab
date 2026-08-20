<x-app-layout>
    @section('title', 'Jadwal & Booking Lab')
    @section('subtitle', 'Kalender praktikum mingguan dan pengajuan booking insidental')

    @php
        $canManage = in_array(auth()->user()->role, ['super_admin', 'teknisi']);
        $canBook = in_array(auth()->user()->role, ['super_admin', 'teknisi', 'instruktur']);

        $labOptions = $labs->mapWithKeys(fn ($lab) => [$lab->id => str_replace('Laboratorium Komputer', 'Lab', $lab->name)])->all();

        $dayMeta = [
            'Monday' => ['label' => 'Senin', 'cell' => 'bg-blue-600/10 border-blue-500/20 text-blue-300'],
            'Tuesday' => ['label' => 'Selasa', 'cell' => 'bg-indigo-600/10 border-indigo-500/20 text-indigo-300'],
            'Wednesday' => ['label' => 'Rabu', 'cell' => 'bg-blue-600/10 border-blue-500/20 text-blue-300'],
            'Thursday' => ['label' => 'Kamis', 'cell' => 'bg-emerald-600/10 border-emerald-500/20 text-emerald-300'],
            'Friday' => ['label' => 'Jumat', 'cell' => 'bg-amber-600/10 border-amber-500/20 text-amber-300'],
            'Saturday' => ['label' => 'Sabtu', 'cell' => 'bg-purple-600/10 border-purple-500/20 text-purple-300'],
        ];

        $startWeek = \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endWeek = $startWeek->copy()->addDays(5);
        $bulan = ['1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April', '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus', '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
        $periode = $startWeek->format('d').' – '.$endWeek->format('d').' '.$bulan[(string) $endWeek->format('n')].' '.$endWeek->format('Y');
    @endphp

    <div class="space-y-4" x-data='{
        schedDay: {{ json_encode(old('day_name', 'Monday'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }},
        schedStart: {{ json_encode(old('start_time', '07:00'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }},
        schedEnd: {{ json_encode(old('end_time', '09:15'), JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) }},
        deleteSched: { id: 0, label: "" },
        deleteBooking: { id: 0, label: "" },
        prefillAdd(day, start, end) {
            this.schedDay = day;
            this.schedStart = start;
            this.schedEnd = end;
            $dispatch("open-modal", "modalAddSchedule");
        },
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

        @if (! $selectedLab)
            <div class="glass-panel p-10 rounded-xl text-center">
                <i class="fa-solid fa-calendar-days text-3xl text-slate-600 mb-2 block"></i>
                <p class="text-sm font-semibold text-slate-400">Belum ada laboratorium terdaftar.</p>
            </div>
        @else
            <!-- Header Panel -->
            <div class="glass-panel p-4 rounded-xl flex flex-wrap items-center justify-between gap-4 relative z-30">
                <div>
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-calendar-days text-blue-400"></i> Jadwal Pemakaian <span class="text-blue-400">{{ str_replace('Laboratorium Komputer', 'Lab', $selectedLab->name) }}</span>
                    </h3>
                    <p class="text-xs text-slate-400">Minggu Ini ({{ $periode }})</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <form method="GET" action="{{ route('schedules.index') }}" class="flex items-center gap-2">
                        <x-select name="lab_id" :options="$labOptions" selected="{{ $selectedLab->id }}" submit />
                    </form>

                    @if ($canBook)
                        <button @click="$dispatch('open-modal', 'modalBooking')" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-3.5 py-2 rounded-lg flex items-center gap-1.5 transition-all shadow-lg shadow-indigo-600/20">
                            <i class="fa-solid fa-calendar-plus"></i> Pengajuan Booking Insidental
                        </button>
                    @endif
                </div>
            </div>

            <!-- Weekly Timetable Grid -->
            <div class="glass-panel rounded-xl p-4 overflow-x-auto custom-scrollbar border border-slate-800 relative z-20">
                <div class="min-w-[860px]">
                    <div class="grid grid-cols-7 gap-2 mb-3 text-center text-xs font-bold text-slate-300 border-b border-slate-800 pb-2">
                        <div class="text-slate-500">Jam / Waktu</div>
                        @foreach ($dayMeta as $meta)
                            <div class="p-1 rounded bg-slate-800/50">{{ $meta['label'] }}</div>
                        @endforeach
                    </div>

                    @forelse ($ranges as $range)
                        @php
                            $dayCells = [];
                            foreach (array_keys($dayMeta) as $day) {
                                $dayCells[$day] = $scheduleMap->get($day.'|'.$range['start']) ?? $bookingMap->get($day.'|'.$range['start']);
                            }
                        @endphp
                        <div class="grid grid-cols-7 gap-2 items-stretch text-xs text-center mb-2">
                            <div class="p-2 font-mono font-semibold text-slate-400 bg-slate-900 rounded border border-slate-800 flex items-center justify-center">
                                {{ $range['start'] }}<span class="mx-0.5">–</span>{{ $range['end'] }}
                            </div>

                            @foreach ($dayMeta as $day => $meta)
                                @php $entry = $dayCells[$day]; @endphp
                                <div>
                                    @if ($entry && isset($entry->subject_name))
                                        <div class="relative group h-full p-2.5 rounded-lg {{ $meta['cell'] }} border text-left leading-snug">
                                            <p class="font-semibold text-[11px] truncate">{{ $entry->subject_name }}</p>
                                            <p class="text-[10px] opacity-80 truncate">{{ $entry->class_group }}</p>
                                            <p class="text-[10px] opacity-60 truncate">{{ $entry->instructor_name }}</p>
                                            @if ($canManage)
                                                <div class="absolute top-1 right-1 hidden group-hover:flex gap-1">
                                                    <button @click="$dispatch('open-modal', 'editSchedule-{{ $entry->id }}')" class="w-5 h-5 bg-slate-900/80 rounded text-blue-300 text-[9px] hover:text-blue-200" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                                    <button @click="deleteSched = { id: {{ $entry->id }}, label: '{{ addslashes($entry->subject_name) }}' }; $dispatch('open-modal', 'confirmDeleteSchedule')" class="w-5 h-5 bg-slate-900/80 rounded text-red-400 text-[9px] hover:text-red-300" title="Hapus"><i class="fa-solid fa-trash"></i></button>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif ($entry && isset($entry->event_name))
                                        <div class="h-full p-2.5 rounded-lg bg-emerald-600/10 border border-emerald-500/20 text-emerald-300 text-left leading-snug" title="Booking oleh {{ $entry->applicant_name }}">
                                            <p class="font-semibold text-[11px] truncate">{{ $entry->event_name }}</p>
                                            <p class="text-[10px] opacity-80 truncate">{{ $entry->applicant_name }}</p>
                                            <span class="inline-block mt-0.5 px-1.5 py-0.5 bg-emerald-500/15 rounded text-[9px] font-bold uppercase">Booking</span>
                                        </div>
                                    @elseif ($canManage)
                                        <button @click="prefillAdd('{{ $day }}', '{{ $range['start'] }}', '{{ $range['end'] }}')"
                                                class="w-full h-full min-h-[52px] p-2.5 rounded-lg border border-dashed border-slate-700 hover:border-blue-500/50 text-slate-500 hover:text-blue-400 transition-colors text-[10px] font-medium">
                                            <i class="fa-solid fa-plus mr-1"></i> Tambah
                                        </button>
                                    @else
                                        <div class="h-full min-h-[52px] p-2.5 rounded-lg border border-dashed border-slate-800 text-slate-700 text-[10px]">Kosong</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="p-10 text-center">
                            <i class="fa-solid fa-calendar-days text-3xl text-slate-600 mb-2 block"></i>
                            <p class="text-sm font-semibold text-slate-400">Belum ada jadwal</p>
                            <p class="text-[11px] text-slate-500 mt-1">Klik tombol <span class="text-slate-300">+ Tambah</span> pada cell kosong untuk mengisi jadwal mingguan.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Booking List -->
            <div class="glass-panel p-5 rounded-xl border border-slate-800">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-indigo-400"></i> Daftar Booking Mingguan
                    </h3>
                    <span class="text-[11px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-medium">{{ $bookings->count() }} booking</span>
                </div>

                @forelse ($bookings as $booking)
                    <div class="flex flex-wrap items-center justify-between gap-3 p-3 rounded-lg bg-slate-900/80 border border-slate-800 mb-2">
                        <div class="min-w-0 flex items-center gap-3">
                            <div class="text-center shrink-0 w-24">
                                <p class="text-xs font-mono font-bold text-indigo-300 truncate">{{ $dayMeta[$booking->day_name]['label'] ?? $booking->day_name }}</p>
                                <p class="text-[10px] font-mono text-slate-400">{{ $booking->start_time->format('H:i') }}–{{ $booking->end_time->format('H:i') }}</p>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-slate-200 truncate">{{ $booking->event_name }}</p>
                                <p class="text-[11px] text-slate-400 truncate">Oleh: {{ $booking->applicant_name }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-2 py-1 rounded text-[10px] font-bold {{ $booking->status === 'APPROVED' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25' : 'bg-red-500/15 text-red-400 border border-red-500/25' }}">
                                {{ $booking->status }}
                            </span>
                            @if ($canManage)
                                @if ($booking->status !== 'APPROVED')
                                    <form method="POST" action="{{ route('bookings.status', $booking) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="APPROVED">
                                        <button class="px-2.5 py-1 bg-emerald-500/15 hover:bg-emerald-500/25 text-emerald-400 rounded border border-emerald-500/25 text-[11px] transition-all">Setujui</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('bookings.status', $booking) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="REJECTED">
                                        <button class="px-2.5 py-1 bg-amber-500/15 hover:bg-amber-500/25 text-amber-400 rounded border border-amber-500/25 text-[11px] transition-all">Tolak</button>
                                    </form>
                                @endif
                                <button @click="deleteBooking = { id: {{ $booking->id }}, label: '{{ addslashes($booking->event_name) }}' }; $dispatch('open-modal', 'confirmDeleteBooking')" class="px-2.5 py-1 bg-slate-800 hover:bg-red-500/20 text-red-400 rounded border border-slate-700 text-[11px] transition-all" title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <i class="fa-solid fa-calendar-plus text-2xl text-slate-600 mb-2 block"></i>
                        <p class="text-xs font-semibold text-slate-400">Belum ada booking</p>
                        <p class="text-[11px] text-slate-500 mt-1">Gunakan tombol "Pengajuan Booking Insidental" untuk membuat booking.</p>
                    </div>
                @endforelse
            </div>
        @endif

        <!-- Modal: Booking Insidental -->
        @if ($canBook && $selectedLab)
            <x-modal name="modalBooking" maxWidth="lg" :show="false" focusable>
                <form method="POST" action="{{ route('bookings.store') }}" class="p-5 space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="lab_id" value="{{ $selectedLab->id }}">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <i class="fa-solid fa-calendar-plus text-indigo-400"></i> Pengajuan Booking Ruang Lab
                        </h3>
                        <button type="button" @click="$dispatch('close-modal', 'modalBooking')" class="text-slate-400 hover:text-white p-1">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>

                    <div>
                        <x-input-label value="Nama Pemohon / Guru / Kegiatan (*)" />
                        <input type="text" name="applicant_name" value="{{ old('applicant_name') }}" placeholder="misal: Pak Hendra (Workshop Sertifikasi Cyber)" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                        <x-input-error :messages="$errors->get('applicant_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label value="Nama Kegiatan / Acara (*)" />
                        <input type="text" name="event_name" value="{{ old('event_name') }}" placeholder="misal: Workshop Sertifikasi" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                        <x-input-error :messages="$errors->get('event_name')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Hari (Berulang Tiap Minggu) (*)" />
                            <select name="day_name" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                @foreach ($dayMeta as $key => $meta)
                                    <option value="{{ $key }}" @selected(old('day_name', 'Monday') === $key)>{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('day_name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Laboratorium" />
                            <input type="text" value="{{ str_replace('Laboratorium Komputer', 'Lab', $selectedLab->name) }}" disabled class="mt-1 w-full bg-slate-900/50 text-slate-400 p-2.5 rounded-lg border border-slate-700">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Jam Mulai (*)" />
                            <input type="time" name="start_time" value="{{ old('start_time', '08:00') }}" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                            <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Jam Selesai (*)" />
                            <input type="time" name="end_time" value="{{ old('end_time', '11:30') }}" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                            <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                        </div>
                    </div>

                    <p class="text-[10px] text-slate-500 leading-relaxed">
                        <i class="fa-solid fa-circle-info text-blue-400 mr-1"></i>
                        Booking bersifat berulang tiap minggu pada hari yang sama. Akan ditolak otomatis jika bentrok dengan jadwal/boking lain.
                    </p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="$dispatch('close-modal', 'modalBooking')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-500 shadow-lg shadow-indigo-600/20">Cek & Ajukan Booking</button>
                    </div>
                </form>
            </x-modal>
        @endif

        <!-- Modal: Tambah Jadwal -->
        @if ($canManage && $selectedLab)
            <x-modal name="modalAddSchedule" maxWidth="lg" :show="false" focusable>
                <form method="POST" action="{{ route('schedules.store') }}" class="p-5 space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="lab_id" value="{{ $selectedLab->id }}">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <h3 class="font-bold text-sm text-white flex items-center gap-2">
                            <i class="fa-solid fa-calendar-plus text-blue-400"></i> Tambah Jadwal Praktikum
                        </h3>
                        <button type="button" @click="$dispatch('close-modal', 'modalAddSchedule')" class="text-slate-400 hover:text-white p-1">
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label value="Mata Pelajaran (*)" />
                            <input type="text" name="subject_name" value="{{ old('subject_name') }}" placeholder="misal: Pemrograman Web" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                            <x-input-error :messages="$errors->get('subject_name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Kelas / Group (*)" />
                            <input type="text" name="class_group" value="{{ old('class_group') }}" placeholder="misal: XII RPL 1" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                            <x-input-error :messages="$errors->get('class_group')" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Guru Pengampu (*)" />
                        <input type="text" name="instructor_name" value="{{ old('instructor_name') }}" placeholder="misal: Pak Hendra, S.Kom" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                        <x-input-error :messages="$errors->get('instructor_name')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <x-input-label value="Hari (*)" />
                            <select name="day_name" x-model="schedDay" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                @foreach ($dayMeta as $key => $meta)
                                    <option value="{{ $key }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('day_name')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Jam Mulai (*)" />
                            <input type="time" name="start_time" x-model="schedStart" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                            <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label value="Jam Selesai (*)" />
                            <input type="time" name="end_time" x-model="schedEnd" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                            <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="$dispatch('close-modal', 'modalAddSchedule')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-500 shadow-lg shadow-blue-600/20">Simpan Jadwal</button>
                    </div>
                </form>
            </x-modal>
        @endif

        <!-- Modal: Edit Jadwal (per jadwal) -->
        @if ($canManage && $selectedLab)
            @foreach ($scheduleMap as $entry)
                <x-modal name="editSchedule-{{ $entry->id }}" maxWidth="lg" :show="false">
                    <form method="POST" action="{{ route('schedules.update', $entry) }}" class="p-5 space-y-4 text-xs">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="lab_id" value="{{ $entry->lab_id }}">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 class="font-bold text-sm text-white flex items-center gap-2">
                                <i class="fa-solid fa-pen text-blue-400"></i> Edit Jadwal
                            </h3>
                            <button type="button" @click="$dispatch('close-modal', 'editSchedule-{{ $entry->id }}')" class="text-slate-400 hover:text-white p-1">
                                <i class="fa-solid fa-xmark text-base"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-input-label value="Mata Pelajaran (*)" />
                                <input type="text" name="subject_name" value="{{ old('subject_name', $entry->subject_name) }}" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                <x-input-error :messages="$errors->get('subject_name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Kelas / Group (*)" />
                                <input type="text" name="class_group" value="{{ old('class_group', $entry->class_group) }}" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                <x-input-error :messages="$errors->get('class_group')" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <x-input-label value="Guru Pengampu (*)" />
                            <input type="text" name="instructor_name" value="{{ old('instructor_name', $entry->instructor_name) }}" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                            <x-input-error :messages="$errors->get('instructor_name')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <x-input-label value="Hari (*)" />
                                <select name="day_name" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                    @foreach ($dayMeta as $key => $meta)
                                        <option value="{{ $key }}" @selected(old('day_name', $entry->day_name) === $key)>{{ $meta['label'] }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('day_name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Jam Mulai (*)" />
                                <input type="time" name="start_time" value="{{ old('start_time', $entry->start_time->format('H:i')) }}" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Jam Selesai (*)" />
                                <input type="time" name="end_time" value="{{ old('end_time', $entry->end_time->format('H:i')) }}" required class="mt-1 w-full bg-slate-900 text-slate-200 p-2.5 rounded-lg border border-slate-700 focus:outline-none focus:border-blue-500">
                                <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                            </div>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="$dispatch('close-modal', 'editSchedule-{{ $entry->id }}')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-500 shadow-lg shadow-blue-600/20">Simpan Perubahan</button>
                        </div>
                    </form>
                </x-modal>
            @endforeach
        @endif

        <!-- Modal: Konfirmasi Hapus Jadwal -->
        <x-modal name="confirmDeleteSchedule" maxWidth="sm" :show="false">
            <div class="p-5 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center text-xl mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="font-bold text-sm text-white">Hapus Jadwal Ini?</h3>
                <p class="text-xs text-slate-400 mt-1">
                    <span class="text-slate-200 font-bold" x-text="deleteSched.label"></span> akan dihapus permanen.
                </p>
                <form method="POST" :action="'/schedules/' + deleteSched.id" class="mt-4 flex justify-center gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="$dispatch('close-modal', 'confirmDeleteSchedule')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-500 shadow-lg shadow-red-600/20">Ya, Hapus</button>
                </form>
            </div>
        </x-modal>

        <!-- Modal: Konfirmasi Hapus Booking -->
        <x-modal name="confirmDeleteBooking" maxWidth="sm" :show="false">
            <div class="p-5 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-500/10 border border-red-500/20 text-red-400 flex items-center justify-center text-xl mb-3">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <h3 class="font-bold text-sm text-white">Hapus Booking Ini?</h3>
                <p class="text-xs text-slate-400 mt-1">
                    <span class="text-slate-200 font-bold" x-text="deleteBooking.label"></span> akan dihapus permanen.
                </p>
                <form method="POST" :action="'/bookings/' + deleteBooking.id" class="mt-4 flex justify-center gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="$dispatch('close-modal', 'confirmDeleteBooking')" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-500 shadow-lg shadow-red-600/20">Ya, Hapus</button>
                </form>
            </div>
        </x-modal>
    </div>
</x-app-layout>