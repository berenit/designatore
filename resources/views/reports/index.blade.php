@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Report Designazioni</h1>
        <p class="text-sm text-gray-500 mt-1">Esporta le designazioni in PDF, Markdown o testo</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

  <div class="space-y-6">

    {{-- Filtri --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Filtri (opzionali)</h2>
        <form id="report-form" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Data partita</label>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500 flex-shrink-0">Dal</span>
                    <input type="date" name="date_from" id="date_from" value="{{ $defaultFrom }}"
                           class="flex-1 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                           onchange="
                               const to = document.getElementById('date_to');
                               to.min = this.value;
                               if (to.value && to.value < this.value) to.value = this.value;
                           ">
                    <span class="text-sm text-gray-500 flex-shrink-0">al</span>
                    <input type="date" name="date_to" id="date_to" value="{{ $defaultTo }}"
                           min="{{ $defaultFrom }}"
                           class="flex-1 rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
                <select name="status" id="status"
                        class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tutti gli stati</option>
                    <option value="pending">In attesa</option>
                    <option value="confirmed">Confermate</option>
                    <option value="completed">Completate</option>
                    <option value="cancelled">Annullate</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Pulsanti export --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-3">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">Scarica report</h2>

        <button onclick="download('pdf')"
                class="w-full flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition group">
            <span class="text-2xl">📄</span>
            <div class="text-left">
                <p class="text-sm font-semibold text-red-800">PDF</p>
                <p class="text-xs text-red-500">Documento formattato, pronto per la stampa</p>
            </div>
            <svg class="w-4 h-4 text-red-400 ml-auto group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
        </button>

        <button onclick="download('markdown')"
                class="w-full flex items-center gap-3 px-4 py-3 bg-purple-50 border border-purple-200 rounded-lg hover:bg-purple-100 transition group">
            <span class="text-2xl">📝</span>
            <div class="text-left">
                <p class="text-sm font-semibold text-purple-800">Markdown</p>
                <p class="text-xs text-purple-500">Tabella .md per Notion, GitHub, editor Markdown</p>
            </div>
            <svg class="w-4 h-4 text-purple-400 ml-auto group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
        </button>

        <button onclick="copyText()"
                class="w-full flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition group">
            <span class="text-2xl">💬</span>
            <div class="text-left">
                <p class="text-sm font-semibold text-green-800">Testo per Telegram / WhatsApp</p>
                <p class="text-xs text-green-500">Copia negli appunti con emoji, pronto da incollare</p>
            </div>
            <svg id="copy-icon" class="w-4 h-4 text-green-400 ml-auto group-hover:translate-x-0.5 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
            </svg>
        </button>

        {{-- Anteprima testo --}}
        <div id="text-preview" class="hidden mt-2">
            <div class="flex items-center justify-between mb-1">
                <p class="text-xs font-medium text-gray-500">Anteprima</p>
                <span id="copy-badge" class="hidden text-xs text-green-600 font-medium">✓ Copiato!</span>
            </div>
            <pre id="text-content" class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs text-gray-700 whitespace-pre-wrap overflow-auto max-h-64 font-mono"></pre>
        </div>
    </div>

  </div>

    {{-- Anteprima designazioni --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden lg:sticky lg:top-6">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 text-sm">Anteprima ({{ $designations->count() }} risultati)</h2>
        </div>
        @if ($designations->isEmpty())
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Nessuna designazione corrispondente ai filtri.</div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($designations as $d)
                    <li class="px-5 py-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $d->match->homeTeam->name }} vs {{ $d->match->awayTeam->name }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ \Carbon\Carbon::parse($d->match->date_time)->format('d/m/Y H:i') }}
                                · {{ $d->referee->name }}
                            </p>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold flex-shrink-0
                            @if ($d->status === 'pending')   bg-yellow-100 text-yellow-800
                            @elseif ($d->status === 'confirmed') bg-green-100 text-green-800
                            @elseif ($d->status === 'completed') bg-blue-100 text-blue-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($d->status) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

</div>

<script>
function params() {
    const f = document.getElementById('report-form');
    const p = new URLSearchParams();
    ['date_from','date_to','status'].forEach(k => {
        const v = f.querySelector(`[name="${k}"]`).value;
        if (v) p.set(k, v);
    });
    return p.toString() ? '?' + p.toString() : '';
}

function download(format) {
    window.location.href = '{{ route("reports." . "pdf") }}'.replace('pdf', format) + params();
}

async function copyText() {
    const url = '{{ route("reports.text") }}' + params();
    const res = await fetch(url);
    const text = await res.text();

    document.getElementById('text-preview').classList.remove('hidden');
    document.getElementById('text-content').textContent = text;

    try {
        await navigator.clipboard.writeText(text);
        const badge = document.getElementById('copy-badge');
        badge.classList.remove('hidden');
        setTimeout(() => badge.classList.add('hidden'), 2500);
    } catch {
        // clipboard not available — show preview only
    }
}
</script>
@endsection
