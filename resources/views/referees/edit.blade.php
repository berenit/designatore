@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('referees.show', $referee) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $referee->name }}
    </a>
</div>

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Modifica Arbitro</h1>

    <form action="{{ route('referees.update', $referee) }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
            <input id="name" type="text" name="name" value="{{ old('name', $referee->name) }}" required
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $referee->email) }}" required
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-400 @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefono <span class="text-gray-400">(opzionale)</span></label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone', $referee->phone) }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="license_level" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select id="license_level" name="license_level" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('license_level') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    @foreach (\App\Models\Referee::CATEGORIES as $level)
                        <option value="{{ $level }}" {{ old('license_level', $referee->license_level) === $level ? 'selected' : '' }}>{{ $level }}</option>
                    @endforeach
                </select>
                @error('license_level')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="availability_status" class="block text-sm font-medium text-gray-700 mb-1">Disponibilità</label>
                <select id="availability_status" name="availability_status" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('availability_status') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    @foreach (\App\Models\Referee::AVAILABILITY_LABELS as $value => $label)
                        <option value="{{ $value }}" {{ old('availability_status', $referee->availability_status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('availability_status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Aggiorna Arbitro
            </button>
            <a href="{{ route('referees.show', $referee) }}" class="text-sm text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mt-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Indisponibilità</h2>
        <p class="text-xs text-gray-400 mb-4">Nei periodi indicati l'arbitro non potrà essere designato.</p>

        @if ($referee->unavailabilities->isEmpty())
            <p class="text-sm text-gray-500 mb-4">Nessun periodo di indisponibilità registrato.</p>
        @else
            <ul class="divide-y divide-gray-100 mb-4">
                @foreach ($referee->unavailabilities as $period)
                    <li class="py-2 flex items-center justify-between text-sm">
                        <span class="text-gray-700">
                            Dal {{ $period->start_date->format('d/m/Y') }} al {{ $period->end_date->format('d/m/Y') }}
                        </span>
                        <form action="{{ route('referees.unavailabilities.destroy', [$referee, $period]) }}" method="POST"
                              x-data @submit.prevent="$store.confirmModal.ask('Rimuovere questo periodo di indisponibilità?', $el)">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-gray-400 hover:text-red-500" title="Rimuovi periodo">&times;</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form action="{{ route('referees.unavailabilities.store', $referee) }}" method="POST" class="flex items-end gap-3">
            @csrf
            <div class="flex-1">
                <label for="start_date" class="block text-xs font-medium text-gray-700 mb-1">Dal</label>
                <input id="start_date" type="date" name="start_date" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('start_date') border-red-400 @enderror">
            </div>
            <div class="flex-1">
                <label for="end_date" class="block text-xs font-medium text-gray-700 mb-1">Al</label>
                <input id="end_date" type="date" name="end_date" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('end_date') border-red-400 @enderror">
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                Aggiungi
            </button>
        </form>
        @error('start_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        @error('end_date')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
@endsection
