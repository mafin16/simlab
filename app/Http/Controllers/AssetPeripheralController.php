<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetPeripheral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetPeripheralController extends Controller
{
    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|string|max:50',
            'brand' => 'required|string|max:50',
            'model_name' => 'nullable|string|max:100',
            'serial_number' => 'nullable|string|max:100',
            'condition' => 'required|string|in:Baik / Normal,Perlu Penggantian,Rusak',
            'location_note' => 'nullable|string|max:100',
        ]);

        $count = $asset->peripherals()->count();

        $asset->peripherals()->create([
            'peripheral_code' => strtoupper($asset->asset_code).'-PER-'.($count + 1),
            ...$data,
        ]);

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Periferal berhasil ditambahkan ke '.$asset->asset_code.'.');
    }

    public function destroy(AssetPeripheral $peripheral): RedirectResponse
    {
        $asset = $peripheral->asset;
        $peripheral->delete();

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Periferal berhasil dihapus.');
    }
}
