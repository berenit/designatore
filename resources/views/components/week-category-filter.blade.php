@props(['route', 'weekStart', 'prevWeek', 'nextWeek', 'categories', 'category' => null])

@php $currentWeek = $weekStart->format('Y-m-d'); @endphp

{{-- Navigazione settimana (mantiene la categoria selezionata) --}}
<div class="flex items-center gap-3 mb-4">
    <a href="{{ route($route, array_filter(['week' => $prevWeek, 'category' => $category])) }}"
       class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Settimana precedente
    </a>

    @unless($weekStart->isSameWeek(now()))
        <a href="{{ route($route, array_filter(['category' => $category])) }}"
           class="text-sm text-indigo-600 hover:text-indigo-800 font-medium px-3 py-1.5">
            Questa settimana
        </a>
    @endunless

    <a href="{{ route($route, array_filter(['week' => $nextWeek, 'category' => $category])) }}"
       class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 shadow-sm transition ml-auto">
        Settimana successiva
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>

{{-- Filtro per categoria (mantiene la settimana selezionata) --}}
@if ($categories->isNotEmpty())
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route($route, ['week' => $currentWeek]) }}"
           class="px-3 py-1 rounded-full text-sm font-medium border {{ $category ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' : 'bg-indigo-600 text-white border-indigo-600' }}">
            Tutte
        </a>
        @foreach ($categories as $cat)
            <a href="{{ route($route, ['week' => $currentWeek, 'category' => $cat]) }}"
               class="px-3 py-1 rounded-full text-sm font-medium border {{ $category === $cat ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                {{ $cat }}
            </a>
        @endforeach
    </div>
@endif
