@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('rugby-matches.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Partite
    </a>
</div>

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">
                        {{ $match->homeTeam->name ?? 'N/A' }} <span class="text-gray-400 font-normal">vs</span> {{ $match->awayTeam->name ?? 'N/A' }}
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ \Carbon\Carbon::parse($match->date_time)->format('d/m/Y H:i') }} — {{ $match->venue }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                        @if ($match->competition_type === 'International') bg-blue-100 text-blue-800
                        @elseif ($match->competition_type === 'Cup') bg-purple-100 text-purple-800
                        @elseif ($match->competition_type === 'Friendly') bg-green-100 text-green-800
                        @elseif ($match->competition_type === 'Tournament') bg-pink-100 text-pink-800
                        @else bg-gray-100 text-gray-700
                        @endif">
                        {{ $match->competition_type }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                        @if ($match->status === 'scheduled') bg-blue-100 text-blue-800
                        @elseif ($match->status === 'postponed') bg-yellow-100 text-yellow-800
                        @elseif ($match->status === 'cancelled') bg-red-100 text-red-800
                        @else bg-green-100 text-green-800
                        @endif">
                        {{ ucfirst($match->status) }}
                    </span>
                </div>
            </div>
        </div>

        <dl class="divide-y divide-gray-100">
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Designazione</dt>
                <dd class="text-sm col-span-2">
                    @if ($match->designation)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                            @if ($match->designation->status === 'confirmed') bg-blue-100 text-blue-800
                            @elseif ($match->designation->status === 'completed') bg-green-100 text-green-800
                            @elseif ($match->designation->status === 'pending') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ ucfirst($match->designation->status) }}
                        </span>
                        <span class="text-gray-500 ml-2">{{ $match->designation->referee->name }}</span>
                    @else
                        <span class="text-gray-400 italic">Non assegnata</span>
                        <a href="{{ route('designations.create') }}?match_id={{ $match->id }}"
                           class="ml-3 text-xs text-indigo-600 hover:text-indigo-800 font-medium">Assegna →</a>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <a href="{{ route('rugby-matches.edit', $match) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Modifica
            </a>
            <form action="{{ route('rugby-matches.destroy', $match) }}" method="POST" onsubmit="return confirm('Eliminare questa partita?')">
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
