<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Presence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PresenceController extends Controller
{
    public function show(string $assetCode): View
    {
        $asset = Asset::with('lab')->where('asset_code', $assetCode)->firstOrFail();

        return view('presences.checkin', [
            'asset' => $asset,
            'blocked' => in_array($asset->status, ['Maintenance', 'Scrapped']),
        ]);
    }

    public function store(Request $request, string $assetCode): RedirectResponse
    {
        $data = $request->validate([
            'user_identifier' => 'required|string|min:3|max:100',
            'user_fullname' => 'required|string|min:3|max:100',
        ]);

        $asset = Asset::with('lab')->where('asset_code', $assetCode)->firstOrFail();

        if (in_array($asset->status, ['Maintenance', 'Scrapped'])) {
            return back()->with('error', "PC {$asset->asset_code} sedang {$asset->status} dan tidak bisa digunakan untuk presensi.");
        }

        $result = DB::transaction(function () use ($asset, $data) {
            $occupant = Presence::whereNull('check_out_time')
                ->whereDate('session_date', today())
                ->where('asset_id', $asset->id)
                ->first();

            if ($occupant && $occupant->user_identifier !== $data['user_identifier']) {
                return ['error' => "Kursi ini sedang dipakai oleh {$occupant->user_fullname} sejak {$occupant->check_in_time->format('H:i')}."];
            }

            if ($occupant) {
                return ['already' => $occupant];
            }

            // Satu user satu kursi: NISN/NIM yang masih aktif di PC lain otomatis di-checkout.
            $previous = Presence::whereNull('check_out_time')
                ->whereDate('session_date', today())
                ->where('user_identifier', $data['user_identifier'])
                ->get()
                ->each(fn (Presence $p) => $p->update(['check_out_time' => now()]));

            $presence = Presence::create($data + [
                'asset_id' => $asset->id,
                'session_date' => today(),
                'check_in_time' => now(),
            ]);

            return ['presence' => $presence, 'moved' => $previous->isNotEmpty()];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        if (isset($result['already'])) {
            return redirect()
                ->route('checkin.show', $asset->asset_code)
                ->with('presence_success', [
                    'fullname' => $result['already']->user_fullname,
                    'identifier' => $result['already']->user_identifier,
                    'time' => $result['already']->check_in_time->format('H:i'),
                    'moved' => false,
                ]);
        }

        return redirect()
            ->route('checkin.show', $asset->asset_code)
            ->with('presence_success', [
                'fullname' => $result['presence']->user_fullname,
                'identifier' => $result['presence']->user_identifier,
                'time' => $result['presence']->check_in_time->format('H:i'),
                'moved' => $result['moved'],
            ]);
    }

    public function checkout(Request $request, string $assetCode): RedirectResponse
    {
        $data = $request->validate([
            'user_identifier' => 'required|string|max:100',
        ]);

        $asset = Asset::where('asset_code', $assetCode)->firstOrFail();

        $presence = Presence::whereNull('check_out_time')
            ->whereDate('session_date', today())
            ->where('asset_id', $asset->id)
            ->where('user_identifier', $data['user_identifier'])
            ->first();

        if (! $presence) {
            return back()->with('error', 'Tidak ditemukan sesi presensi aktif dengan identitas tersebut di PC ini.');
        }

        $presence->update(['check_out_time' => now()]);

        return redirect()
            ->route('checkin.show', $asset->asset_code)
            ->with('checkout_success', [
                'fullname' => $presence->user_fullname,
                'time' => $presence->check_out_time->format('H:i'),
            ]);
    }
}
