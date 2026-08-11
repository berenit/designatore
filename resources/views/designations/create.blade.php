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

    @php
        $refereesJson = $referees->map(fn ($r) => [
            'id' => $r->id,
            'label' => $r->name.' — '.$r->license_level,
        ])->values()->toJson();
        $oldState = [
            'matchId'   => (string) old('match_id', $preselect),
            'referees'  => (object) old('referees', []),
            'arbitri'   => array_map('strval', old('arbitri', [])),
        ];
    @endphp

    <form action="{{ route('designations.store') }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5"
          x-data="designationForm(
              {{ json_encode($matchRoles) }},
              {{ json_encode($matchIsMulti) }},
              {{ json_encode($matchAssignments) }},
              {{ $refereesJson }},
              {{ json_encode($oldState) }}
          )">
        @csrf

        <div>
            <label for="match_id" class="block text-sm font-medium text-gray-700 mb-1">Partita</label>
            <select id="match_id" name="match_id" x-model="matchId" @change="onMatchChange()" required
                    class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('match_id') border-red-400 @enderror">
                <option value="">Seleziona una partita...</option>
                @foreach($matches as $match)
                    <option value="{{ $match->id }}">
                        {{ $match->label }} ({{ $match->date_time->format('d/m/Y H:i') }})
                    </option>
                @endforeach
            </select>
            @error('match_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div x-show="matchId" x-transition>
            {{-- Arbitri: uno o più, liberi (senza ruolo specifico) nei Concentramenti/Tornei --}}
            <template x-if="isMulti">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Arbitri</label>
                    <div class="space-y-2">
                        <template x-for="(refId, index) in arbitri" :key="index">
                            <div class="flex items-center gap-2">
                                <select class="flex-1 w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        name="arbitri[]" x-model="arbitri[index]">
                                    <option value="">— nessuno —</option>
                                    <template x-for="r in refereeList" :key="r.id">
                                        <option :value="r.id" x-text="r.label"></option>
                                    </template>
                                </select>
                                <button type="button" @click="removeArbitro(index)"
                                        class="text-gray-400 hover:text-red-500 px-2" title="Rimuovi arbitro">&times;</button>
                            </div>
                        </template>
                    </div>
                    <button type="button" @click="addArbitro()"
                            class="mt-2 text-sm text-indigo-600 hover:text-indigo-800">+ Aggiungi arbitro</button>
                    <p class="mt-2 text-xs text-gray-400">Almeno un arbitro è obbligatorio.</p>
                    @error('arbitri')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </template>

            {{-- Una riga arbitro per ciascun ruolo previsto dalla gara selezionata --}}
            <label class="block text-sm font-medium text-gray-700 mb-2" x-text="isMulti ? 'Altre figure di gara' : 'Arbitri per ruolo'"></label>
            <div class="space-y-3">
                <template x-if="!isMulti">
                    <div class="grid grid-cols-3 gap-3 items-center">
                        <span class="text-sm text-gray-700">Arbitro</span>
                        <select class="col-span-2 w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                name="referees[Arbitro]" x-model="referees['Arbitro']">
                            <option value="">— nessuno —</option>
                            <template x-for="r in refereeList" :key="r.id">
                                <option :value="r.id" x-text="r.label"></option>
                            </template>
                        </select>
                    </div>
                </template>
                <template x-for="role in otherRoles" :key="role">
                    <div class="grid grid-cols-3 gap-3 items-center">
                        <span class="text-sm text-gray-700" x-text="roleLabel(role)"></span>
                        <select class="col-span-2 w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                :name="`referees[${role}]`" x-model="referees[role]">
                            <option value="">— nessuno —</option>
                            <template x-for="r in refereeList" :key="r.id">
                                <option :value="r.id" x-text="r.label"></option>
                            </template>
                        </select>
                    </div>
                </template>
            </div>
            <p class="mt-2 text-xs text-gray-400" x-text="isMulti ? 'Nei Concentramenti il Direttore di concentramento è obbligatorio.' : 'L\'Arbitro è sempre obbligatorio. Lascia \'nessuno\' per i ruoli che non vuoi designare ora.'"></p>
            @error('referees')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
                Salva Designazioni
            </button>
            <a href="{{ route('designations.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>
</div>

<script>
function designationForm(matchRoles, matchIsMulti, matchAssignments, refereeList, old) {
    // Etichette più leggibili per alcuni ruoli interni
    const LABELS = {
        'Assistente 1': 'Giudice di linea 1',
        'Assistente 2': 'Giudice di linea 2',
    };

    return {
        matchRoles,        // { match_id: ['Arbitro', ...] }
        matchIsMulti,      // { match_id: bool }
        matchAssignments,  // { match_id: { roles: { role: referee_id, ... }, arbitri: [referee_id, ...] } }
        refereeList,       // [{ id, label }]
        matchId: old.matchId || '',
        referees: {},      // { role: referee_id }, ruolo Arbitro incluso solo per le gare singole
        arbitri: [],       // [referee_id, ...], uno o più arbitri liberi per Concentramenti/Tornei

        init() {
            if (this.matchId) {
                this.onMatchChange();
                // Ripristina i valori inviati in caso di errore di validazione
                Object.assign(this.referees, old.referees || {});
                if (old.arbitri && old.arbitri.length) {
                    this.arbitri = old.arbitri;
                }
            }
        },

        get isMulti() {
            return !!this.matchIsMulti[this.matchId];
        },

        get roles() {
            return this.matchRoles[this.matchId] || [];
        },

        // Ruoli previsti diversi dall'Arbitro, gestito a parte (singolo o lista libera)
        get otherRoles() {
            return this.roles.filter((r) => r !== 'Arbitro');
        },

        roleLabel(role) {
            return LABELS[role] || role;
        },

        // Al cambio gara, pre-compila con le designazioni eventualmente già presenti
        onMatchChange() {
            const prefill = this.matchAssignments[this.matchId] || { roles: {}, arbitri: [] };
            const next = {};
            for (const role of this.otherRoles) {
                next[role] = prefill.roles[role] != null ? String(prefill.roles[role]) : '';
            }
            this.referees = next;

            if (this.isMulti) {
                this.arbitri = (prefill.arbitri || []).map(String);
                if (this.arbitri.length === 0) this.arbitri = [''];
            } else {
                this.referees['Arbitro'] = prefill.roles['Arbitro'] != null ? String(prefill.roles['Arbitro']) : '';
            }
        },

        addArbitro() {
            this.arbitri.push('');
        },

        removeArbitro(index) {
            this.arbitri.splice(index, 1);
            if (this.arbitri.length === 0) this.arbitri.push('');
        },
    }
}
</script>
@endsection
