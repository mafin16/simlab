<x-app-layout>
    @section('title', 'Edit Aset')
    @section('subtitle', 'Perbarui data unit aset: '.$asset->asset_code)

    <div>
        <form method="POST" action="{{ route('assets.update', $asset) }}" class="glass-panel rounded-xl p-5 space-y-5">
            @csrf
            @method('PUT')

            @include('assets._form')

            <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                <a href="{{ route('assets.show', $asset) }}" class="text-xs text-slate-400 hover:text-slate-200 flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-left"></i> Kembali ke detail
                </a>
                <div class="flex gap-2">
                    <button type="button" onclick="window.history.back()" class="px-4 py-2 bg-slate-800 text-slate-300 font-semibold rounded-lg hover:bg-slate-700 text-xs">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-500 shadow-lg shadow-blue-600/20 text-xs">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Perbarui Aset
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>