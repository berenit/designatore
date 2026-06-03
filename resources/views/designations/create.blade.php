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

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Nuova Designazione</h1>

    <form action="{{ route('designations.store') }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label for="match_id" class="block text-sm font-medium text-gray-700 mb-1">Partita</label>
            <select id="match_id" name="match_id" required
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('match_id') border-red-400 @enderror">
                <option value="">Seleziona una partita...</option>
                @foreach($matches as $match)
                    <option value="{{ $match->id }}" {{ old('match_id', $preselect) == $match->id ? 'selected' : '' }}>
                        {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}
                        ({{ \Carbon\Carbon::parse($match->date_time)->format('d/m/Y H:i') }})
                    </option>
                @endforeach
            </select>
            @error('match_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="referee_id" class="block text-sm font-medium text-gray-700 mb-1">Arbitro</label>
            <select id="referee_id" name="referee_id" required
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('referee_id') border-red-400 @enderror">
                <option value="">Seleziona un arbitro...</option>
                @foreach($referees as $referee)
                    <option value="{{ $referee->id }}" {{ old('referee_id') == $referee->id ? 'selected' : '' }}>
                        {{ $referee->name }} — {{ $referee->license_level }}
                    </option>
                @endforeach
            </select>
            @error('referee_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Note <span class="text-gray-400">(opzionale)</span></label>
            <textarea id="notes" name="notes" rows="3"
                      class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
            @error('notes')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-between pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Salva Designazione
            </button>
            <a href="{{ route('designations.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>
</div>
@endsection
