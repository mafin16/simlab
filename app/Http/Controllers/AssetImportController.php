<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetImportController extends Controller
{
    private const COLUMNS = [
        'asset_code', 'name', 'lab_code', 'seat_label', 'category',
        'cpu_spec', 'ram_gb', 'ram_type', 'storage_primary', 'storage_secondary',
        'gpu_spec', 'ip_address', 'mac_address', 'serial_number',
        'procurement_source', 'purchase_date', 'warranty_expiry', 'status',
    ];

    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import');

        $headers = [
            'Kode Aset', 'Nama Perangkat', 'Kode Lab', 'Baris / Meja', 'Kategori',
            'Processor', 'RAM (GB)', 'Tipe RAM', 'Storage Utama', 'Storage Sekunder',
            'GPU / VGA', 'IP Address', 'MAC Address', 'Serial Number',
            'Sumber Pengadaan', 'Tanggal Pembelian', 'Masa Garansi', 'Status',
        ];

        $sheet->fromArray($headers, null, 'A1');

        $sheet->fromArray([
            'LAB1-PC-21', 'PC Client 21', 'LAB-1', 'Meja A-21', 'PC Desktop',
            'Intel i5-12400', 16, 'DDR4', '512GB NVMe SSD', 'HDD 500GB',
            'Intel UHD Graphics 730', '192.168.10.31', '00:1A:2B:3C:01:21', 'SN-LAB1-PC-21',
            'Dana BOS', '2024-08-01', '2027-08-01', 'Ready',
        ], null, 'A2');

        $sheet->getStyle('A1:R1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:R1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2563EB');
        $sheet->getStyle('A1:R1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A2:R2')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE8F0FE');

        foreach (range('A', 'R') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'template_import_aset.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        } catch (\Throwable $e) {
            return back()->with('error', 'File tidak bisa dibaca: '.$e->getMessage());
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            return back()->with('error', 'File Excel kosong.');
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), array_shift($rows));

        $created = 0;
        $errors = [];

        foreach ($rows as $row) {
            if (empty(array_filter($row))) {
                continue;
            }

            $row = array_combine($headers, array_pad($row, count($headers), ''));

            $result = $this->createAssetFromRow($row);
            if ($result['success']) {
                $created++;
            } else {
                $errors[] = ($row['asset_code'] ?? '?').': '.$result['message'];
            }
        }

        if ($created > 0) {
            session()->flash('success', "Berhasil mengimpor {$created} data aset.");
        }

        if (! empty($errors)) {
            session()->flash('import_errors', $errors);
        }

        return back();
    }

    private function createAssetFromRow(array $row): array
    {
        $assetCode = trim((string) ($row['asset_code'] ?? ''));
        if ($assetCode === '') {
            return ['success' => false, 'message' => 'Kode aset kosong'];
        }

        if (Asset::where('asset_code', $assetCode)->exists()) {
            return ['success' => false, 'message' => 'Kode aset sudah ada'];
        }

        $lab = Lab::where('lab_code', trim((string) ($row['lab_code'] ?? '')))->first();
        if (! $lab) {
            return ['success' => false, 'message' => 'Lab tidak ditemukan'];
        }

        $category = trim((string) ($row['category'] ?? 'PC Desktop'));
        if (! in_array($category, ['PC Desktop', 'Server', 'Workstation'], true)) {
            $category = 'PC Desktop';
        }

        $status = trim((string) ($row['status'] ?? 'Ready'));
        if (! in_array($status, ['Ready', 'Degraded', 'Maintenance', 'Scrapped'], true)) {
            $status = 'Ready';
        }

        Asset::create([
            'asset_code' => $assetCode,
            'name' => trim((string) ($row['name'] ?? $assetCode)),
            'lab_id' => $lab->id,
            'seat_label' => trim((string) ($row['seat_label'] ?? '')),
            'category' => $category,
            'cpu_spec' => trim((string) ($row['cpu_spec'] ?? '')),
            'ram_gb' => (int) ($row['ram_gb'] ?? 0),
            'ram_type' => trim((string) ($row['ram_type'] ?? 'DDR4')),
            'storage_primary' => trim((string) ($row['storage_primary'] ?? '')),
            'storage_secondary' => $this->nullable($row['storage_secondary'] ?? null),
            'gpu_spec' => $this->nullable($row['gpu_spec'] ?? null),
            'ip_address' => $this->nullable($row['ip_address'] ?? null),
            'mac_address' => $this->nullable($row['mac_address'] ?? null),
            'serial_number' => $this->nullable($row['serial_number'] ?? null),
            'procurement_source' => $this->nullable($row['procurement_source'] ?? null),
            'purchase_date' => $this->nullable($row['purchase_date'] ?? null),
            'warranty_expiry' => $this->nullable($row['warranty_expiry'] ?? null),
            'status' => $status,
        ]);

        return ['success' => true];
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
