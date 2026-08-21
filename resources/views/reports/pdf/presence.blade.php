<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Log Presensi & Utilisasi</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #0f172a; }
        .header { text-align: center; border-bottom: 3px solid #7c3aed; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { font-size: 16px; letter-spacing: 1px; }
        .header p { font-size: 10px; color: #475569; margin-top: 3px; }
        .meta { width: 100%; margin-bottom: 12px; }
        .meta td { font-size: 10px; padding: 2px 0; vertical-align: top; }
        .meta td.label { width: 130px; font-weight: bold; color: #334155; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #7c3aed; color: #ffffff; font-size: 9px; padding: 6px 5px; text-align: left; border: 1px solid #6d28d9; }
        table.data td { font-size: 9px; padding: 5px; border: 1px solid #cbd5e1; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f5f3ff; }
        .ongoing { color: #7c3aed; font-style: italic; }
        .summary { margin-top: 14px; padding: 8px 10px; background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 4px; font-size: 10px; }
        .footer { margin-top: 20px; font-size: 9px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LOG PRESENSI &amp; UTILISASI LAB</h1>
        <p>SIMLAB &mdash; Sistem Pengelolaan Laboratorium Komputer</p>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Periode</td>
            <td>: {{ $from->format('d/m/Y') }} s.d. {{ $to->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Laboratorium</td>
            <td>: {{ $lab ? $lab->name : 'Semua Laboratorium' }}</td>
        </tr>
        <tr>
            <td class="label">Dicetak</td>
            <td>: {{ now()->format('d/m/Y H:i') }} WIB</td>
        </tr>
    </table>

    @if ($presences->isEmpty())
        <p style="text-align:center; padding:30px 0; color:#64748b;">Tidak ada sesi presensi pada periode ini.</p>
    @else
        @php
            $totalMenit = $presences->filter(fn ($p) => $p->check_out_time)->sum(fn ($p) => $p->check_in_time->diffInMinutes($p->check_out_time));
            $unik = $presences->unique('user_identifier')->count();
        @endphp

        <table class="data">
            <thead>
                <tr>
                    <th style="width:24px;">No</th>
                    <th style="width:55px;">Tanggal</th>
                    <th style="width:62px;">Kode PC</th>
                    <th style="width:95px;">Lab</th>
                    <th style="width:60px;">NISN/NIM</th>
                    <th style="width:110px;">Nama Lengkap</th>
                    <th style="width:42px;">Masuk</th>
                    <th style="width:42px;">Keluar</th>
                    <th>Durasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($presences as $i => $presence)
                    <tr>
                        <td>{{ $i + 1 }}.</td>
                        <td>{{ $presence->session_date->format('d/m/Y') }}</td>
                        <td>{{ $presence->asset->asset_code }}</td>
                        <td>{{ str_replace('Laboratorium Komputer', 'Lab', $presence->asset->lab->name) }}</td>
                        <td>{{ $presence->user_identifier }}</td>
                        <td>{{ $presence->user_fullname }}</td>
                        <td>{{ $presence->check_in_time->format('H:i') }}</td>
                        <td>{{ $presence->check_out_time?->format('H:i') ?? '-' }}</td>
                        <td>
                            @if ($presence->check_out_time)
                                @php($menit = $presence->check_in_time->diffInMinutes($presence->check_out_time))
                                {{ intdiv($menit, 60) }} jam {{ str_pad($menit % 60, 2, '0', STR_PAD_LEFT) }} mnt
                            @else
                                <span class="ongoing">Berlangsung</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <strong>Ringkasan:</strong> {{ $presences->count() }} sesi &bull;
            {{ $unik }} pengguna unik &bull;
            Total {{ intdiv($totalMenit, 60) }} jam {{ str_pad($totalMenit % 60, 2, '0', STR_PAD_LEFT) }} menit waktu praktikum tercatat
        </div>
    @endif

    <div class="footer">Dicetak otomatis oleh SIMLAB</div>
</body>
</html>
