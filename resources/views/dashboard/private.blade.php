@extends('layouts.app')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-500 mt-1 text-sm">Benvenuto, {{ auth()->user()->name }}</p>
</div>

{{-- Stat cards --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @php
        $cards = [
            [
                'label' => 'Arbitri disponibili questo fine settimana',
                'value' => $stats['available_referees'] . ' / ' . $stats['referees'],
                'icon' => '👤',
                'color' => 'indigo',
                // Chi è indisponibile solo sabato o solo domenica è contato, ma va segnalato
                'note' => $partiallyAvailableReferees
                    ? '⚠️ di cui ' . count($partiallyAvailableReferees) . (count($partiallyAvailableReferees) === 1 ? ' disponibile' : ' disponibili') . ' solo sabato o solo domenica'
                    : null,
                'note_title' => collect($partiallyAvailableReferees)
                    ->map(fn ($r) => $r['name'] . ': indisponibile ' . $r['days'])
                    ->implode("\n"),
            ],
            ['label' => 'Squadre',              'value' => $stats['teams'],              'icon' => '🛡️', 'color' => 'blue'],
            ['label' => 'Partite in programma questa settimana', 'value' => $stats['upcoming_matches'],   'icon' => '📅', 'color' => 'purple'],
            ['label' => 'Designazioni in attesa questa settimana', 'value' => $stats['pending_designations'], 'icon' => '⏳', 'color' => 'yellow'],
            ['label' => 'Designazioni accettate questa settimana', 'value' => $stats['confirmed_designations'], 'icon' => '✅', 'color' => 'green'],
        ];
    @endphp
    @foreach ($cards as $card)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex items-center gap-4">
            <div class="text-3xl">{{ $card['icon'] }}</div>
            <div>
                <p class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</p>
                <p class="text-sm text-gray-500">{{ $card['label'] }}</p>
                @if (! empty($card['note']))
                    <p class="text-xs text-amber-700 mt-0.5 cursor-help" title="{{ $card['note_title'] }}">{{ $card['note'] }}</p>
                @endif
            </div>
        </div>
    @endforeach

    @php $missing = $stats['matches_without_designation']; @endphp
    <a href="{{ route('designations.index') }}"
       class="rounded-xl shadow-sm p-5 flex items-center gap-4 transition
           {{ $missing > 0 ? 'bg-red-50 border border-red-200 hover:bg-red-100' : 'bg-white border border-gray-200 hover:bg-gray-50' }}">
        <div class="text-3xl">{{ $missing > 0 ? '🚨' : '✅' }}</div>
        <div>
            <p class="text-2xl font-bold {{ $missing > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $missing }}</p>
            <p class="text-sm {{ $missing > 0 ? 'text-red-600' : 'text-gray-500' }}">Partite della settimana senza designazione</p>
        </div>
    </a>

    @if ($hasMatchesToDesignate)
        <a href="{{ route('designations.create') }}"
           class="bg-indigo-600 rounded-xl shadow-sm p-5 flex items-center gap-4 hover:bg-indigo-700 transition group">
            <div class="text-3xl">➕</div>
            <div>
                <p class="text-base font-bold text-white">Nuova designazione</p>
                <p class="text-sm text-indigo-200">Assegna un arbitro</p>
            </div>
        </a>
    @else
        <span title="Tutte le partite sono già completamente designate"
              class="bg-gray-200 rounded-xl shadow-sm p-5 flex items-center gap-4 cursor-not-allowed">
            <div class="text-3xl opacity-50">➕</div>
            <div>
                <p class="text-base font-bold text-gray-500">Nuova designazione</p>
                <p class="text-sm text-gray-400">Nessuna partita da designare</p>
            </div>
        </span>
    @endif
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- Prossime partite --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Prossime partite</h2>
            <a href="{{ route('rugby-matches.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">Vedi tutte →</a>
        </div>
        @if ($upcomingMatches->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Nessuna partita programmata</div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($upcomingMatches as $match)
                    <li class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ $match->label }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ \Carbon\Carbon::parse($match->date_time)->format('d/m/Y H:i') }} — {{ $match->venue_label }}@if ($match->category_label) · {{ $match->category_label }}@endif
                            </p>
                        </div>
                        @if ($match->designations->isNotEmpty())
                            <span class="flex-shrink-0 text-xs font-medium text-green-700 text-right">
                                {{ $match->designations->count() }} {{ $match->designations->count() === 1 ? 'arbitro' : 'arbitri' }}
                            </span>
                        @else
                            <a href="{{ route('designations.create', ['match_id' => $match->id]) }}"
                               class="flex-shrink-0 text-xs text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap">
                                Designa →
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Ultime designazioni --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Ultime designazioni della settimana</h2>
            <a href="{{ route('designations.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">Vedi tutte →</a>
        </div>
        @if ($recentDesignations->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Nessuna designazione per le partite di questa settimana</div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($recentDesignations as $d)
                    <li class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $d->referee->name }} <span class="text-xs font-normal text-gray-400">· {{ $d->role }}</span></p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ $d->match->label }}@if ($d->match->category_label) · {{ $d->match->category_label }}@endif
                            </p>
                        </div>
                        <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                            @if ($d->status === 'pending')   bg-yellow-100 text-yellow-800
                            @elseif ($d->status === 'confirmed') bg-green-100 text-green-800
                            @elseif ($d->status === 'completed') bg-blue-100 text-blue-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ $d->status_label }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Designazioni per arbitro nella stagione --}}
    @php
        $allRoles = \App\Models\Designation::ROLES;
        $rolesSummary = count($seasonRoles) === count($allRoles) ? 'tutti i ruoli' : implode(', ', $seasonRoles);
    @endphp
    <div id="stagione" class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden lg:col-span-2">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Designazioni per arbitro — Stagione {{ $seasonLabel }} <span class="text-xs font-normal text-gray-400">({{ $rolesSummary }})</span></h2>
            <a href="{{ route('referees.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">Vedi arbitri →</a>
        </div>

        {{-- Ruoli da conteggiare: la selezione ricarica la pagina (nessun ruolo = solo Arbitro) --}}
        <form method="GET" action="{{ route('dashboard') }}#stagione" class="px-5 py-3 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ruoli</span>
                <span class="text-xs space-x-2">
                    <a href="{{ route('dashboard', ['season_roles' => $allRoles]) }}#stagione" class="text-indigo-600 hover:text-indigo-800">Tutti</a>
                    <a href="{{ route('dashboard') }}#stagione" class="text-gray-500 hover:text-gray-700">Solo Arbitro</a>
                </span>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                @foreach ($allRoles as $role)
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="season_roles[]" value="{{ $role }}" onchange="this.form.submit()"
                               @checked(in_array($role, $seasonRoles, true))
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        {{ $role }}
                    </label>
                @endforeach
            </div>
        </form>
        @if ($refereeSeasonCounts->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Nessun arbitro registrato</div>
        @else
            <ul class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                @foreach ($refereeSeasonCounts as $entry)
                    <li class="px-5 py-2.5 flex items-center justify-between gap-3 hover:bg-gray-50 transition">
                        <a href="{{ route('referees.show', $entry['referee']) }}" class="text-sm text-gray-900 hover:text-indigo-700">
                            {{ $entry['referee']->name }}
                        </a>
                        <span class="flex-shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                            {{ $entry['count'] > 0 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-400' }}">
                            {{ $entry['count'] }} {{ $entry['count'] === 1 ? 'partita' : 'partite' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
