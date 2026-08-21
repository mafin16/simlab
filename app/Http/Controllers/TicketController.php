<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lab;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
            'resolvedTickets' => $resolvedAll->take(10),
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

            if ($ticket->asset->status !== 'Scrapped') {
                $ticket->asset->update(['status' => 'Ready']);
            }
        });

        return back()
            ->with('success', "Tiket {$ticket->ticket_code} diselesaikan. Status {$ticket->asset->asset_code} kembali Ready.");
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $label = $ticket->ticket_code.' ('.$ticket->asset->asset_code.')';
        $ticket->delete();

        return back()->with('success', "Tiket {$label} berhasil dihapus.");
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
}
