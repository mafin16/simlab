<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Lab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = in_array((int) $request->input('per_page'), [10, 30, 50, 100], true)
            ? (int) $request->input('per_page')
            : 10;

        $assets = Asset::with('lab', 'peripherals')
            ->when($request->filled('lab_id'), fn ($q) => $q->where('lab_id', $request->input('lab_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->input('search');
                $q->where(function ($sub) use ($search) {
                    $sub->where('asset_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('cpu_spec', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%");
                });
            })
            ->orderBy('asset_code')
            ->paginate($perPage)
            ->withQueryString()
            ->appends(['per_page' => $perPage]);

        return view('assets.index', [
            'assets' => $assets,
            'labs' => Lab::orderBy('id')->get(),
            'filters' => $request->only(['lab_id', 'status', 'category', 'search']),
        ]);
    }

    public function create(): View
    {
        return view('assets.create', [
            'labs' => Lab::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        Asset::create($data);

        return redirect()->route('assets.index')
            ->with('success', "Aset {$data['asset_code']} berhasil ditambahkan.");
    }

    public function show(Asset $asset): View
    {
        return view('assets.show', [
            'asset' => $asset->load('lab', 'peripherals'),
        ]);
    }

    public function edit(Asset $asset): View
    {
        return view('assets.edit', [
            'asset' => $asset,
            'labs' => Lab::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, Asset $asset): RedirectResponse
    {
        $rules = $this->rules();
        $rules['asset_code'] = 'required|string|max:50|unique:assets,asset_code,'.$asset->id;

        $data = $request->validate($rules);

        $asset->update($data);

        return redirect()->route('assets.show', $asset)
            ->with('success', "Aset {$asset->asset_code} berhasil diperbarui.");
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $code = $asset->asset_code;
        $asset->delete();

        return redirect()->route('assets.index')
            ->with('success', "Aset {$code} berhasil dihapus.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));

        if (empty($ids)) {
            return redirect()->route('assets.index')
                ->with('error', 'Tidak ada aset yang dipilih.');
        }

        $count = Asset::whereIn('id', $ids)->delete();

        return redirect()->route('assets.index')
            ->with('success', "{$count} data aset berhasil dihapus.");
    }

    private function rules(): array
    {
        return [
            'asset_code' => 'required|string|max:50|unique:assets,asset_code',
            'name' => 'required|string|max:100',
            'lab_id' => 'required|exists:labs,id',
            'seat_label' => 'required|string|max:30',
            'category' => 'required|string|in:PC Desktop,Server,Workstation',
            'cpu_spec' => 'required|string|max:100',
            'ram_gb' => 'required|integer|min:1',
            'ram_type' => 'required|string|max:20',
            'storage_primary' => 'required|string|max:100',
            'storage_secondary' => 'nullable|string|max:100',
            'gpu_spec' => 'nullable|string|max:100',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string|max:17',
            'serial_number' => 'nullable|string|max:100',
            'procurement_source' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'warranty_expiry' => 'nullable|date',
            'status' => 'required|string|in:Ready,Degraded,Maintenance,Scrapped',
        ];
    }
}
