@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Campi da Rugby</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $venues->count() }} {{ $venues->count() === 1 ? 'campo' : 'campi' }}</p>
    </div>
    <a href="{{ route('venues.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuovo Campo
    </a>
</div>

@if ($venues->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm py-16 text-center">
        <div class="text-5xl mb-4">🏟️</div>
        <h3 class="text-lg font-semibold text-gray-700">Nessun campo trovato</h3>
        <p class="text-gray-500 text-sm mt-1 mb-6">Inizia aggiungendo il primo campo da rugby.</p>
        <a href="{{ route('venues.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            Aggiungi Campo
        </a>
    </div>
@else
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Città</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Indirizzo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Referente</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Telefono</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Azioni</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($venues as $venue)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-900">{{ $venue->name }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $venue->city }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $venue->address }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $venue->contact_person ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $venue->contact_phone ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center">
                                    <a href="{{ route('venues.show', $venue) }}" title="Dettaglio"
                                       class="inline-flex items-center justify-center w-8 h-8 text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('venues.edit', $venue) }}" title="Modifica"
                                       class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </a>
                                    <form action="{{ route('venues.destroy', $venue) }}" method="POST" onsubmit="return confirm('Eliminare questo campo?')" class="contents">
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
