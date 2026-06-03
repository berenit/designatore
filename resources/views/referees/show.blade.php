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

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $referee->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                        @if ($referee->license_level === 'International') bg-blue-100 text-blue-800
                        @elseif ($referee->license_level === 'National') bg-purple-100 text-purple-800
                        @elseif ($referee->license_level === 'Regional') bg-pink-100 text-pink-800
                        @else bg-gray-100 text-gray-700
                        @endif">
                        {{ $referee->license_level }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                        @if ($referee->availability_status === 'available') bg-green-100 text-green-800
                        @elseif ($referee->availability_status === 'limited') bg-yellow-100 text-yellow-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ ucfirst($referee->availability_status) }}
                    </span>
                </div>
            </div>
        </div>

        <dl class="divide-y divide-gray-100">
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Email</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $referee->email }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Telefono</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $referee->phone ?? '—' }}</dd>
            </div>
            <div class="px-6 py-4 grid grid-cols-3 gap-4">
                <dt class="text-sm font-medium text-gray-500">Designazioni</dt>
                <dd class="text-sm text-gray-900 col-span-2">{{ $referee->designations->count() }}</dd>
            </div>
        </dl>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
            <a href="{{ route('referees.edit', $referee) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Modifica
            </a>
            <form action="{{ route('referees.destroy', $referee) }}" method="POST" onsubmit="return confirm('Eliminare questo arbitro?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-red-300 text-red-600 text-sm font-medium rounded-lg hover:bg-red-50 transition">
                    Elimina
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
