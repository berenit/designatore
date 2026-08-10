@extends('layouts.app')

@section('content')
<div class="mb-6">
    <a href="{{ route('venues.show', $venue) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $venue->name }}
    </a>
</div>

<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Modifica Campo</h1>

    <form action="{{ route('venues.update', $venue) }}" method="POST"
          class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
        @csrf @method('PUT')

        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nome campo</label>
                <input id="name" type="text" name="name" value="{{ old('name', $venue->name) }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Città</label>
                <input id="city" type="text" name="city" value="{{ old('city', $venue->city) }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('city') border-red-400 @enderror">
                @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
                <input id="address" type="text" name="address" value="{{ old('address', $venue->address) }}" required
                       class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('address') border-red-400 @enderror">
                @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="border-t border-gray-100 pt-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Contatto</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="contact_person" class="block text-sm font-medium text-gray-700 mb-1">Referente <span class="text-gray-400">(opz.)</span></label>
                    <input id="contact_person" type="text" name="contact_person" value="{{ old('contact_person', $venue->contact_person) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('contact_person') border-red-400 @enderror">
                    @error('contact_person')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1">Telefono <span class="text-gray-400">(opz.)</span></label>
                    <input id="contact_phone" type="tel" name="contact_phone" value="{{ old('contact_phone', $venue->contact_phone) }}"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Aggiorna Campo
            </button>
            <a href="{{ route('venues.show', $venue) }}" class="text-sm text-gray-500 hover:text-gray-700">Annulla</a>
        </div>
    </form>
</div>
@endsection
