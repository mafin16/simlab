<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lab;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    private const SEVERITY = ['Ready' => 0, 'Degraded' => 1, 'Maintenance' => 2];

    public function index(Request $request): View
    {
        $labs = Lab::orderBy('id')->get();
        $selectedLab = $labs->firstWhere('id', $request->integer('lab_id'));

        $ticketsQuery = Ticket::with('asset.lab')->orderBy('reported_at');
        if ($selectedLab) {
            $ticketsQuery->whereHas('asset', fn ($q) => $q->where('lab_id', $selectedLab->id));
        }
        $tickets = $ticketsQuery->get();

        $resolvedAll = $tickets->where('status', 'Resolved')->sortByDesc('resolved_at')->values();

        $assetsQuery = Asset::with('lab')
            ->where('category', 'PC Desktop')
            ->orderBy('asset_code');
        if ($selectedLab) {
            $assetsQuery->where('lab_id', $selectedLab->id);
        }

        return view('tickets.index', [
            'labs' => $labs,
            'selectedLab' => $selectedLab,
            'openTickets' => $tickets->where('status', 'Open'),
            'progressTickets' => $tickets->where('status', 'In Progress'),
            'resolvedTickets' => $resolvedAll->take(5),
            'resolvedTotal' => $resolvedAll->count(),
            'assets' => $assetsQuery->get(),
            'canManage' => in_array($request->user()->role, ['super_admin', 'teknisi']),
            'canCreate' => in_array($request->user()->role, ['super_admin', 'teknisi', 'instruktur']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'component_issue' => 'required|in:'.implode(',', Ticket::COMPONENTS),
            'priority' => 'required|in:'.implode(',', Ticket::PRIORITIES),
            'description' => 'required|string|min:5|max:1000',
            'reporter_name' => 'required|string|max:100',
        ]);

        $ticket = DB::transaction(function () use ($data) {
            $ticket = Ticket::create($data + [
                'ticket_code' => Ticket::nextCode(),
                'status' => 'Open',
                'reported_at' => now(),
            ]);

            $this->escalateAsset($ticket->asset, $data['component_issue']);

            return $ticket;
        });

        return back()
            ->with('success', "Tiket {$ticket->ticket_code} untuk {$ticket->asset->asset_code} berhasil dibuat (SLA {$ticket->priority}: ".Ticket::SLA_HOURS[$ticket->priority].' jam).');
    }

    public function start(Request $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->status !== 'Open') {
            return back()->with('error', "Tiket {$ticket->ticket_code} sudah diproses sebelumnya.");
        }

        $ticket->update([
            'status' => 'In Progress',
            'technician_name' => $request->user()->name,
        ]);

        return back()
            ->with('success', "Tiket {$ticket->ticket_code} dipindahkan ke In Progress. Penanggung jawab: ".$request->user()->name.'.');
    }

    public function resolve(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'resolution_notes' => 'required|string|min:5|max:1000',
        ]);

        if ($ticket->status !== 'In Progress') {
            return back()->with('error', "Tiket {$ticket->ticket_code} belum dalam proses perbaikan.");
        }

        DB::transaction(function () use ($ticket, $data) {
            $ticket->update($data + [
                'status' => 'Resolved',
                'resolved_at' => now(),
            ]);

            $this->syncAssetStatus($ticket->asset);
        });

        return back()
            ->with('success', "Tiket {$ticket->ticket_code} diselesaikan. Status {$ticket->asset->asset_code} kini {$ticket->asset->status}.");
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $asset = $ticket->asset;
        $label = $ticket->ticket_code.' ('.$asset->asset_code.')';
        $ticket->delete();
        $this->syncAssetStatus($asset);

        return back()->with('success', "Tiket {$label} berhasil dihapus.");
    }

    public function history(Request $request): View
    {
        $labs = Lab::orderBy('id')->get();

        $data = $request->validate([
            'lab_id' => 'nullable|integer|exists:labs,id',
            'status' => 'nullable|in:'.implode(',', Ticket::STATUSES),
            'priority' => 'nullable|in:'.implode(',', Ticket::PRIORITIES),
            'search' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $tickets = $this->historyQuery($data)
            ->orderByDesc('reported_at')
            ->paginate(15)
            ->withQueryString();

        return view('tickets.history', [
            'tickets' => $tickets,
            'labs' => $labs,
            'filters' => collect($data),
        ]);
    }

    public function historyExcel(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'lab_id' => 'nullable|integer|exists:labs,id',
            'status' => 'nullable|in:'.implode(',', Ticket::STATUSES),
            'priority' => 'nullable|in:'.implode(',', Ticket::PRIORITIES),
            'search' => 'nullable|string|max:100',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $tickets = $this->historyQuery($data)->orderByDesc('reported_at')->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Riwayat Tiket');

        $sheet->fromArray([
            'Kode Tiket', 'Kode Aset', 'Lab', 'Komponen', 'Prioritas', 'Status',
            'Pelapor', 'Teknisi', 'Dilaporkan', 'Diselesaikan', 'Durasi (jam)', 'Solusi',
        ], null, 'A1');

        $rowIndex = 2;
        foreach ($tickets as $ticket) {
            $duration = $ticket->resolved_at
                ? (int) ceil(($ticket->resolved_at->getTimestamp() - $ticket->reported_at->getTimestamp()) / 3600)
                : null;

            $sheet->fromArray([
                $ticket->ticket_code,
                $ticket->asset->asset_code,
                $ticket->asset->lab->name,
                $ticket->component_issue,
                $ticket->priority,
                $ticket->status,
                $ticket->reporter_name,
                $ticket->technician_name ?? '-',
                $ticket->reported_at->format('d/m/Y H:i'),
                $ticket->resolved_at?->format('d/m/Y H:i') ?? '-',
                $duration ?? '-',
                $ticket->resolution_notes ?? '-',
            ], null, 'A'.$rowIndex);
            $rowIndex++;
        }

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'riwayat-tiket.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, must-revalidate',
        ]);
    }

    private function historyQuery(array $data)
    {
        return Ticket::with('asset.lab')
            ->when($data['lab_id'] ?? null, fn ($q, $v) => $q->whereHas('asset', fn ($q2) => $q2->where('lab_id', (int) $v)))
            ->when($data['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($data['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($data['search'] ?? null, fn ($q, $v) => $q->where(fn ($q2) => $q2
                ->where('ticket_code', 'like', "%{$v}%")
                ->orWhere('reporter_name', 'like', "%{$v}%")))
            ->when($data['date_from'] ?? null, fn ($q, $v) => $q->whereDate('reported_at', '>=', $v))
            ->when($data['date_to'] ?? null, fn ($q, $v) => $q->whereDate('reported_at', '<=', $v));
    }

    private function escalateAsset(Asset $asset, string $component): void
    {
        $target = Ticket::COMPONENT_ASSET_STATUS[$component];

        if ($asset->status === 'Scrapped') {
            return;
        }

        if (self::SEVERITY[$asset->status] < self::SEVERITY[$target]) {
            $asset->update(['status' => $target]);
        }
    }

    private function syncAssetStatus(Asset $asset): void
    {
        if ($asset->status === 'Scrapped') {
            return;
        }

        $target = $asset->tickets()->where('status', '!=', 'Resolved')
            ->get()
            ->map(fn (Ticket $t) => Ticket::COMPONENT_ASSET_STATUS[$t->component_issue])
            ->sortByDesc(fn (string $s) => self::SEVERITY[$s])
            ->first();

        $newStatus = $target ?? 'Ready';

        if ($newStatus !== $asset->status) {
            $asset->update(['status' => $newStatus]);
        }
    }
}
