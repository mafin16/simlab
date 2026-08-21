<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lab;
use App\Models\Presence;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeatMapController extends Controller
{
    public function index(Request $request): View
    {
        [$labs, $selectedLab] = $this->resolveLab($request);

        if ($selectedLab) {
            $this->closeExpiredPresences($selectedLab);
        }

        return view('seatmap.index', [
            'labs' => $labs,
            'selectedLab' => $selectedLab,
            'seats' => $this->buildSeats($selectedLab),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        [$labs, $selectedLab] = $this->resolveLab($request);

        if ($selectedLab) {
            $this->closeExpiredPresences($selectedLab);
        }

        return response()->json([
            'server_time' => now()->format('H:i:s'),
            'lab_id' => $selectedLab?->id,
            'seats' => $this->buildSeats($selectedLab),
        ]);
    }

    private function resolveLab(Request $request): array
    {
        $labs = Lab::orderBy('id')->get();
        $selectedLab = $labs->firstWhere('id', $request->integer('lab_id')) ?? $labs->first();

        return [$labs, $selectedLab];
    }

    private function buildSeats(?Lab $lab): array
    {
        if (! $lab) {
            return [];
        }

        $assets = $lab->assets()->orderBy('asset_code')->get();

        $activePresences = Presence::whereNull('check_out_time')
            ->whereDate('session_date', today())
            ->whereIn('asset_id', $assets->pluck('id'))
            ->orderBy('check_in_time')
            ->get()
            ->keyBy('asset_id');

        return $assets->map(fn (Asset $asset) => [
            'id' => $asset->id,
            'code' => $asset->asset_code,
            'name' => $asset->name,
            'seat_label' => $asset->seat_label,
            'status' => $asset->status,
            'cpu_spec' => $asset->cpu_spec,
            'ram_gb' => $asset->ram_gb,
            'ip_address' => $asset->ip_address,
            'presence' => ($presence = $activePresences->get($asset->id)) ? [
                'identifier' => $presence->user_identifier,
                'fullname' => $presence->user_fullname,
                'check_in_time' => $presence->check_in_time->format('H:i'),
            ] : null,
        ])->values()->all();
    }

    /**
     * Auto clear session: presensi aktif ditutup ketika sesi praktikumnya
     * (sesuai jadwal lab pada hari itu) sudah berakhir. check_out_time diisi
     * jam akhir sesi agar durasi tercatat akurat.
     */
    private function closeExpiredPresences(Lab $lab): void
    {
        Presence::whereNull('check_out_time')
            ->whereDate('session_date', '<', today())
            ->get()
            ->each(fn (Presence $p) => $p->update(['check_out_time' => $p->session_date->copy()->setTime(23, 59)]));

        $schedules = $lab->schedules()
            ->where('day_name', now()->format('l'))
            ->orderBy('end_time')
            ->get();

        if ($schedules->isEmpty()) {
            return;
        }

        $now = now();

        Presence::whereNull('check_out_time')
            ->whereDate('session_date', today())
            ->whereHas('asset', fn ($q) => $q->where('lab_id', $lab->id))
            ->with('asset')
            ->get()
            ->each(function (Presence $presence) use ($schedules, $now) {
                $endedSession = $schedules->first(
                    fn (Schedule $s) => $presence->check_in_time->lte($s->end_time) && $now->gt($s->end_time)
                );

                if ($endedSession) {
                    $presence->update(['check_out_time' => $endedSession->end_time]);
                }
            });
    }
}
