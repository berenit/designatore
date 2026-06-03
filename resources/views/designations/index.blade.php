@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Designazioni</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $weekStart->locale('it')->isoFormat('D MMMM') }} – {{ $weekEnd->locale('it')->isoFormat('D MMMM YYYY') }}
        </p>
    </div>
    <a href="{{ route('designations.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuova Designazione
    </a>
</div>

{{-- Navigazione settimana --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('designations.index', ['week' => $prevWeek]) }}"
       class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Settimana precedente
    </a>

    @unless($weekStart->isSameWeek(now()))
        <a href="{{ route('designations.index') }}"
           class="text-sm text-indigo-600 hover:text-indigo-800 font-medium px-3 py-1.5">
            Questa settimana
        </a>
    @endunless

    <a href="{{ route('designations.index', ['week' => $nextWeek]) }}"
       class="inline-flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 shadow-sm transition ml-auto">
        Settimana successiva
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </a>
</div>

@if ($matches->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
        <div class="text-5xl mb-4">📅</div>
        <h3 class="text-lg font-semibold text-gray-700">Nessuna partita questa settimana</h3>
        <p class="text-gray-400 text-sm mt-1">Naviga nelle settimane o aggiungi nuove partite.</p>
    </div>
@else
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Partita</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stato partita</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Arbitro</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stato designazione</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($matches as $match)
                        @php $designation = $match->designation; @endphp
                        <tr class="hover:bg-gray-50 transition {{ $designation ? '' : 'bg-amber-50/40' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ \Carbon\Carbon::parse($match->date_time)->locale('it')->isoFormat('ddd D/MM') }}
                                </div>
                                <div class="text-xs text-gray-900 mt-0.5">
                                    {{ \Carbon\Carbon::parse($match->date_time)->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900">
                                    {{ $match->homeTeam->name }} <span class="text-gray-400 font-normal">vs</span> {{ $match->awayTeam->name }}
                                </div>
                                <div class="text-xs text-gray-400 mt-0.5">{{ $match->venue }} · {{ $match->competition_type }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if ($match->status === 'scheduled') bg-blue-100 text-blue-800
                                    @elseif ($match->status === 'postponed') bg-yellow-100 text-yellow-800
                                    @elseif ($match->status === 'cancelled') bg-red-100 text-red-800
                                    @else bg-green-100 text-green-800 @endif">
                                    {{ ucfirst($match->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($designation)
                                    <span class="font-medium text-gray-900">{{ $designation->referee->name }}</span>
                                    <span class="text-xs text-gray-400 block mt-0.5">{{ $designation->referee->license_level }}</span>
                                @else
                                    <span class="text-amber-600 text-xs font-medium">Da designare</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($designation)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                        @if ($designation->status === 'pending')   bg-yellow-100 text-yellow-800
                                        @elseif ($designation->status === 'confirmed') bg-green-100 text-green-800
                                        @elseif ($designation->status === 'completed') bg-blue-100 text-blue-800
                                        @else bg-red-100 text-red-800 @endif">
                                        {{ ucfirst($designation->status) }}
                                    </span>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center">
                                    @if ($designation)
                                        <a href="{{ route('designations.show', $designation) }}" title="Dettaglio"
                                           class="inline-flex items-center justify-center w-8 h-8 text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <a href="{{ route('designations.edit', $designation) }}" title="Modifica"
                                           class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </a>
                                        <form action="{{ route('designations.destroy', $designation) }}" method="POST" onsubmit="return confirm('Eliminare questa designazione?')" class="contents">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Elimina"
                                                    class="inline-flex items-center justify-center w-8 h-8 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-md transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('designations.create', ['match_id' => $match->id]) }}" title="Designa"
                                           class="inline-flex items-center justify-center w-8 h-8 text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Designa
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Legenda --}}
    <p class="text-xs text-gray-400 mt-3 flex items-center gap-2">
        <span class="inline-block w-3 h-3 rounded-sm bg-amber-50 border border-amber-200"></span>
        Partite senza arbitro designato
    </p>
@endif
@endsection
