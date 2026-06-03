@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Partite</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $matches->count() }} {{ $matches->count() === 1 ? 'partita' : 'partite' }} programmate</p>
    </div>
    <a href="{{ route('rugby-matches.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuova Partita
    </a>
</div>

@if ($matches->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
        <div class="text-5xl mb-4">📅</div>
        <h3 class="text-lg font-semibold text-gray-700">Nessuna partita programmata</h3>
        <p class="text-gray-500 text-sm mt-1 mb-6">Aggiungi la prima partita per iniziare le designazioni.</p>
        <a href="{{ route('rugby-matches.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            Programma Partita
        </a>
    </div>
@else
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Data & Ora</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Incontro</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Campo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Competizione</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Stato</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($matches as $match)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($match->date_time)->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900">{{ $match->homeTeam->name }}</span>
                                <span class="text-gray-400 text-xs mx-1">vs</span>
                                <span class="text-sm font-semibold text-gray-900">{{ $match->awayTeam->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $match->venue }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if ($match->competition_type === 'International') bg-blue-100 text-blue-800
                                    @elseif ($match->competition_type === 'Cup') bg-purple-100 text-purple-800
                                    @elseif ($match->competition_type === 'Friendly') bg-green-100 text-green-800
                                    @elseif ($match->competition_type === 'Tournament') bg-pink-100 text-pink-800
                                    @else bg-gray-100 text-gray-700
                                    @endif">
                                    {{ $match->competition_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if ($match->status === 'scheduled') bg-blue-100 text-blue-800
                                    @elseif ($match->status === 'postponed') bg-yellow-100 text-yellow-800
                                    @elseif ($match->status === 'cancelled') bg-red-100 text-red-800
                                    @else bg-green-100 text-green-800
                                    @endif">
                                    {{ ucfirst($match->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center">
                                    <a href="{{ route('rugby-matches.show', $match) }}" title="Dettaglio"
                                       class="inline-flex items-center justify-center w-8 h-8 text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('rugby-matches.edit', $match) }}" title="Modifica"
                                       class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                    <form action="{{ route('rugby-matches.destroy', $match) }}" method="POST" onsubmit="return confirm('Eliminare questa partita?')" class="contents">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Elimina"
                                                class="inline-flex items-center justify-center w-8 h-8 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-md transition">
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
