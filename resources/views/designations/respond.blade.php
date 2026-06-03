@extends('layouts.app')

@section('content')
<div class="max-w-lg mx-auto text-center py-12">
    @if ($alreadyProcessed)
        <div class="text-5xl mb-4">ℹ️</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Designazione già elaborata</h1>
        <p class="text-gray-500">
            Questa designazione è già in stato
            <span class="font-semibold">{{ ucfirst($designation->status) }}</span>
            e non può essere modificata di nuovo tramite questo link.
        </p>
    @elseif ($action === 'confirm')
        <div class="text-5xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Designazione accettata</h1>
        <p class="text-gray-600">
            Hai confermato la tua disponibilità per dirigere:
        </p>
        <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-left">
            <p class="font-bold text-gray-900 text-lg">
                {{ $designation->match->homeTeam->name }} vs {{ $designation->match->awayTeam->name }}
            </p>
            <p class="text-gray-500 text-sm mt-1">
                {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y \a\l\l\e H:i') }}
                — {{ $designation->match->venue }}
            </p>
        </div>
        <p class="text-gray-400 text-sm mt-6">Il designatore è stato informato della tua conferma.</p>
    @else
        <div class="text-5xl mb-4">❌</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Designazione rifiutata</h1>
        <p class="text-gray-600">
            Hai rifiutato la designazione per:
        </p>
        <div class="mt-4 bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-left">
            <p class="font-bold text-gray-900 text-lg">
                {{ $designation->match->homeTeam->name }} vs {{ $designation->match->awayTeam->name }}
            </p>
            <p class="text-gray-500 text-sm mt-1">
                {{ \Carbon\Carbon::parse($designation->match->date_time)->format('d/m/Y \a\l\l\e H:i') }}
                — {{ $designation->match->venue }}
            </p>
        </div>
        <p class="text-gray-400 text-sm mt-6">Il designatore è stato informato del rifiuto e provvederà a una nuova assegnazione.</p>
    @endif
</div>
@endsection
