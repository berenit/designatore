@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Squadre</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $teams->count() }} {{ $teams->count() === 1 ? 'squadra' : 'squadre' }}</p>
    </div>
    <a href="{{ route('teams.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuova Squadra
    </a>
</div>

{{-- Filtro campionato --}}
<form method="GET" action="{{ route('teams.index') }}" class="flex flex-wrap items-end gap-3 mb-5">
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="dir" value="{{ $dir }}">
    <div>
        <label class="block text-xs font-medium text-gray-500 mb-1">Campionato</label>
        <select name="filter_league" onchange="this.form.submit()"
                class="rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
            <option value="">Tutti i campionati</option>
            @foreach ($leagues as $l)
                <option value="{{ $l }}" {{ $league === $l ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    @if ($league)
        <a href="{{ route('teams.index', array_filter(['sort' => $sort, 'dir' => $dir])) }}"
           class="text-sm text-gray-400 hover:text-gray-600 pb-0.5">× Rimuovi filtro</a>
    @endif
</form>

@if ($teams->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
        <div class="text-5xl mb-4">🛡️</div>
        <h3 class="text-lg font-semibold text-gray-700">
            {{ $league ? "Nessuna squadra nel campionato \"$league\"" : 'Nessuna squadra trovata' }}
        </h3>
        @unless($league)
            <p class="text-gray-500 text-sm mt-1 mb-6">Inizia aggiungendo la prima squadra.</p>
            <a href="{{ route('teams.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Aggiungi Squadra
            </a>
        @endunless
    </div>
@else
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50">
                        @php
                            $sortIcon = fn($col) => $sort === $col
                                ? ($dir === 'asc'
                                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>'
                                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>')
                                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>';
                            $nextDir = fn($col) => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
                        @endphp
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('teams.index', ['sort' => 'name', 'dir' => $nextDir('name'), 'filter_league' => $league]) }}"
                               class="inline-flex items-center gap-1 hover:text-gray-800 transition {{ $sort === 'name' ? 'text-indigo-600' : '' }}">
                                Nome
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $sortIcon('name') !!}
                                </svg>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Città</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <a href="{{ route('teams.index', ['sort' => 'league_division', 'dir' => $nextDir('league_division'), 'filter_league' => $league]) }}"
                               class="inline-flex items-center gap-1 hover:text-gray-800 transition {{ $sort === 'league_division' ? 'text-indigo-600' : '' }}">
                                Campionato
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $sortIcon('league_division') !!}
                                </svg>
                            </a>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Referente</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($teams as $team)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900">{{ $team->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $team->city }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $team->league_division }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $team->contact_person ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $team->contact_email ?? '—' }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('teams.show', $team) }}" title="Dettaglio"
                                       class="p-1.5 text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('teams.edit', $team) }}" title="Modifica"
                                       class="p-1.5 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                    <form action="{{ route('teams.destroy', $team) }}" method="POST" onsubmit="return confirm('Eliminare questa squadra?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Elimina"
                                                class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-md transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
