<x-app-layout>
    @section('title', 'Ubah Password')

    <div class="max-w-3xl">
        <div class="p-4 sm:p-8 bg-slate-900/70 backdrop-blur border border-white/8 sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>
    </div>
</x-app-layout>