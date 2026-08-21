<?php

namespace App\Http\Controllers;

use App\Models\Lab;
use App\Models\Presence;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth()->toDateTimeString();

        $resolvedThisMonth = Ticket::where('status', 'Resolved')
            ->where('resolved_at', '>=', $monthStart)
            ->count();

        $presenceMinutes = Presence::whereDate('session_date', '>=', $monthStart)
            ->whereNotNull('check_out_time')
            ->get()
            ->sum(fn (Presence $p) => $p->check_in_time->diffInMinutes($p->check_out_time));

        return view('reports.index', [
            'labs' => Lab::orderBy('id')->get(),
            'activeTickets' => Ticket::whereIn('status', ['Open', 'In Progress'])->count(),
            'resolvedThisMonth' => $resolvedThisMonth,
            'sessionsThisMonth' => Presence::whereDate('session_date', '>=', $monthStart)->count(),
            'presenceHoursThisMonth' => (int) round($presenceMinutes / 60),
        ]);
    }

    public function maintenancePdf(Request $request): Response
    {
        [$from, $to, $labId] = $this->resolveRange($request);

        $tickets = Ticket::with('asset.lab')
            ->where('status', 'Resolved')
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$from, $to])
            ->when($labId, fn ($q) => $q->whereHas('asset', fn ($q2) => $q2->where('lab_id', $labId)))
            ->orderBy('resolved_at')
            ->get();

        $pdf = Pdf::loadView('reports.pdf.maintenance', [
            'tickets' => $tickets,
            'from' => $from,
            'to' => $to,
            'lab' => $labId ? Lab::find($labId) : null,
        ])->setPaper('a4', 'portrait');

        $response = $pdf->download('laporan-pemeliharaan-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf');
        $response->headers->set('Cache-Control', 'no-store, must-revalidate');

        return $response;
    }

    public function presencePdf(Request $request): Response
    {
        [$from, $to, $labId] = $this->resolveRange($request);

        $presences = $this->presenceQuery($from, $to, $labId)->get();

        $pdf = Pdf::loadView('reports.pdf.presence', [
            'presences' => $presences,
            'from' => $from,
            'to' => $to,
            'lab' => $labId ? Lab::find($labId) : null,
        ])->setPaper('a4', 'portrait');

        $response = $pdf->download('log-presensi-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf');
        $response->headers->set('Cache-Control', 'no-store, must-revalidate');

        return $response;
    }

    public function presenceExcel(Request $request): StreamedResponse
    {
        [$from, $to, $labId] = $this->resolveRange($request);

        $presences = $this->presenceQuery($from, $to, $labId)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Log Presensi');

        $sheet->fromArray([
            'Tanggal', 'Kode PC', 'Lab', 'NISN/NIM', 'Nama Lengkap', 'Jam Masuk', 'Jam Keluar', 'Durasi',
        ], null, 'A1');

        $rowIndex = 2;
        foreach ($presences as $presence) {
            $sheet->fromArray([
                $presence->session_date->format('d/m/Y'),
                $presence->asset->asset_code,
                $presence->asset->lab->name,
                $presence->user_identifier,
                $presence->user_fullname,
                $presence->check_in_time->format('H:i'),
                $presence->check_out_time?->format('H:i') ?? '-',
                $this->durationLabel($presence),
            ], null, 'A'.$rowIndex);
            $rowIndex++;
        }

        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'log-presensi-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, must-revalidate',
        ]);
    }

    private function presenceQuery(Carbon $from, Carbon $to, ?int $labId)
    {
        return Presence::with('asset.lab')
            ->whereBetween('session_date', [$from->toDateString(), $to->toDateString()])
            ->when($labId, fn ($q) => $q->whereHas('asset', fn ($q2) => $q2->where('lab_id', $labId)))
            ->orderBy('session_date')
            ->orderBy('check_in_time');
    }

    private function durationLabel(Presence $presence): string
    {
        if (! $presence->check_out_time) {
            return 'Berlangsung';
        }

        $minutes = (int) $presence->check_in_time->diffInMinutes($presence->check_out_time);

        return sprintf('%d jam %02d menit', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: int|null}
     */
    private function resolveRange(Request $request): array
    {
        $data = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'lab_id' => 'nullable|integer|exists:labs,id',
        ]);

        $from = isset($data['date_from'])
            ? Carbon::parse($data['date_from'])->startOfDay()
            : now()->startOfMonth();
        $to = isset($data['date_to'])
            ? Carbon::parse($data['date_to'])->endOfDay()
            : now()->endOfMonth();

        return [$from, $to, isset($data['lab_id']) ? (int) $data['lab_id'] : null];
    }
}
