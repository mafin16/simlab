<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Pemeliharaan</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: DejaVu Sans, sans-serif; }
        body { font-size: 10px; color: #0f172a; }
        .header { text-align: center; border-bottom: 3px solid #1d4ed8; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { font-size: 16px; letter-spacing: 1px; }
        .header p { font-size: 10px; color: #475569; margin-top: 3px; }
        .meta { width: 100%; margin-bottom: 12px; }
        .meta td { font-size: 10px; padding: 2px 0; vertical-align: top; }
        .meta td.label { width: 130px; font-weight: bold; color: #334155; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1d4ed8; color: #ffffff; font-size: 9px; padding: 6px 5px; text-align: left; border: 1px solid #1e40af; }
        table.data td { font-size: 9px; padding: 5px; border: 1px solid #cbd5e1; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f1f5f9; }
        .sla-ok { color: #047857; font-weight: bold; }
        .sla-late { color: #b91c1c; font-weight: bold; }
        .summary { margin-top: 14px; padding: 8px 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; font-size: 10px; }
        .footer { margin-top: 20px; font-size: 9px; color: #64748b; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN RIWAYAT PEMELIHARAAN</h1>
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

    @if ($tickets->isEmpty())
        <p style="text-align:center; padding:30px 0; color:#64748b;">Tidak ada tiket pemeliharaan yang diselesaikan pada periode ini.</p>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th style="width:22px;">No</th>
                    <th style="width:60px;">Kode Tiket</th>
                    <th style="width:62px;">PC / Lab</th>
                    <th style="width:120px;">Komponen Rusak</th>
                    <th style="width:42px;">Prio</th>
                    <th style="width:80px;">Teknisi</th>
                    <th style="width:58px;">Selesai</th>
                    <th style="width:45px;">Durasi</th>
                    <th style="width:48px;">SLA</th>
                    <th>Solusi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tickets as $i => $ticket)
                    @php
                        $jam = ceil(($ticket->resolved_at->getTimestamp() - $ticket->reported_at->getTimestamp()) / 3600);
                        $tepat = $jam <= \App\Models\Ticket::SLA_HOURS[$ticket->priority];
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}.</td>
                        <td>{{ $ticket->ticket_code }}</td>
                        <td>{{ $ticket->asset->asset_code }}<br><span style="color:#64748b;">{{ str_replace('Laboratorium Komputer', 'Lab', $ticket->asset->lab->name) }}</span></td>
                        <td>{{ $ticket->component_issue }}</td>
                        <td>{{ $ticket->priority }}</td>
                        <td>{{ $ticket->technician_name ?? '-' }}</td>
                        <td>{{ $ticket->resolved_at->format('d/m/y H:i') }}</td>
                        <td>{{ $jam }} jam</td>
                        <td class="{{ $tepat ? 'sla-ok' : 'sla-late' }}">{{ $tepat ? 'Tepat' : 'Lewat' }}</td>
                        <td>{{ $ticket->resolution_notes ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <strong>Ringkasan:</strong> {{ $tickets->count() }} tiket diselesaikan &bull;
            {{ $tickets->filter(fn ($t) => ceil(($t->resolved_at->getTimestamp() - $t->reported_at->getTimestamp()) / 3600) <= \App\Models\Ticket::SLA_HOURS[$t->priority])->count() }} tepat SLA &bull;
            {{ $tickets->filter(fn ($t) => ceil(($t->resolved_at->getTimestamp() - $t->reported_at->getTimestamp()) / 3600) > \App\Models\Ticket::SLA_HOURS[$t->priority])->count() }} melewati SLA
        </div>
    @endif

    <div class="footer">Dicetak otomatis oleh SIMLAB</div>
</body>
</html>
