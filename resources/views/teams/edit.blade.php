@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('teams.show', $team) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $team->name }}
    </a>
</div>

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Modifica Squadra</h1>

    <form action="{{ route('teams.update', $team) }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome squadra</label>
                <input id="name" type="text" name="name" value="{{ old('name', $team->name) }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                <input id="city" type="text" name="city" value="{{ old('city', $team->city) }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('city') border-red-400 @enderror">
                @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="league_division" class="block text-sm font-medium text-gray-700 mb-1">Campionato</label>
                <input id="league_division" type="text" name="league_division" value="{{ old('league_division', $team->league_division) }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('league_division') border-red-400 @enderror">
                @error('league_division')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="border-t border-gray-100 pt-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Contatto</p>
            <div class="space-y-4">
                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Referente <span class="text-gray-400">(opz.)</span></label>
                    <input id="contact_person" type="text" name="contact_person" value="{{ old('contact_person', $team->contact_person) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('contact_person') border-red-400 @enderror">
                    @error('contact_person')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-gray-400">(opz.)</span></label>
                        <input id="contact_email" type="email" name="contact_email" value="{{ old('contact_email', $team->contact_email) }}"
                               class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('contact_email') border-red-400 @enderror">
                        @error('contact_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Telefono <span class="text-gray-400">(opz.)</span></label>
                        <input id="contact_phone" type="tel" name="contact_phone" value="{{ old('contact_phone', $team->contact_phone) }}"
                               class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Aggiorna Squadra
            </button>
            <a href="{{ route('teams.show', $team) }}" class="text-sm text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>
</div>
@endsection
