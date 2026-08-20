<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $assets = Asset::with('lab')
            ->when($request->filled('lab_id'), fn ($q) => $q->where('lab_id', $request->input('lab_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->orderBy('asset_code')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventaris Aset');

        $headers = [
            'Kode Aset', 'Nama Perangkat', 'Kode Lab', 'Baris / Meja', 'Kategori',
            'Processor', 'RAM (GB)', 'Tipe RAM', 'Storage Utama', 'Storage Sekunder',
            'GPU / VGA', 'IP Address', 'MAC Address', 'Serial Number',
            'Sumber Pengadaan', 'Tanggal Pembelian', 'Masa Garansi', 'Status',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $rowIndex = 2;
        foreach ($assets as $asset) {
            $sheet->fromArray([
                $asset->asset_code,
                $asset->name,
                $asset->lab?->lab_code,
                $asset->seat_label,
                $asset->category,
                $asset->cpu_spec,
                $asset->ram_gb,
                $asset->ram_type,
                $asset->storage_primary,
                $asset->storage_secondary,
                $asset->gpu_spec,
                $asset->ip_address,
                $asset->mac_address,
                $asset->serial_number,
                $asset->procurement_source,
                $asset->purchase_date?->format('Y-m-d'),
                $asset->warranty_expiry?->format('Y-m-d'),
                $asset->status,
            ], null, 'A'.$rowIndex);

            $rowIndex++;
        }

        $this->styleSheet($sheet, $rowIndex - 1);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'rekap_inventaris_aset_'.date('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Apply basic styling: bold header with fill + auto column widths.
     */
    private function styleSheet(Worksheet $sheet, int $lastRow): void
    {
        $sheet->getStyle('A1:R1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:R1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2563EB');
        $sheet->getStyle('A1:R1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if ($lastRow >= 1) {
            $sheet->getStyle('A1:R'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }
    }
}
