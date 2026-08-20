@props(['active'])

@php
$classes = ($active ?? false)
            ? 'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-blue-400 bg-blue-600/10 border border-blue-500/20 transition-all'
            : 'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 transition-all';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
