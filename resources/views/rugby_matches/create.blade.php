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
        $teamsJson = $teams->map(fn($t) => [
            'id'     => $t->id,
            'name'   => $t->name,
            'league' => $t->league_division,
        ])->values()->toJson();
        $oldState = [
            'type'    => old('competition_type', ''),
            'league'  => old('_league', ''),
            'name'    => old('name', ''),
            'homeId'  => old('home_team_id', ''),
            'awayId'  => old('away_team_id', ''),
            'date'    => old('date_time', $defaultDate),
            'teamIds' => array_map('strval', old('team_ids', [])),
            'status'  => old('status', ''),
            'extraRoles' => old('extra_roles', []),
        ];
    @endphp

    <form action="{{ route('rugby-matches.store') }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5"
          x-data="matchForm({{ $teamsJson }}, {{ json_encode($bookedDates) }}, {{ json_encode($multiTeamTypes) }}, {{ json_encode($oldState) }})">
        @csrf

        {{-- Campo nascosto per conservare il campionato scelto in caso di errore validazione --}}
        <input type="hidden" name="_league" x-bind:value="league">

        {{-- Tipo competizione: pilota la modalità (partita singola vs evento multi-squadra) --}}
        <div>
            <label for="competition_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo competizione</label>
            <select id="competition_type" name="competition_type" x-model="type" @change="resetTeams()" required
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('competition_type') border-red-400 @enderror">
                <option value="">Seleziona...</option>
                @foreach ($competitionTypes as $ct)
                    <option value="{{ $ct }}">{{ $ct }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-400" x-show="isMulti" x-transition>Concentramenti e Tornei coinvolgono 3 o più squadre.</p>
            @error('competition_type')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Nome evento (solo Concentramento / Torneo) --}}
        <div x-show="isMulti" x-transition>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome evento</label>
            <input id="name" type="text" name="name" x-model="name" :disabled="!isMulti" :required="isMulti"
                   placeholder="Es. Concentramento U14 - Roma"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Campionato (filtra le squadre) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Campionato</label>
            <select x-model="league" @change="resetTeams()"
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Seleziona campionato...</option>
                @foreach ($leagues as $l)
                    <option value="{{ $l }}">{{ $l }}</option>
                @endforeach
            </select>
        </div>

        {{-- Squadre — Partita singola: casa vs ospite --}}
        <div class="grid grid-cols-2 gap-4" x-show="league && !isMulti" x-transition>
            <div>
                <label for="home_team_id" class="block text-sm font-medium text-gray-700 mb-1">Squadra di casa</label>
                <select id="home_team_id" name="home_team_id" x-model="homeId" :disabled="isMulti" :required="!isMulti"
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
                <select id="away_team_id" name="away_team_id" x-model="awayId" :disabled="isMulti" :required="!isMulti"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('away_team_id') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    <template x-for="t in filteredTeams" :key="t.id">
                        <option :value="t.id" x-text="t.name" :disabled="t.id == homeId && homeId !== ''" :selected="t.id == awayId"></option>
                    </template>
                </select>
                @error('away_team_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Squadre — Concentramento / Torneo: selezione multipla (3+) --}}
        <div x-show="league && isMulti" x-transition>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Squadre partecipanti
                <span class="text-gray-400 font-normal">(min. 3 — selezionate: <span x-text="teamIds.length"></span>)</span>
            </label>
            <div class="grid grid-cols-2 gap-2 max-h-56 overflow-y-auto rounded-lg border border-gray-200 p-3 @error('team_ids') border-red-400 @enderror">
                <template x-for="t in filteredTeams" :key="t.id">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="team_ids[]" :value="t.id" x-model="teamIds"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span x-text="t.name"></span>
                    </label>
                </template>
                <p class="text-xs text-gray-400 col-span-2" x-show="filteredTeams.length === 0">Nessuna squadra disponibile per il campionato/la data selezionati.</p>
            </div>
            @error('team_ids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('team_ids.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- Data, campo, stato --}}
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="date_time" class="block text-sm font-medium text-gray-700 mb-1">Data e Ora</label>
                <input id="date_time" type="datetime-local" name="date_time" x-model="dateTime"
                       @change="resetTeams()" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('date_time') border-red-400 @enderror">
                @error('date_time')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="venue_id" class="block text-sm font-medium text-gray-700 mb-1">Campo</label>
                <select id="venue_id" name="venue_id" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('venue_id') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    @foreach ($venues as $v)
                        <option value="{{ $v->id }}" {{ (string) old('venue_id') === (string) $v->id ? 'selected' : '' }}>
                            {{ $v->name }} — {{ $v->city }}
                        </option>
                    @endforeach
                </select>
                @error('venue_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="col-span-2">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
                <select id="status" name="status" x-model="status" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('status') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    <option value="scheduled">Programmata</option>
                    <option value="postponed">Rinviata</option>
                    <option value="cancelled">Annullata</option>
                    <option value="completed">Completata</option>
                </select>
                @error('status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        {{-- Figure di gara previste --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Figure di gara previste</label>
            <div class="rounded-lg border border-gray-200 p-3 space-y-2">
                <label class="flex items-center gap-2 text-sm text-gray-500">
                    <input type="checkbox" checked disabled
                           class="rounded border-gray-300 text-indigo-600">
                    <span>Arbitro <span class="text-gray-400">(sempre previsto)</span></span>
                </label>
                @foreach ($extraRoleOptions as $key => $option)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        @if ($key === 'director')
                            <input type="checkbox" name="extra_roles[]" value="{{ $key }}"
                                   x-model="directorChecked" :disabled="type === 'Concentramento'"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>{{ $option['label'] }} <span class="text-gray-400 text-xs" x-show="type === 'Concentramento'">— obbligatorio nei Concentramenti</span></span>
                        @else
                            <input type="checkbox" name="extra_roles[]" value="{{ $key }}"
                                   {{ in_array($key, old('extra_roles', [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>{{ $option['label'] }}</span>
                        @endif
                    </label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-gray-400">Determina le righe arbitro da compilare in fase di designazione.</p>
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
function matchForm(teams, bookedDates, multiTeamTypes, old) {
    return {
        teams,
        bookedDates,    // { team_id: ['2026-06-08', ...] }
        multiTeamTypes, // ['Concentramento', 'Torneo']
        type:     old.type || '',
        league:   old.league || '',
        name:     old.name || '',
        homeId:   old.homeId ? String(old.homeId) : '',
        awayId:   old.awayId ? String(old.awayId) : '',
        dateTime: old.date || '',
        teamIds:  old.teamIds || [],
        status:   old.status || '',
        directorChecked: (old.extraRoles || []).includes('director'),

        init() {
            // Nei Concentramenti il Direttore di concentramento è sempre obbligatorio
            this.$watch('type', (value) => {
                if (value === 'Concentramento') this.directorChecked = true;
            });
        },

        // Vero per Concentramento / Torneo
        get isMulti() {
            return this.multiTeamTypes.includes(this.type);
        },

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

        // Azzera le selezioni quando cambia tipo, campionato o data
        resetTeams() {
            this.homeId = '';
            this.awayId = '';
            this.teamIds = [];
        },
    }
}
</script>
@endsection
