@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('referees.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Arbitri
    </a>
</div>

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Nuovo Arbitro</h1>

    <form action="{{ route('referees.store') }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-red-400 @enderror">
            @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefono <span class="text-gray-400">(opzionale)</span></label>
            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                   class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="license_level" class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                <select id="license_level" name="license_level" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('license_level') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    @foreach (\App\Models\Referee::CATEGORIES as $level)
                        <option value="{{ $level }}" {{ old('license_level') === $level ? 'selected' : '' }}>{{ $level }}</option>
                    @endforeach
                </select>
                @error('license_level')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="availability_status" class="block text-sm font-medium text-gray-700 mb-1">Disponibilità</label>
                <select id="availability_status" name="availability_status" required
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('availability_status') border-red-400 @enderror">
                    <option value="">Seleziona...</option>
                    <option value="available" {{ old('availability_status') === 'available' ? 'selected' : '' }}>Disponibile</option>
                    <option value="limited" {{ old('availability_status') === 'limited' ? 'selected' : '' }}>Limitata</option>
                    <option value="unavailable" {{ old('availability_status') === 'unavailable' ? 'selected' : '' }}>Non disponibile</option>
                </select>
                @error('availability_status')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Salva Arbitro
            </button>
            <a href="{{ route('referees.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>
</div>
@endsection
