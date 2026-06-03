@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('rugby-matches.show', $match) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Dettaglio partita
    </a>
</div>

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Modifica Partita</h1>

    <form action="{{ route('rugby-matches.update', $match) }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="date_time" class="block text-sm font-medium text-gray-700 mb-1">Data e Ora</label>
                <input id="date_time" type="datetime-local" name="date_time" value="{{ old('date_time', $match->date_time) }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('date_time') border-red-400 @enderror">
                @error('date_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="venue" class="block text-sm font-medium text-gray-700 mb-1">Campo</label>
                <input id="venue" type="text" name="venue" value="{{ old('venue', $match->venue) }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('venue') border-red-400 @enderror">
                @error('venue')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="home_team_id" class="block text-sm font-medium text-gray-700 mb-1">Squadra di casa</label>
                <select id="home_team_id" name="home_team_id" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('home_team_id') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" {{ old('home_team_id', $match->home_team_id) == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                    @endforeach
                </select>
                @error('home_team_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="away_team_id" class="block text-sm font-medium text-gray-700 mb-1">Squadra ospite</label>
                <select id="away_team_id" name="away_team_id" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('away_team_id') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" {{ old('away_team_id', $match->away_team_id) == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                    @endforeach
                </select>
                @error('away_team_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="competition_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo competizione</label>
                <select id="competition_type" name="competition_type" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('competition_type') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    @foreach (['League', 'Cup', 'Friendly', 'International', 'Tournament'] as $type)
                        <option value="{{ $type }}" {{ old('competition_type', $match->competition_type) === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
                @error('competition_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
                <select id="status" name="status" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    <option value="scheduled" {{ old('status', $match->status) === 'scheduled' ? 'selected' : '' }}>Programmata</option>
                    <option value="postponed" {{ old('status', $match->status) === 'postponed' ? 'selected' : '' }}>Rinviata</option>
                    <option value="cancelled" {{ old('status', $match->status) === 'cancelled' ? 'selected' : '' }}>Annullata</option>
                    <option value="completed" {{ old('status', $match->status) === 'completed' ? 'selected' : '' }}>Completata</option>
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Aggiorna Partita
            </button>
            <a href="{{ route('rugby-matches.show', $match) }}" class="text-sm text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>
</div>
@endsection
