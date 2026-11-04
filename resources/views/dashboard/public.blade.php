@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Partite della Settimana</h1>
    <p class="text-gray-500 mt-1 text-sm">
        Designazioni arbitrali dal {{ $weekStart->format('d/m/Y') }} al {{ $weekEnd->format('d/m/Y') }}
        @if (! $showRefereeNames)
            – <span class="text-red-600 font-semibold uppercase">Designazioni visibili dal giovedì</span>
        @endif
    </p>

    @if ($isAuthenticated)
        @php
            $weekParams = fn ($date) => array_filter(['week' => $date?->toDateString(), 'category' => $category]);
            $weekLinkClass = 'px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition';
        @endphp
        <nav class="mt-4 flex flex-wrap items-center gap-2" aria-label="Scorri le settimane">
            <a href="{{ route('home', $weekParams($weekStart->copy()->subWeek())) }}" class="{{ $weekLinkClass }}">← Settimana precedente</a>
            @unless ($isCurrentWeek)
                <a href="{{ route('home', $weekParams(null)) }}" class="{{ $weekLinkClass }}">Settimana corrente</a>
            @endunless
            <a href="{{ route('home', $weekParams($weekStart->copy()->addWeek())) }}" class="{{ $weekLinkClass }}">Settimana successiva →</a>
        </nav>
    @endif
</div>

@if ($categories->isNotEmpty())
    @php $selectedWeek = $isCurrentWeek ? [] : ['week' => $weekStart->toDateString()]; @endphp
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('home', $selectedWeek) }}"
           class="px-3 py-1 rounded-full text-sm font-medium border {{ $category ? 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' : 'bg-indigo-600 text-white border-indigo-600' }}">
            Tutte
        </a>
        @foreach ($categories as $cat)
            <a href="{{ route('home', [...$selectedWeek, 'category' => $cat]) }}"
               class="px-3 py-1 rounded-full text-sm font-medium border {{ $category === $cat ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                {{ $cat }}
            </a>
        @endforeach
    </div>
@endif

@if ($upcomingMatches->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
        <div class="text-5xl mb-4">📅</div>
        <h3 class="text-lg font-semibold text-gray-700">Nessuna partita in programma {{ $isCurrentWeek ? 'questa settimana' : 'in questa settimana' }}</h3>
        <p class="text-gray-400 text-sm mt-1">
            @if ($category)
                Nessuna partita in programma per la categoria «{{ $category }}».
            @else
                Le partite della settimana appariranno qui.
            @endif
        </p>
    </div>
@else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($upcomingMatches as $match)
            @php $designations = $match->designations; @endphp
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">
                            {{ $match->competition_type }}
                            @if ($match->category_label)
                                · {{ $match->category_label }}
                            @endif
                        </p>
                        <h3 class="font-bold text-gray-900 text-base leading-tight">
                            {{ $match->label }}
                        </h3>
                    </div>
                    @if ($designations->isNotEmpty())
                        <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            {{ $designations->count() }} {{ $designations->count() === 1 ? 'arbitro' : 'arbitri' }}
                        </span>
                    @else
                        <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                            Da designare
                        </span>
                    @endif
                </div>

                <div class="space-y-1 text-sm text-gray-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        {{ \Carbon\Carbon::parse($match->date_time)->format('d/m/Y H:i') }}
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        {{ $match->venue_label }}
                    </div>
                    {{-- Prima del giovedì le righe arbitro non si mostrano: l'avviso è nell'intestazione --}}
                    @foreach ($showRefereeNames ? $designations : [] as $designation)
                        <div class="flex items-center gap-2 pt-1">
                            <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            @if ($designation->status === 'confirmed' || $isAuthenticated)
                                <span class="text-indigo-700 font-medium">{{ $designation->referee->name }}</span>
                            @else
                                <span class="text-gray-400 italic">In attesa di conferma</span>
                            @endif
                            <span class="text-xs text-gray-400">
                                · {{ $designation->role }}
                                @if ($isAuthenticated)
                                    · {{ $designation->status_label }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                    @if ($match->has_evaluator)
                        <p class="pt-1 text-amber-700 font-medium">Valutatore della prestazione designato</p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@guest
    <div class="mt-12 text-center">
        <p class="text-sm text-gray-400">
            Sei un arbitro o un designatore?
            <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Accedi</a>
            alla tua area riservata.
        </p>
    </div>
@endguest
@endsection
