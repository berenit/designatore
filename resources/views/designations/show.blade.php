@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('designations.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Designazioni
    </a>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-start justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">
                    {{ $designation->match->homeTeam->name }} vs {{ $designation->match->awayTeam->name }}
                </h1>
                <p class="text-sm text-gray-500 mt-1">
                    {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y H:i') }} — {{ $designation->match->venue }}
                </p>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                @if ($designation->status === 'pending') bg-yellow-100 text-yellow-800
                @elseif ($designation->status === 'confirmed') bg-blue-100 text-blue-800
                @elseif ($designation->status === 'completed') bg-green-100 text-green-800
                @else bg-red-100 text-red-800
                @endif">
                {{ ucfirst($designation->status) }}
            </span>
        </div>

        <dl class="divide-y divide-gray-100">
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Arbitro</dt>
                <dd class="text-sm text-gray-900 col-span-2">
                    {{ $designation->referee->name }}
                    <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                        {{ $designation->referee->license_level }}
                    </span>
                </dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Stato partita</dt>
                <dd class="text-sm col-span-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                        {{ $designation->match->status == 'scheduled' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $designation->match->status == 'completed' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $designation->match->status == 'cancelled' ? 'bg-red-100 text-red-800' : '' }}
                        {{ $designation->match->status == 'postponed' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                        {{ ucfirst($designation->match->status) }}
                    </span>
                </dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Competizione</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $designation->match->competition_type }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Note</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $designation->notes ?: '—' }}</dd>
            </div>
        </dl>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <a href="{{ route('designations.edit', $designation) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Modifica
            </a>
            <form action="{{ route('designations.destroy', $designation) }}" method="POST" onsubmit="return confirm('Eliminare questa designazione?')">
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
