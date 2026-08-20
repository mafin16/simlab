@props([
    'name' => '',
    'options' => [],
    'selected' => '',
    'placeholder' => 'Pilih...',
    'submit' => false,
    'direction' => 'down',
])

@php
    $map = [];
    foreach ($options as $value => $label) {
        if (is_array($label)) {
            $map[(string) $label['value']] = $label['label'];
        } else {
            $map[(string) $value] = $label;
        }
    }
    $extraClass = $attributes->get('class');
    $jsonMap = json_encode($map, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $jsonSelected = json_encode((string) $selected, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    $up = $direction === 'up';
    $listPos = $up ? 'bottom-full mb-1' : 'mt-1';
    $enterStart = $up ? 'opacity-0 translate-y-1' : 'opacity-0 -translate-y-1';
    $leaveEnd = $up ? 'opacity-0 translate-y-1' : 'opacity-0 -translate-y-1';
@endphp

<div class="relative inline-block" x-data='{ open: false, value: {{ $jsonSelected }}, map: {{ $jsonMap }} }'>
    <button type="button"
            @click="open = !open"
            @click.outside="open = false"
            @keydown.escape="open = false"
            class="flex items-center justify-between gap-2 bg-slate-900 text-xs text-slate-200 pl-3 pr-2.5 py-2 rounded-lg border border-slate-700 hover:border-blue-500/50 transition-colors {{ $extraClass }}">
        <span class="truncate" x-text="map[value] ?? @js($placeholder)"></span>
        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''"></i>
    </button>

    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="{{ $enterStart }}"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="{{ $leaveEnd }}"
         class="absolute left-0 z-50 {{ $listPos }} min-w-[180px] bg-slate-800 border border-slate-700 rounded-lg shadow-xl shadow-black/30 overflow-hidden">
        <div class="max-h-56 overflow-y-auto custom-scrollbar">
            @foreach ($map as $val => $label)
                <button type="button"
                        @click="value = '{{ $val }}'; open = false; $el.closest('form').querySelector('[name={{ $name }}]').value = '{{ $val }}'; @if($submit) $el.closest('form').submit(); @endif"
                        class="w-full text-left px-3 py-2 text-xs transition-colors"
                        :class="value === '{{ $val }}' ? 'bg-blue-500/10 text-blue-400 font-semibold' : 'text-slate-300 hover:bg-slate-700'">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" value="{{ $selected }}">
</div>