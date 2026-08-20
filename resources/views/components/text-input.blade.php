@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-700 bg-slate-800 text-slate-200 placeholder-slate-500 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm']) }}>