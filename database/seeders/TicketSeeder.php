<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'asset_code' => 'LAB1-PC-03',
                'component_issue' => 'PC Tidak Bisa Booting / OS Error',
                'priority' => 'High',
                'description' => 'PC menyala tapi stuck di logo Windows, sudah dicoba hard restart 3x.',
                'reporter_name' => 'Pak Hendra, S.Kom',
                'reported_hours_ago' => 5,
            ],
            [
                'asset_code' => 'LAB2-PC-07',
                'component_issue' => 'Koneksi Jaringan LAN Putus',
                'priority' => 'Medium',
                'description' => 'Lampu LAN mati, kabel port sudah diganti tetap tidak dapat IP.',
                'reporter_name' => 'Bu Sari',
                'reported_hours_ago' => 4,
            ],
            [
                'asset_code' => 'LAB1-PC-11',
                'component_issue' => 'Mouse / Keyboard',
                'priority' => 'Low',
                'description' => 'Klik kiri mouse kadang double, kabel kendor.',
                'reporter_name' => 'Pak Dedi',
                'reported_hours_ago' => 2,
            ],
            [
                'asset_code' => 'LAB2-PC-02',
                'component_issue' => 'Monitor Blank / Flashing',
                'priority' => 'High',
                'description' => 'Layar berkedip lalu blank total, kabel HDMI sudah ditukar.',
                'reporter_name' => 'Bu Rina',
                'technician_name' => 'Teknisi Lab',
                'reported_hours_ago' => 1,
                'status' => 'In Progress',
            ],
            [
                'asset_code' => 'LAB1-PC-15',
                'component_issue' => 'Audio / Headset Mati',
                'priority' => 'Medium',
                'description' => 'Audio jack depan tidak keluar suara.',
                'reporter_name' => 'Pak Agus',
                'technician_name' => 'Teknisi Lab',
                'status' => 'Resolved',
                'resolution_notes' => 'Driver audio diinstall ulang, jack belakang dipakai sementara. Suara normal.',
                'reported_hours_ago' => 30,
                'resolved_hours_after' => 6,
            ],
            [
                'asset_code' => 'LAB2-PC-12',
                'component_issue' => 'Mouse / Keyboard',
                'priority' => 'Low',
                'description' => 'Keyboard tombol spasi nyangkut.',
                'reporter_name' => 'Kakak Mentoring',
                'technician_name' => 'Teknisi Lab',
                'status' => 'Resolved',
                'resolution_notes' => 'Keyboard diganti unit cadangan gudang.',
                'reported_hours_ago' => 60,
                'resolved_hours_after' => 20,
            ],
        ];

        foreach ($rows as $row) {
            $asset = Asset::where('asset_code', $row['asset_code'])->first();

            if (! $asset) {
                continue;
            }

            $reportedAt = now()->subHours($row['reported_hours_ago']);

            Ticket::create([
                'ticket_code' => Ticket::nextCode(),
                'asset_id' => $asset->id,
                'component_issue' => $row['component_issue'],
                'description' => $row['description'],
                'priority' => $row['priority'],
                'status' => $row['status'] ?? 'Open',
                'reporter_name' => $row['reporter_name'],
                'technician_name' => $row['technician_name'] ?? null,
                'resolution_notes' => $row['resolution_notes'] ?? null,
                'reported_at' => $reportedAt,
                'resolved_at' => isset($row['resolved_hours_after'])
                    ? $reportedAt->copy()->addHours($row['resolved_hours_after'])
                    : null,
            ]);

            if (($row['status'] ?? 'Open') !== 'Resolved') {
                $target = Ticket::COMPONENT_ASSET_STATUS[$row['component_issue']] ?? null;

                if ($target && $asset->status === 'Ready') {
                    $asset->update(['status' => $target]);
                }
            }
        }
    }
}
