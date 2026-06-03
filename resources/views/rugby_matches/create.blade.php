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

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Nuova Partita</h1>

    {{-- Dati teams come JSON per Alpine --}}
    @php
        $teamsJson  = $teams->map(fn($t) => [
            'id'     => $t->id,
            'name'   => $t->name,
            'league' => $t->league_division,
        ])->values()->toJson();
        $oldLeague   = old('_league');
        $oldHomeId   = old('home_team_id');
        $oldAwayId   = old('away_team_id');
        $oldDate     = old('date_time', $defaultDate);
    @endphp

    <form action="{{ route('rugby-matches.store') }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5"
          x-data="matchForm({{ $teamsJson }}, {{ json_encode($bookedDates) }}, {{ json_encode($oldLeague) }}, {{ json_encode($oldHomeId) }}, {{ json_encode($oldAwayId) }}, {{ json_encode($oldDate) }})">
        @csrf

        {{-- Campo nascosto per conservare il campionato scelto in caso di errore validazione --}}
        <input type="hidden" name="_league" x-bind:value="league">

        {{-- Step 1: campionato --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campionato</label>
            <select x-model="league" @change="homeId = ''; awayId = ''"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Seleziona campionato...</option>
                @foreach ($leagues as $l)
                    <option value="{{ $l }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>

        {{-- Step 2: squadre (disabilitate finché non si sceglie il campionato) --}}
        <div class="grid grid-cols-2 gap-4" x-show="league" x-transition>
            <div>
                <label for="home_team_id" class="block text-sm font-medium text-gray-700 mb-1">Squadra di casa</label>
                <select id="home_team_id" name="home_team_id" x-model="homeId" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('home_team_id') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    <template x-for="t in filteredTeams" :key="t.id">
                        <option :value="t.id" x-text="t.name" :selected="t.id == homeId"></option>
                    </template>
                </select>
                @error('home_team_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="away_team_id" class="block text-sm font-medium text-gray-700 mb-1">Squadra ospite</label>
                <select id="away_team_id" name="away_team_id" x-model="awayId" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('away_team_id') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    <template x-for="t in filteredTeams" :key="t.id">
                        <option :value="t.id" x-text="t.name" :disabled="t.id == homeId && homeId !== ''" :selected="t.id == awayId"></option>
                    </template>
                </select>
                @error('away_team_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Resto del form --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="date_time" class="block text-sm font-medium text-gray-700 mb-1">Data e Ora</label>
                <input id="date_time" type="datetime-local" name="date_time" x-model="dateTime"
                       @change="homeId = ''; awayId = ''" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('date_time') border-red-400 @enderror">
                @error('date_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="venue" class="block text-sm font-medium text-gray-700 mb-1">Campo</label>
                <input id="venue" type="text" name="venue" value="{{ old('venue') }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('venue') border-red-400 @enderror">
                @error('venue')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="competition_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo competizione</label>
                <select id="competition_type" name="competition_type" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('competition_type') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    @foreach (['League', 'Cup', 'Friendly', 'International', 'Tournament'] as $type)
                        <option value="{{ $type }}" {{ old('competition_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
                @error('competition_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
                <select id="status" name="status" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    <option value="scheduled" {{ old('status') === 'scheduled' ? 'selected' : '' }}>Programmata</option>
                    <option value="postponed" {{ old('status') === 'postponed' ? 'selected' : '' }}>Rinviata</option>
                    <option value="cancelled" {{ old('status') === 'cancelled' ? 'selected' : '' }}>Annullata</option>
                    <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completata</option>
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Salva Partita
            </button>
            <a href="{{ route('rugby-matches.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>
</div>

<script>
function matchForm(teams, bookedDates, oldLeague, oldHomeId, oldAwayId, oldDate) {
    return {
        teams,
        bookedDates,   // { team_id: ['2026-06-08', ...] }
        league:   oldLeague || '',
        homeId:   oldHomeId ? String(oldHomeId) : '',
        awayId:   oldAwayId ? String(oldAwayId) : '',
        dateTime: oldDate || '',

        // Data nel formato YYYY-MM-DD estratta dal datetime-local
        get selectedDate() {
            return this.dateTime ? this.dateTime.substring(0, 10) : '';
        },

        // Squadre filtrate per campionato e NON già impegnate nella data scelta
        get filteredTeams() {
            if (!this.league) return [];
            const date = this.selectedDate;
            return this.teams.filter(t => {
                if (t.league !== this.league) return false;
                if (!date) return true;
                const booked = this.bookedDates[t.id] || [];
                return !booked.includes(date);
            });
        },
    }
}
</script>
@endsection
