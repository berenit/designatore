<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-6">
        <h1 class="text-2xl font-bold text-gray-900">Indisponibilità</h1>

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
            <div class="max-w-xl">
                <p class="text-sm text-gray-600">
                    @if ($referee->isFuoriRuolo())
                        Nei periodi indicati non potrai essere designato come {{ $referee->is_evaluator ? 'Tutor o Valutatore' : 'Tutor' }}.
                    @else
                        Nei periodi indicati non potrai essere designato.
                    @endif
                </p>

                @if ($unavailabilities->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">Nessun periodo di indisponibilità registrato.</p>
                @else
                    <ul class="mt-4 divide-y divide-gray-100">
                        @foreach ($unavailabilities as $period)
                            <li class="py-2 flex items-center justify-between text-sm">
                                <span class="text-gray-700">
                                    Dal {{ $period->start_date->format('d/m/Y') }} al {{ $period->end_date->format('d/m/Y') }}
                                </span>
                                <form action="{{ route('profile.unavailabilities.destroy', $period->id) }}" method="POST"
                                      x-data @submit.prevent="$store.confirmModal.ask('Rimuovere questo periodo di indisponibilità?', $el)">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-500" title="Rimuovi periodo" aria-label="Rimuovi periodo">&times;</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <form action="{{ route('profile.unavailabilities.store') }}" method="POST" class="mt-4 flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-36">
                        <x-input-label for="start_date" value="Dal" />
                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date')" required />
                    </div>
                    <div class="flex-1 min-w-36">
                        <x-input-label for="end_date" value="Al" />
                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date')" required />
                    </div>
                    <x-primary-button>Aggiungi</x-primary-button>
                </form>
                <x-input-error class="mt-2" :messages="$errors->get('start_date')" />
                <x-input-error class="mt-2" :messages="$errors->get('end_date')" />
            </div>
        </div>
    </div>
</x-app-layout>
