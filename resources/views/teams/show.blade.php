@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('teams.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Squadre
    </a>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h1 class="text-xl font-bold text-gray-900">{{ $team->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $team->city }} — {{ $team->league_division }}</p>
        </div>

        <dl class="divide-y divide-gray-100">
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Referente</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $team->contact_person }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $team->contact_email }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Telefono</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $team->contact_phone ?? '—' }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Partite in casa</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $team->homeMatches->count() }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Partite in trasferta</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $team->awayMatches->count() }}</dd>
            </div>
        </dl>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <a href="{{ route('teams.edit', $team) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Modifica
            </a>
            <form action="{{ route('teams.destroy', $team) }}" method="POST" onsubmit="return confirm('Eliminare questa squadra?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                    Elimina
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
